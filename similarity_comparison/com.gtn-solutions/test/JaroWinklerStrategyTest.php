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
use GTN\strategy\editbased\JaroWinklerStrategy;
use PHPUnit\Framework\TestCase;

/**
 * @covers \GTN\strategy\editbased\JaroWinklerStrategy
 */
class JaroWinklerStrategyTest extends TestCase {
    private static JaroWinklerStrategy $jaroStrategy;

    public static function setUpBeforeClass(): void {
        self::$jaroStrategy = ComparisonStrategyFactory::createJaroWinklerStrategy();
    }

    /**
     * @dataProvider stringProvider
     * @throws \Exception
     */
    public function testJaroWinklerCompare(string $first, string $second, bool $expected): void
    {
        $this->assertSame($expected, self::$jaroStrategy->isSimilar($first,$second));
    }

    public function stringProvider(): array
    {
        return [
                'equal'  => ["aString", "aString", true],
                'not equal' => ["abc", "def", false],
                'very similar' => ["aString", "bString", true],
                'quite similar1'  => ["Vukovara", "Vukovarska", true],
                'quite similar2'  => ["Riječka", "Riječku", true],
                'quite similar3'  => ["Pilara", "Pilareva", true],
                'quite similar4'  => ["Šenoa", "Šenoina", true],
                'quite similar5'  => ["Gaja", "Gajeva", true],
                'quite similar6'  => ["Gotovca", "Gotovčeva", true],
                'quite similar7'  => ["Dane", "Dance", true],
                'quite similar8'  => ["Dane", "Dan", true],
                'quite similar9'  => ["Paje", "Page", true],
                'quite similar10'  => ["Sutonska", "Sotonska", true],
                'quite similar11'  => ["Međimurska", "Međimurje", true],
        ];
    }

    /**
     * @return array[] [filepath, threshold, nrOfExpectedDuplicatesTotal]
     */
    public function xlsDataProvider(): array
    {
        return [
                '00'  => ["/data/00.mdl_questions_032022.xls", "Tabelle1", 0.0, 890],
                '01'  => ["/data/00.mdl_questions_032022.xls", "Tabelle1", 0.8, 735],
                '02'  => ["/data/00.mdl_questions_032022.xls", "Tabelle1", 0.9, 492],
                '03'  => ["/data/00.mdl_questions_032022.xls", "Tabelle1", 0.99, 384],
        ];
    }

    /**
     * @dataProvider xlsDataProvider
     * @throws \Exception
     */
    public function testJaroWinklerAllRecords(string $filename, string $sheetname, float $threshold, int $nrDuplicates): void {
        Logger::info("JaroWinkler: Reading records of ", ['file' => dirname(__FILE__, 1).$filename, 'sheet' => $sheetname ]);
        $xlsImporter = new XlsImporter(dirname(__FILE__, 1).$filename, $sheetname);

        $allRowEntites = $xlsImporter->importAll();
        $questions = $xlsImporter->getKnownQuestionRowEntites();

        $qComparator = new QuestionOnlyComparator(self::$jaroStrategy, $threshold, true, 16);
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
