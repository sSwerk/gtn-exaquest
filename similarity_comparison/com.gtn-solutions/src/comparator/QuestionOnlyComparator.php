<?php
/*
 * Copyright (c) 2022 Stefan Swerk
 * All rights reserved.
 *
 * Unless required by applicable law or agreed to in writing, software is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 */
namespace GTN\comparator;

use GTN\Logger;
use GTN\model\QuestionRowEntity;
use GTN\strategy\ComparisonStrategy;
use GTN\strategy\ComparisonStrategyFactory;
use GTN\strategy\editbased\JaroWinklerStrategy;
use Amp\Loop;
use Amp\Parallel\Worker\CallableTask;
use Amp\Parallel\Worker\DefaultPool;
use Amp;

require_once(dirname(__FILE__, 4).'/vendor/autoload.php');

/**
 * This Comparator will use the available information from QuestionRowEntity, i.e. it will calculate similarities only for a
 * set of question entities.
 *
 * It is also able to optionally perform the computationally intensive part in a multithreaded way using the Amp Parallel library.
 */
class QuestionOnlyComparator {
    private array $questionList; // list of QuestionRowEntities to compare
    private ComparisonStrategy $comparisonStrategy; // the strategy for calculating the similarity distance
    private float $distanceThreshold; // if the normalized similarity is greater than this threshold, the questions are considered to be similar
    private bool $multithreading; // true -> use multithreaded Amp implementation
    private int $nrOfThreads; // this value indicates the number of threads to utilize, it has no effect if $multihreading == false
    private float $elapsedTime; // internal counter for execution time measurement

    private array $distanceMatrix; // 2D matrix that links two questions to its distance, format [id1][id2] => distance
     // 2D matrix that indicates whether two questions are similar ('true') or not ('false'), based on the given distance threshold
    private array $distanceThresholdMatrix; // format [id1][id2] => true|false

    /**
     * @param ComparisonStrategy|null $comparisonStrategy default strategy is the JaroWinkler implementation
     * @param float $distanceThreshold
     * @param bool $multithreading whether to use multiple threads for the computations
     * @param int $nrOfThreads the number of worker threads to use for the computations, will be ignored iff multithreading is false
     */
    public function __construct(?ComparisonStrategy $comparisonStrategy, float $distanceThreshold=0.8, bool $multithreading = false, int $nrOfThreads = 4) {
        if(isset($comparisonStrategy)) {
            $this->comparisonStrategy = $comparisonStrategy;
        } else {
            $this->comparisonStrategy = ComparisonStrategyFactory::createJaroWinklerStrategy();
        }

        $this->questionList = array();
        $this->distanceMatrix = array();
        $this->distanceThresholdMatrix = array();
        $this->setDistanceThreshold($distanceThreshold);
        $this->multithreading = $multithreading;
        $this->nrOfThreads = $nrOfThreads;
        $this->elapsedTime = microtime(true);
    }

    /**
     * Add a QuestionRowEntity to this comparator - the computation will be performed immediately if updateSimilarityMatrix==true.
     * Otherwise, the caller has to call the update function at some point in time.
     *
     * It is not possible to add the same questionRowEntity object twice.
     *
     * @param QuestionRowEntity $questionRowEntity
     * @param bool $updateSimilarityMatrix
     */
    public function addQuestionRowEntity(QuestionRowEntity $questionRowEntity, bool $updateSimilarityMatrix=true): void {
        if(!isset($questionRowEntity)) {
            Logger::error('Invalid question row entity, null values are not allowed');
        } else if(in_array($questionRowEntity, $this->questionList, false)) {
            Logger::warning('QuestionRowEntity already known, will not add again', ['duplicateID' => $questionRowEntity->getId()]);
        } else {
            $this->questionList[] = $questionRowEntity;
        }

        if($updateSimilarityMatrix) {
            $this->updateSimilarityMatrix(false, $this->multithreading);
        }
    }

