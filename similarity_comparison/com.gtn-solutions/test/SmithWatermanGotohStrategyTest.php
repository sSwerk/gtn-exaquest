<?php
/*
 * Copyright (c) 2022 Stefan Swerk
 * All rights reserved.
 *
 * Unless required by applicable law or agreed to in writing, software is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 */

namespace GTN\test;

use GTN\comparator\QuestionOnlyComparator;
use GTN\import\XlsImporter;
use GTN\Logger;
use GTN\strategy\ComparisonStrategyFactory;
use GTN\strategy\editbased\SmithWatermanGotohStrategy;
use PHPUnit\Framework\TestCase;

/**
 * @covers \GTN\strategy\editbased\SmithWatermanGotohStrategy
 */
class SmithWatermanGotohStrategyTest extends TestCase {
    private static SmithWatermanGotohStrategy $strategy;

    public static function setUpBeforeClass(): void {
       self::$strategy = ComparisonStrategyFactory::createSmithWatermanGotohStrategy();
    }

    /**
     * @dataProvider stringProvider
     * @throws \Exception
     */
    public function testSmithWatermanGotohCompare(string $first, string $second, bool $expected): void
    {
        Logger::info(self::$strategy->getSmithWatermanGotohDistance($first,$second));
        $this->assertSame($expected, self::$strategy->isSimilar($first,$second));
    }

    public function stringProvider(): array
    {
        return [
                'equal'  => ["aString", "aString", true],
                'not equal' => ["abc", "def", false],
                'very similar' => ["aString", "bString", true],
                'not quite similar'  => ["aString", "bStrinf", false]
        ];
    }

    /**
     * @return array[] [filepath, threshold, nrOfExpectedDuplicatesTotal]
     */
    public function xlsDataProvider(): array
    {
        return [
                '00'  => ["/data/00.mdl_questions_032022.xls", "Tabelle1", 0.0, 890],
                '01'  => ["/data/00.mdl_questions_032022.xls", "Tabelle1", 0.8, 492],
                '02'  => ["/data/00.mdl_questions_032022.xls", "Tabelle1", 0.9, 433],
                '03'  => ["/data/00.mdl_questions_032022.xls", "Tabelle1", 0.99, 392],
        ];
    }

    /**
     * @dataProvider xlsDataProvider
     * @throws \Exception
     */
    public function testSmithWatermanGotohAllRecords(string $filename, string $sheetname, float $threshold, int $nrDuplicates): void {
        Logger::info("SmithWatermanGotoh: Reading records of ", ['file' => dirname(__FILE__, 1).$filename, 'sheet' => $sheetname ]);
        $xlsImporter = new XlsImporter(dirname(__FILE__, 1).$filename, $sheetname);

        $allRowEntites = $xlsImporter->importAll();
        $questions = $xlsImporter->getKnownQuestionRowEntites();

        $qComparator = new QuestionOnlyComparator(self::$strategy, $threshold, true, 16);
        $qComparator->addQuestionRowEntities($questions);
        $distanceMatrix = $qComparator->getDistanceMatrix();
        $similarQuestionsMatrix= $qComparator->createSimilarQuestionsMatrix();

        Logger::info("Similar records ", $similarQuestionsMatrix);

        // number of distance matrix keys/values must be the same as te number of total question records
        self::assertCount(count($xlsImporter->getKnownQuestionRowEntites()), array_keys($distanceMatrix));
        self::assertCount(count($xlsImporter->getKnownQuestionRowEntites()), array_values($distanceMatrix));

        // compare number of expected duplicates
        $allSimilarQuestions = array_merge(...$similarQuestionsMatrix);
        $uniqueSimilarQuestions = array_unique(array_values($allSimilarQuestions));
        self::assertCount($nrDuplicates, $uniqueSimilarQuestions);

        //print_r($qComparator->createSimilarQuestionsMatrix());

    }
}
