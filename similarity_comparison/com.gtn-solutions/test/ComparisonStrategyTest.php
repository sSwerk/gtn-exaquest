<?php
/*
 * Copyright (c) 2022 Stefan Swerk
 * All rights reserved.
 *
 * Unless required by applicable law or agreed to in writing, software is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 */

namespace GTN\test;

use Exception;
use GTN\comparator\QuestionOnlyComparator;
use GTN\import\XlsImporter;
use GTN\Logger;
use GTN\strategy\ComparisonStrategyFactory;
use GTN\strategy\editbased\JaroWinklerStrategy;
use GTN\strategy\editbased\SmithWatermanGotohStrategy;
use PHPUnit\Framework\TestCase;

/**
 * @covers \GTN\strategy\ComparisonStrategyFactory
 */
class ComparisonStrategyTest extends TestCase {
    private static JaroWinklerStrategy $jaroStrategy;
    private static SmithWatermanGotohStrategy $smithWatermanStrategy;
    private static XlsImporter $xlsImporter;

    /**
     * @throws Exception
     */
    public static function setUpBeforeClass(): void {
        self::$jaroStrategy = ComparisonStrategyFactory::createJaroWinklerStrategy();
        self::$smithWatermanStrategy = ComparisonStrategyFactory::createSmithWatermanGotohStrategy();

        self::$xlsImporter = new XlsImporter();
        self::$xlsImporter->importAll();
    }

    /**
     * Convenience test case for direct comparison of various algorithms,
     * specify multiple question pairs in the data provider array, and the computations will be performed
     * using all strategies defined
     *
     * @dataProvider questionProvider
     * @throws Exception
     */
    public function testAllStrategies(int $firstId, int $secondId): void {
        $questions = self::$xlsImporter->getKnownQuestionRowEntites();

        self::assertArrayHasKey($firstId, $questions);
        self::assertArrayHasKey($secondId, $questions);

        $q1 = $questions[$firstId];
        $q2 = $questions[$secondId];

        Logger::info("Testing record using all strategies ", ['qId1' => $firstId, 'text1' => $q1->getText(),
                'qId2' => $secondId, 'text2' => $q2->getText()]);

        $jaroQComparator = new QuestionOnlyComparator(self::$jaroStrategy, 0.8, false, 1);
        $smithQComparator = new QuestionOnlyComparator(self::$smithWatermanStrategy, 0.8, false, 1);

        $distanceJaro = self::$jaroStrategy->getDistance($q1->getText(), $q2->getText());
        $distanceSmith = self::$smithWatermanStrategy->getDistance($q1->getText(), $q2->getText());

        $jaroQComparator->addQuestionRowEntity($q1);
        $jaroQComparator->addQuestionRowEntity($q2);
        $smithQComparator->addQuestionRowEntity($q1);
        $smithQComparator->addQuestionRowEntity($q2);

        Logger::info('Computed Distances', ['JaroWinkler' => $distanceJaro, 'SmithWatermanGotoh' => $distanceSmith]);
        Logger::info('Computed Distances using comparator', ['JaroWinkler' => $jaroQComparator->getDistanceMatrix(),
                'SmithWatermanGotoh' => $smithQComparator->getDistanceMatrix()]);
        // TODO: add suitable assertions
    }

    // format: questionId#1, questionId#2
    public function questionProvider(): array
    {
        return [
                [1, 1],
                [4, 4],
                [1, 4],
                [1, 7],
                [1, 8],
                [4, 7],
                [4, 8],
                [7, 8]
        ];
    }

}