    /**
     * Adds multiple QuestionRowEntity objects and calls the update function afterwards.
     * If an automatic update of the similarity matrix is not wanted, use addQuestionRowEntity() instead.
     *
     * @param array $questionRowEntities
     * @return void
     */
    public function addQuestionRowEntities(array $questionRowEntities): void {
        // type check
        foreach($questionRowEntities as $rowEntity) {
            if($rowEntity instanceof QuestionRowEntity) {
                $this->addQuestionRowEntity($rowEntity, false);
            } else {
                Logger::error('Invalid class detected, will not add to question list', ['expected' => 'QuestionRowEntity', 'actual' => get_class($rowEntity)]);
            }
        }

        $this->updateSimilarityMatrix(false, $this->multithreading);
    }

    /**
     * Update the underlying similarity distance matrices (distanceMatrix, distanceThresholdMatrix) by computing their
     * distance, optionally using multiple threads, optionally overriding existing values.
     *
     * You should not need to call this function manually, it will be called from the addQuestionRowEntity* methods automatically.
     *
     * The number of threads is determined by the field $nrOfThreads (see constructor).
     * The Multithreading implementation partitions the given dataset into row buckets of approximately equal size.
     * Then each bucket will be processed by one thread, if there is one available.
     * Note that the first bucket will most likely take the most time to finish, since the amount of required computations is higher
     * compared to buckets assigned to higher row indices.
     *
     * @param bool $forceUpdate whether to update all values, even existing ones
     * @param bool $multithreading whether to use multiple threads for the computations (experimental)
     * @return void the field matrices (distanceMatrix, distanceThresholdMatrix) will now contain the updated similarity distances
     */
    public function updateSimilarityMatrix(bool $forceUpdate = false, bool $multithreading = false): void {
        $this->getElapsedSecondsSinceLastCall(); // reset timer

        if(count($this->questionList) < 2) {
            Logger::warning('Question list size is <2, cannot compute similarity matrix');
        }
        Logger::info('Computing similarity matrix', ['totalElements' => count($this->questionList)]);

        if($multithreading) {
            // ##### MULTITHREAD START #####

            // A variable to store our fetched results
            $results = [];

            // We can first define tasks and then run them
            $tasks = array();
            $threads  = $this->nrOfThreads;
            $bucketSize = (int) (count($this->questionList) / $threads);
            Logger::info("Partitioning data set",
                    ['threads' => $threads, 'bucketSize' => $bucketSize, 'totalCount' => count($this->questionList)]);
            for($i=0; $i<=$threads; $i++) {
                $start = $i * $bucketSize;
                $end = ($i+1) * $bucketSize;
                $end = min($end, (count($this->questionList)-1)); // calculate indices

                if($start<$end) { // important for last small remainder bucket, if there is one
                    Logger::info("Partition ", ['startIdx' => $start, 'endIdx' => $end]);
                    $tasks[] = new CallableTask(array($this, 'calculateSimilarityDistanceRange'), [$start, $end, $forceUpdate]);
                }
            }

            Logger::info("Spawning multiple worker threads");
            $computedResults = $this->spawnThreads($results, $tasks, $threads);

            // each thread produces its own subset of matrices, this has to be merged now
            foreach($computedResults as $result) {
                // result is an array with the following two keys
                $computedDistanceMatrix = $result['computedDistanceMatrix'];
                $computedDistanceThresholdMatrix = $result['computedDistanceThresholdMatrix'];

                $this->mergeDistanceMatrices($computedDistanceMatrix, $computedDistanceThresholdMatrix);
            }

            Logger::debug("Workers finished processing");

             // ##### MULTITHREAD END #####
        } else {  // single threaded execution
            $this->calculateSimilarityDistanceRange(0, count($this->questionList), $forceUpdate);
        }

        Logger::info("Matrix computation finished",['elapsedSeconds' => $this->getElapsedSecondsSinceLastCall()]);
    }

