<?php
/*
 * Copyright (c) 2022 Stefan Swerk
 * All rights reserved.
 *
 * Unless required by applicable law or agreed to in writing, software is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 */

namespace GTN\test;

use GTN\import\XlsImporter;
use GTN\Logger;
use PHPUnit\Framework\TestCase;

define('TESTROOT', dirname(__FILE__, 1));

/**
 * @covers \GTN\import\XlsImporter
 */
class XlsImporterTest extends TestCase {

    /**
     * @dataProvider xlsProvider
     */
    public function testReadXlsFileHeader(string $filename, string $sheetname, int $expectedQuestions, int $expectedAnswers): void {
        Logger::info("Reading ", ['file' => TESTROOT.$filename, 'sheet' => $sheetname ]);
        $xlsImporter = new XlsImporter(TESTROOT.$filename, $sheetname);
        Logger::info("Sheetnames ", $xlsImporter->getSpreadsheet()->getSheetNames());

        self::assertContainsEquals($sheetname, $xlsImporter->getSpreadsheet()->getSheetNames());


        $headers = $xlsImporter->readHeader();
        Logger::info("Headers ", $headers);

        self::assertContainsEquals("id", $headers);
        self::assertContainsEquals("category", $headers);
        self::assertContainsEquals("parent", $headers);
        self::assertContainsEquals("name", $headers);
        self::assertContainsEquals("questiontext", $headers);
        self::assertContainsEquals("questiontextformat", $headers);
        self::assertContainsEquals("generalfeedback", $headers);
        self::assertContainsEquals("generalfeedbackformat", $headers);
        self::assertContainsEquals("defaultmark", $headers);
        self::assertContainsEquals("penalty", $headers);
        self::assertContainsEquals("qtype", $headers);
        self::assertContainsEquals("length", $headers);
        self::assertContainsEquals("stamp", $headers);
        self::assertContainsEquals("version", $headers);
        self::assertContainsEquals("hidden", $headers);
        self::assertContainsEquals("timecreated", $headers);
        self::assertContainsEquals("timemodified", $headers);
        self::assertContainsEquals("createdby", $headers);
        self::assertContainsEquals("modifiedby", $headers);
        self::assertContainsEquals("idnumber", $headers);

        self::assertTrue($xlsImporter->verifyHeaderSemantic($headers));
    }

    /**
     * @dataProvider xlsProvider
     * @throws \Exception
     */
    public function testReadFirstXlsFileRecord(string $filename, string $sheetname, int $expectedQuestions, int $expectedAnswers): void
    {
        Logger::info("Reading first record of ", ['file' => TESTROOT.$filename, 'sheet' => $sheetname ]);
        $xlsImporter = new XlsImporter(TESTROOT.$filename, $sheetname);
        $xlsImporter->readHeader();

        $dataRow = $xlsImporter->readNextDataRow(); // first data row at index 1, line number 2
        Logger::info("First data row ", $dataRow);

        $parsedDataRow = $xlsImporter->parseDataRow($dataRow);
        self::assertTrue(isset($parsedDataRow['id']));

        $rowEntity = $xlsImporter->createRawRowEntity($parsedDataRow, true);
        self::assertNotNull($rowEntity);

        Logger::info("First data raw row enttity ", ['type' => get_class($rowEntity),
                'id' => $rowEntity->getId(), 'name' => $rowEntity->getName(), 'parentId' => $rowEntity?->getParentEntity()?->getId(),
                'text' => $rowEntity->getText()]);

    }

    /**
     * @dataProvider xlsProvider
     * @throws \Exception
     */
    public function testImportAllXlsFileRecords(string $filename, string $sheetname, int $expectedQuestions, int $expectedAnswers): void
    {
        Logger::info("Reading first record of ", ['file' => TESTROOT.$filename, 'sheet' => $sheetname ]);
        $xlsImporter = new XlsImporter(TESTROOT.$filename, $sheetname);

        $allRowEntites = $xlsImporter->importAll();
        $questions = $xlsImporter->getKnownQuestionRowEntites();
        $answers = $xlsImporter->getKnownAnswerRowEntites();

        self::assertCount($expectedQuestions, $questions);
        self::assertCount($expectedAnswers, $answers);
        self::assertCount($expectedQuestions+$expectedAnswers, $allRowEntites);

    }

    /**
     * @return array[] ['filepath', 'sheetname', expectedNrOfQuestionRecords, expectedNumberOfAnswerRecords]
     */
    public function xlsProvider(): array
    {
        return [
                '00'  => ["/data/00.mdl_questions_032022.xls", "Tabelle1", 890, 406],
        ];
    }
}