    /**
     * Launches a thread pool of size $threads and executes all given tasks concurrently.
     *
     * This method will wait for all tasks to finish execution.
     *
     * @param array $results
     * @param array $tasks
     * @param int $threads
     * @return array key = worker#, value: array(computedDistanceMatrix, computedDistanceThresholdMatrix)
     */
    private function spawnThreads(array &$results, array $tasks, int $threads): array {
        // Event loop for parallel tasks
        Loop::run(static function() use (&$results, $tasks, $threads) {
            /**$timer = Loop::repeat(200, static function() {
                \printf(".");
            });
            Loop::unreference($timer);**/

            $pool = new DefaultPool($threads);
            $promises = [];

            foreach ($tasks as $index => $task) {
                $promises[] = Amp\call(function() use ($pool, $index, $task) {
                    $result = yield $pool->enqueue($task);
                    Logger::info("Worker ", ['#' => $index, 'computations' => array_sum(array_map("count", $result))]);

                    return $result;
                });
            }

            $results = yield Amp\Promise\all($promises);

            return yield $pool->shutdown();
        });
        return $results;
    }

    /**
     * Merge the two given matrices into the fields distanceMatrix and distanceThresholdMatrix.
     *
     * This method will override conflicting values.
     *
     * TODO: implement a more efficient merge in case performance issues arise
     * TODO: verify merge results
     *
     * @param array $computedDistanceMatrix merge into distanceMatrix
     * @param array $computedDistanceThresholdMatrix merge into distanceThresholdMatrix
     * @return void
     */
    private function mergeDistanceMatrices(array $computedDistanceMatrix, array $computedDistanceThresholdMatrix): void {
        foreach ($computedDistanceMatrix as $firstID => $secondIDArr) {
            if (!isset($this->distanceMatrix[$firstID])) { // create nested array if it does not exist yet
                $this->distanceMatrix[$firstID] = array();
            }

            if (!isset($this->distanceThresholdMatrix[$firstID])) { // create nested array if it does not exist yet
                $this->distanceThresholdMatrix[$firstID] = array();
            }

            foreach ($secondIDArr as $secondID => $computedDistance) {
                $this->distanceMatrix[$firstID][$secondID] = $computedDistance;
                $this->distanceThresholdMatrix[$firstID][$secondID] = $computedDistanceThresholdMatrix[$firstID][$secondID];
            }
        }
    }

    private function computeBinaryThresholdMatrix(): void {
        Logger::info('Computing binary threshold similarity matrix', ['totalQuestions' => count($this->questionList)]);

        foreach($this->distanceMatrix as $firstID => $secondIDArr) {
            foreach($secondIDArr as $secondID => $distance) {
                $this->distanceThresholdMatrix[$firstID][$secondID] = $distance >= $this->distanceThreshold;
                $this->distanceThresholdMatrix[$secondID][$firstID] = $this->distanceThresholdMatrix[$firstID][$secondID];
            }
        }
    }

    /**
     * wrapper function used for multithreaded computation
     *
     * @param QuestionRowEntity $first
     * @param QuestionRowEntity $second
     * @return float the computed similarity distance
     */
    private function calculateSimilarityDistance(QuestionRowEntity $first, QuestionRowEntity $second): float {
        return $this->comparisonStrategy->getDistance($first->getText(), $second->getText());
    }

    /**
     * Calculate all similarity distances of rows [from - to[
     *
     * @param int $from start index inclusive
     * @param int $to
     * @param bool $forceUpdate whether to update existing values as well
     * @return array of size 2 with indices of ['computedDistanceMatrix'  and 'computedDistanceThresholdMatrix];
     */
    public function calculateSimilarityDistanceRange(int $from, int $to, bool $forceUpdate = false): array {
        if($from < 0 || $from >= $to || $to > count($this->questionList)) {
            Logger::debug('Wont calculate similarity distance range, invalid parameters provided',
                    ['from' => $from, 'to' => $to]);
            return ['computedDistanceMatrix' => $this->distanceMatrix, 'computedDistanceThresholdMatrix' => $this->distanceThresholdMatrix];
        }

        $nrOfComputations = 0; // local to the provided range

        // row based buckets
        $jMax = count($this->questionList);
        for($i=$from;$i<$to;$i++) {
            $firstQuestion = $this->questionList[$i];
            $firstID = $firstQuestion->getId();

            for($j=$i;$j<$jMax;$j++) { // calculate only upper part (half of matrix)
                $secondQuestion = $this->questionList[$j];
                $secondID = $secondQuestion->getId();

                if(!$forceUpdate && isset($this->distanceMatrix[$firstID][$secondID])) {
                    Logger::debug('Skipping distance calculation because forceUpdate is false and a value is already set',
                            ['ID#1' => $firstID, 'ID#2' => $secondID]);
                    continue;
                }

                if($firstID === $secondID) {
                    // same question, similarity must be 1
                    $this->distanceMatrix[$firstID][$secondID] = 1.0;
                    $this->distanceThresholdMatrix[$firstID][$secondID] = true;
                } else {
                    $distance = $this->calculateSimilarityDistance($firstQuestion,$secondQuestion);
                    $this->distanceMatrix[$firstID][$secondID] = $distance;
                    $this->distanceThresholdMatrix[$firstID][$secondID] = $distance >= $this->distanceThreshold;
                    // the reverse mapping should have the same similarity
                    $this->distanceMatrix[$secondID][$firstID] = $distance;
                    $this->distanceThresholdMatrix[$secondID][$firstID] = $this->distanceThresholdMatrix[$firstID][$secondID];
                }
            }
            $nrOfComputations += ($jMax-$i);
            Logger::debug('Calculated similarity distances of question with id ',
                    [$firstID => array_column($this->distanceMatrix, $firstID), 'computations' => $nrOfComputations]);
        }

        return ['computedDistanceMatrix' => $this->distanceMatrix, 'computedDistanceThresholdMatrix' => $this->distanceThresholdMatrix];
    }

    /**
     * This will create a new array that contains only those question IDs where the similarity threshold is reached
     *
     * @param bool $includeIdentity true -> include reference to itself (e.g. 1 -> 1, 2 -> 2)
     * @return array array consisting of [qID -> [similarQID#1, similarQID#2, ....]
     */
    public function createSimilarQuestionsMatrix(bool $includeIdentity=false): array {
        $similarQuestionsAboveThreshold = array();

        foreach($this->distanceThresholdMatrix as $firstID => $secondIDArr) {
            foreach ($secondIDArr as $secondID => $isSimilar) {
                if ($firstID === $secondID) {
                    if($includeIdentity) {
                        $similarQuestionsAboveThreshold[$firstID][] = $secondID;
                    }
                } else if($isSimilar) {
                    $similarQuestionsAboveThreshold[$firstID][] = $secondID;
                }
            }
        }

        return $similarQuestionsAboveThreshold;
    }

    /**
     * @return array
     */
    public function getQuestionList(): array {
        return $this->questionList;
    }

    /**
     * @return ComparisonStrategy|JaroWinklerStrategy
     */
    public function getComparisonStrategy(): ComparisonStrategy|JaroWinklerStrategy {
        return $this->comparisonStrategy;
    }

    /**
     * @return array
     */
    public function getDistanceMatrix(): array {
        return $this->distanceMatrix;
    }

    /**
     * @return array
     */
    public function getDistanceThresholdMatrix(): array {
        return $this->distanceThresholdMatrix;
    }

    /**
     * @return float
     */
    public function getDistanceThreshold(): float {
        return $this->distanceThreshold;
    }

    /**
     * @param float $distanceThreshold in the range [0.0 - 1.0]
     */
    public function setDistanceThreshold(float $distanceThreshold): void {
        if(!isset($distanceThreshold) || $distanceThreshold < 0.0 || $distanceThreshold > 1.0) {
            Logger::error('Invalid distance threshold, will not overwrite existing value',
                    ['invalidVal' => $distanceThreshold, 'currentVal' => $this->distanceThreshold]);
        } else {
            $this->distanceThreshold = $distanceThreshold;
            if(count($this->getQuestionList()) >= 2) {
                Logger::info('Threshold was modified, recomputing binary matrix only');
                $this->computeBinaryThresholdMatrix();
            }
        }
    }

    private function getElapsedSecondsSinceLastCall(): float {
        $currentTime = microtime(true);
        $elapsedSeconds = $currentTime - $this->elapsedTime;
        $this->elapsedTime = $currentTime;

        return $elapsedSeconds;
    }
}