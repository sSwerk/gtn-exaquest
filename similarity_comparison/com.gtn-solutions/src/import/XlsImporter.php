<?php
/*
 * Copyright (c) 2022 Stefan Swerk
 * All rights reserved.
 *
 * Unless required by applicable law or agreed to in writing, software is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 */
namespace GTN\import;

use DateTimeImmutable;
use Exception;
use GTN\comparator\QuestionOnlyComparator;
use GTN\Logger;
use GTN\model\AnswerRowEntity;
use GTN\model\NoHTMLRowEntity;
use GTN\model\QuestionRowEntity;
use GTN\model\RawRowEntity;
use GTN\strategy\ComparisonStrategyFactory;

define('ROOT', dirname(__FILE__, 4));
require_once(ROOT.'/vendor/autoload.php');

class XlsImporter {
    // as defined in the 00.mdl_questions_032022.xls Excel worksheet, the ordering is important and assumed fixed
    private const EXPECTED_HEADERS = array('id', 'category', 'parent', 'name', 'questiontext', 'questiontextformat', 'generalfeedback',
            'generalfeedbackformat', 'defaultmark', 'penalty', 'qtype', 'length', 'stamp', 'version', 'hidden', 'timecreated',
            'timemodified', 'createdby', 'modifiedby', 'idnumber');

    private string $inputFilePath; // path to the Excel sheet

    private \PhpOffice\PhpSpreadsheet\Reader\IReader $reader;
    private \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet;
    private \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $activeWorksheet;
    private \PhpOffice\PhpSpreadsheet\Worksheet\RowIterator $headerRowIterator;
    private \PhpOffice\PhpSpreadsheet\Worksheet\RowIterator $dataRowIterator;

    private array $headerIndicesMap; // [
    private bool $isFinished = false; // Raw Data Import from excel sheet, toggled as by reaching iterator end

    // internal registry for creating child -> parent mappings. array of arrays [parentIntId -> [childIntId1,childIntId2,...]]
    private array $childParentRefMap;
    private array $knownRawRowEntities; //[elementId -> RawRowEntity]
    private array $knownQuestionRowEntites; //[elementId -> QuestionRowEntity]
    private array $knownAnswerRowEntites;//[elementId -> AnswerRowEntity]

    /**
     *
     * @param string $inputFilePath path to an Excel Moodle question/answer export file
     * @param string $sheetName the name of the sheet to import
     *
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function __construct(string $inputFilePath=ROOT."/com.gtn-solutions/test/data/00.mdl_questions_032022.xls", string $sheetName="Tabelle1") {
        Logger::debug('Construction XlsImporter with filepath|sheetname',  ['filepath' => $inputFilePath, 'sheetName' => $sheetName ]);

        $this->inputFilePath = $inputFilePath;
        $this->reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($inputFilePath);
        $this->reader->setReadDataOnly(true);
        $this->reader->setLoadSheetsOnly([$sheetName]);
        $this->spreadsheet = $this->reader->load($inputFilePath);
        $this->activeWorksheet = $this->spreadsheet->getActiveSheet();
        $this->headerRowIterator = $this->activeWorksheet->getRowIterator(endRow: 1);
        $this->dataRowIterator = $this->activeWorksheet->getRowIterator(startRow: 2);
        $this->childParentRefMap = array();
        $this->knownRawRowEntities = array();
        $this->knownQuestionRowEntites = array();
        $this->knownAnswerRowEntites = array();
    }

    /**
     * Returns a map that contains entries that map a excel column name (e.g. 'A', 'B') to their header value (e.g. 'id', 'createdBy')
     *
     * This function should only be called once.
     *
     * @return array a map consisting of ['COLUMN_NAME' -> 'HEADER_VALUE']
     */
    public function readHeader(): array {
        if(isset($this->headerIndicesMap)) {
            Logger::warning('Header was already read before, returning existing header map');
            return $this->headerIndicesMap;
        }

        Logger::info('Reading header from file (first line)');

        $headerRow = $this->headerRowIterator->current();
        $headerCellIterator = $headerRow->getCellIterator();

        // e.g. 'A' -> 'id'
        $this->headerIndicesMap = array();

        foreach ($headerCellIterator as $idx=>$cell) {
            $cellValue = $cell->getValue();
            $this->verifyStringType($idx);
            $this->verifyStringType($cellValue);

            $this->headerIndicesMap[$idx] = $cellValue;
        }

        return $this->headerIndicesMap;
    }

    /**
     * returns the raw data map as returned by the library (unchecked)
     * It will consist of mappings between Excel column name (e.g. 'A', 'K') to their column value (e.g. '1', 'multianswer')
     *
     * The data is preserved/not modified in any way, as returned by the XLS library
     *
     * @throws Exception
     * @returns empty array if there is no more data to be read
     */
    public function readNextDataRow() : array {
        if($this->isFinished) {
            Logger::debug('Finished reading all data rows, returning empty array');
            return array();
        }
        if(!isset($this->dataRowIterator)) {
            throw new Exception("Data row iterator is not ready, please verify your data model");
        }

        if(!isset($this->headerIndicesMap)) {
            throw new Exception("Header Map must be parsed first, please call function readHeader");
        }

        if(!$this->verifyHeaderSemantic($this->headerIndicesMap)) {
            throw new Exception("Header Map is semantically invalid, please check your data model and error log");
        }

        $dataRow  = $this->dataRowIterator->current();
        $dataCellIterator = $dataRow->getCellIterator();
        $dataIndicesMap = array();

        Logger::debug('Reading cells of row ',  ['#' => $dataRow->getRowIndex()]);

        foreach ($dataCellIterator as $idx=>$cell) {
            $cellValue = $cell->getValue();
            $dataIndicesMap[$idx] = $cellValue;
        }

        // advance iterator if possible, finish otherwise
        if($this->dataRowIterator->valid()) {
            $this->dataRowIterator->next();
        } else {
            $this->isFinished = true;
        }

        return $dataIndicesMap;
    }

    /**
     * Joins the two arrays dataRow and headerIndicesMap, essentially by using the Excel indices ('A', 'B', ...)
     *
     * @param array $dataRow as returned by the method readNextDataRow, may not be null
     * @return array returns a map consisting of 'header_value' -> 'data_value'
     */
    public function parseDataRow(array $dataRow) : array {
        // merge header map with data row map first
        $dataMapWithHeaders = array();

        foreach($this->headerIndicesMap as $idx=>$header) {
           if($this->verifyHeaderExistsInDataRow($idx,$dataRow)) {
               $dataMapWithHeaders[$header] = $dataRow[$idx];
           } else {
               Logger::warning('Skipping assignment header <-> datarow, since the column index does not match',
                       ['idx' => $idx, 'dataRow' => $dataRow ]);
           }
        }

        return $dataMapWithHeaders;
    }

    /**
     * Transforms the raw data as returned by method parseDataRow into an object of type RawRowEntity.
     *
     * Warning/potential side effect:
     *      The pointer to the parent RawRowEntity may be undefined until the parent entity has been created
     *      Currently, the pointer will be saved as an integer reference (id) within the array childParentRefMap
     *      Structure of this array is [parentId -> [childId1, childId2, ...]
     *      As soon as the reference for a child to its parent has been set, the childId will be removed from the childParentRefMap
     *      After the entire dataset has been parsed, the childParentRefMap MUST be empty or should contain parent-References only.
     *      In case it is not empty, i.e. it still contains child-ids, the dataset/datamodel is INCONSISTENT.
     *
     * All references of returned RawRowEntities will be saved in the array knownRawRowEntities for further processing.
     * TODO: this may use significant amount of RAM, consider switching to frame based processing
     *
     * @param array $parsedDataRow
     * @param bool $stripHTML true -> return NoHTMLRowEntity, false -> return RawRowEntity
     * @return RawRowEntity
     * @throws Exception if the required headers are not defined as keys within the given dataRow
     */
    public function createRawRowEntity(array $parsedDataRow, bool $stripHTML = true) : RawRowEntity {
        if(array_keys($parsedDataRow) != array_values($this->headerIndicesMap)) {
            // TODO log warning
            throw new Exception("Required headers not presented in the given data Row, has it been read and parsed already?");
        }

        $rowEntity = $stripHTML ? new NoHTMLRowEntity() : new RawRowEntity();
        $rowEntity = $this->isQuestion($parsedDataRow) ? new QuestionRowEntity($rowEntity) : new AnswerRowEntity($rowEntity);

        $rowEntity->setId($parsedDataRow[self::EXPECTED_HEADERS[0]]); // int
        $rowEntity->setCategory($parsedDataRow[self::EXPECTED_HEADERS[1]]); // int
        // add to known entities map
        $this->mapKnownRawRowEntity($rowEntity);

        $rowEntity->setParentEntity(null); // initialize with null ref
        $parentRefInt = $parsedDataRow[self::EXPECTED_HEADERS[2]]; // -> int id ref to parent entity, if 0 no ref? TODO


        if (isset($this->childParentRefMap[$parentRefInt]) && is_array($this->childParentRefMap[$parentRefInt])) {
            // push to array if it does exist
            $this->childParentRefMap[$parentRefInt][] = $rowEntity->getId();
        } else {
            $this->childParentRefMap[$parentRefInt] = array($rowEntity->getId());
        }

        $rowEntity->setName($parsedDataRow[self::EXPECTED_HEADERS[3]]);
        $rowEntity->setText($parsedDataRow[self::EXPECTED_HEADERS[4]]);
        $format = $parsedDataRow[self::EXPECTED_HEADERS[5]];
        $rowEntity->setFormat($format ?? -1);
        //$rowEntity->setGeneralFeedback($parsedDataRow[self::EXPECTED_HEADERS[6]]); // TODO irrelevant?
        //$rowEntity->setGeneralFeedbackFormat($parsedDataRow[self::EXPECTED_HEADERS[7]]); // TODO irrelevant?
        $rowEntity->setDefaultMark((float) $parsedDataRow[self::EXPECTED_HEADERS[8]]); // ENHANCEMENT maybe perform type verification
        $rowEntity->setPenalty((float) $parsedDataRow[self::EXPECTED_HEADERS[9]]); // ENHANCEMENT maybe perform type verification
        $rowEntity->setQtype($parsedDataRow[self::EXPECTED_HEADERS[10]] ?? '');
        $rowEntity->setLength($parsedDataRow[self::EXPECTED_HEADERS[11]] ?? 0);
        $rowEntity->setStamp($parsedDataRow[self::EXPECTED_HEADERS[12]] ?? '');
        $rowEntity->setVersion($parsedDataRow[self::EXPECTED_HEADERS[13]] ?? '');
        $rowEntity->setHidden((bool) ($parsedDataRow[self::EXPECTED_HEADERS[14]] ?? true)); // ENHANCEMENT maybe perform type verification

        $timestampCreated = $parsedDataRow[self::EXPECTED_HEADERS[15]] ?? 0.0; // float
        $rowEntity->setTimeCreatedDate($this->parseDataTimeFromFloat($timestampCreated));
        $timestampModified = $parsedDataRow[self::EXPECTED_HEADERS[16]] ?? 0.0; // float
        $rowEntity->setTimeModifiedDate($this->parseDataTimeFromFloat($timestampModified));

        $rowEntity->setCreatedBy($parsedDataRow[self::EXPECTED_HEADERS[17]] ?? -1);
        $rowEntity->setModifiedBy($parsedDataRow[self::EXPECTED_HEADERS[18]] ?? -1);
        //$rowEntity->setIdnumber($parsedDataRow[self::EXPECTED_HEADERS[19]]); // TODO irrelevant?

        return $rowEntity;
    }

    /**
     * @param array $parsedDataRow
     * @return bool true, iff the raw parent id equals 0
     */
    private function isQuestion(array $parsedDataRow) : bool {
        $parentRefId = $parsedDataRow[self::EXPECTED_HEADERS[2]];

        return $parentRefId === 0;
    }

    /**
     * Tries to reverse map known parent RawRowEntities to their children.
     *
     * Warning/potential side effect:
     *      The pointer to the parent RawRowEntity may be undefined until the parent entity has been created
     *      Currently, the pointer will be saved as an integer reference (id) within the array childParentRefMap
     *      Structure of this array is [parentId -> [childId1, childId2, ...]
     *      As soon as the reference for a child to its parent has been set, the childId will be removed from the childParentRefMap
     *      After the entire dataset has been parsed, the childParentRefMap MUST be empty or should contain parent-References only.
     *      In case it is not empty, i.e. it still contains child-ids, the dataset/datamodel is INCONSISTENT.
     */
    public function processChildParentRegistry() : void {
        // [parent -> [child1,child2,....]]
        foreach ($this->childParentRefMap as $parentIdx=>$childArray) {
            // look for the parent row entity, if it is already known/built
            $parentRowEntity = $this->findRowEntityById($parentIdx);
            if($parentRowEntity !== null) {
                // assign the known parent to all childs and remove the child from the childrenRefMap array
                foreach ($childArray as $childIdx => $childId) {
                    $childRowEntity = $this->findRowEntityById($childId);
                    if($childRowEntity !== null) {
                        $childRowEntity->setParentEntity($parentRowEntity);
                        Logger::debug('Mapping children (answers) to their parent (questions)',
                                ['childIdx|childId' => $childIdx .'|'.$childId, 'parentId' => $parentIdx]);
                        unset($childArray[$childIdx]);
                    }
                }
            }
        }
    }

    /**
     * @throws Exception if the parser cannot read the input data
     */
    public function importAll() : array {
        Logger::info("Starting data import, reading and verifying headers");
        $allRowEntities = array();
        $headers = $this->readHeader();
        if(!$this->verifyHeaderSemantic($headers)) {
            Logger::error("Invalid headers, please check data source");
        }

        Logger::info("Headers appear valid, starting to read the raw data rows");
        $dataRow = $this->readNextDataRow(); // first data row at index 1
        for($rowNr=2, $rowExists = count($dataRow); $rowExists > 0;$dataRow = $this->readNextDataRow(), $rowNr++) {
            $parsedDataRow = $this->parseDataRow($dataRow);

            if(!isset($parsedDataRow['id'])) {
                Logger::warning("ID column is empty or could not be parsed, breaking parser loop at row #", [ 'rowIdx' => $rowNr]);
                break;
            }

            $rowEntity = $this->createRawRowEntity($parsedDataRow, true);
            $allRowEntities[] = $rowEntity;
            Logger::DEBUG("Created raw row entity: ", ['type' => get_class($rowEntity), 'rowIdx' => $rowNr, 'id' => $rowEntity->getId(), 'name' => $rowEntity->getName(), 'parentId' => $rowEntity?->getParentEntity()?->getId(), 'text' => $rowEntity->getText()]);
        }

        Logger::info("All data rows read, mapping children to their parents", ['totalRowEntities' => count($allRowEntities)]);

        $this->processChildParentRegistry();

        Logger::info("All data raw row entities imported", ['totalRowEntities' => count($allRowEntities), 'nrQuestions' => count($this->knownQuestionRowEntites), 'nrAnswers' => count($this->knownAnswerRowEntites)]);

        return $allRowEntities;
    }

    /**
     * Puts the given rowEntity into:
     *  - knownRawRowEntities
     *  - knownAnswerRowEntites or knownQuestionRowEntites depending on the given type
     *
     * @param QuestionRowEntity|AnswerRowEntity $rowEntity
     * @return void
     */
    private function mapKnownRawRowEntity(QuestionRowEntity|AnswerRowEntity $rowEntity): void {
        $this->knownRawRowEntities[$rowEntity->getId()] = $rowEntity;

        if($rowEntity instanceof QuestionRowEntity) {
            $this->knownQuestionRowEntites[$rowEntity->getId()] = $rowEntity;
        } else {
            $this->knownAnswerRowEntites[$rowEntity->getId()] = $rowEntity;
        }
    }

    /**
     * helper function to look for an existing RawRowEntity by its ID.
     *
     *
     * @param int $id the ID as defined by the dataset
     * @return RawRowEntity|null null, iff the RawRowEntity was not created yet/is not in the dataset
     */
    private function findRowEntityById(int $id) : ?RawRowEntity {
        if(!isset($id) || !array_key_exists($id, $this->knownRawRowEntities)) {
            Logger::warning("There is no known row entity with the provided id", ['providedId' => $id]);
            return null;
        }

        return $this->knownRawRowEntities[$id];
    }

    /**
     * Given a float timestamp as returned by the Excel importer library, this method will return a DateTimeImmutable object.
     *
     * TODO: verify that the timestamp returned by the Excel library is an Unix Timestamp, i.e. that the DateTime is correct
     *
     * @param float $timestamp UNIX Timestamp
     * @return DateTimeImmutable
     */
    private function parseDataTimeFromFloat(float $timestamp) : DateTimeImmutable {
        $date = new DateTimeImmutable();
        return $date->setTimestamp((int) $timestamp); // ENHANCEMENT maybe perform type verification
    }

    /**
     * Helper function that verifies that a given non-null string header exists within the given dataRow
     *
     * @param $header string, non-null
     * @param $dataRow array a raw data row from the Worksheet
     * @return bool false, if the header is null or not a string, or the dataRow array does not contain a key with the given header
     */
    private function verifyHeaderExistsInDataRow(string $header, array $dataRow) : bool {
        if(!$this->verifyStringType($header)) {
            return false;
        }

        if(!array_key_exists($header, $dataRow)) {
            Logger::error('Error: expected the following Header key but it does not exist in the given datarow', [ 'headerKey' => $header]);
            return false;
        }

        return true;
    }

    /**
     * Performs initial semantic verifications for the provided dataset (Excel sheet)
     *
     * -> There must be exactly 20 Headers
     * -> all Headers as defined in the constant EXPECTED_HEADERS must be present in the parsed Header-Row
     *
     * @param array $headerIndicesMap
     * @return bool
     */
    public function verifyHeaderSemantic(array $headerIndicesMap) : bool {
        $headerCount = count($headerIndicesMap);
        if(count($headerIndicesMap) !== count(self::EXPECTED_HEADERS)) {
            Logger::error('Error: header count mismatch',
                    [ 'expectedHeaders' => count(self::EXPECTED_HEADERS), 'actualHeaders' => $headerCount]);

            return false;
        }

        foreach (self::EXPECTED_HEADERS as $expectedHeader) {
            if (!in_array($expectedHeader, $headerIndicesMap, false)) {
                Logger::error('Error: expected header does not exist in the given header map',
                        [ 'expectedHeader' => $expectedHeader, 'actualHeaders' => $headerIndicesMap]);
                return false;
            }
        }

        return true;
    }

    /**
     * Helper function that logs an error and returns false it the given parameter is not a string
     *
     * @param $var mixed to check
     * @return bool true, iff var is of type string
     */
    private function verifyStringType(mixed $var) : bool {
        if(!is_string($var)) {
            Logger::warning('Not a string:', [ 'var' => $var]);

            return false;
        }

        return true;
    }

    /**
     * @return string Path as provided to the constructor
     */
    public function getInputFilePath(): string {
        return $this->inputFilePath;
    }

    /**
     * @return \PhpOffice\PhpSpreadsheet\Spreadsheet the loaded Excel spreadsheet
     */
    public function getSpreadsheet(): \PhpOffice\PhpSpreadsheet\Spreadsheet {
        return $this->spreadsheet;
    }

    /**
     * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet the active Worksheet (see constructor filter by Sheet-Name)
     */
    public function getActiveWorksheet(): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet {
        return $this->activeWorksheet;
    }

    /**
     * @return array
     */
    public function getKnownRawRowEntities(): array {
        return $this->knownRawRowEntities;
    }

    /**
     * @return array
     */
    public function getKnownQuestionRowEntites(): array {
        return $this->knownQuestionRowEntites;
    }

    /**
     * @return array
     */
    public function getKnownAnswerRowEntites(): array {
        return $this->knownAnswerRowEntites;
    }

}

/**
 * quick and dirty test code starts here
 */
/*
$xlsImporter = new XlsImporter();
echo $xlsImporter->getSpreadsheet()->getSheetNames()[0].PHP_EOL;

echo 'custom'.PHP_EOL;

Logger::info("log test", ['extra' => 'information', 'about' => 'anything' ]);
$allRowEntites = $xlsImporter->importAll();
$questions = $xlsImporter->getKnownQuestionRowEntites();

$firstQ = current($questions);
$secondQ = next($questions);
$thirdQ = next($questions);

echo $firstQ->getText() . PHP_EOL;
echo $secondQ->getText() . PHP_EOL;
echo $thirdQ->getText() . PHP_EOL;

//$strategy = ComparisonStrategyFactory::createJaroWinklerStrategy();
$strategy = ComparisonStrategyFactory::createSmithWatermanGotohStrategy();
$qComparator = new QuestionOnlyComparator($strategy, 0.8, true, 16);

//preg_replace('/\s+/', ' ', $firstQ->getText());

$distance = $strategy->getDistance($firstQ->getText(), $secondQ->getText());
echo $distance . PHP_EOL;
echo json_encode($strategy->isSimilar($firstQ->getText(), $thirdQ->getText(), 0.8)) . PHP_EOL;

//$qComparator->addQuestionRowEntity($firstQ);
//$qComparator->addQuestionRowEntity($secondQ);
//$qComparator->addQuestionRowEntity($thirdQ);
$qComparator->addQuestionRowEntities($questions);
$distanceMatrix = $qComparator->getDistanceMatrix();
//print_r($distanceMatrix);


print_r($qComparator->createSimilarQuestionsMatrix());
//echo $rowEntity;

//var_dump($parsedDataRow);
//echo array_values($dataRow)[4] . PHP_EOL;
//var_dump($xlsImporter->parseDataRow($dataRow));
//echo $rowEntity;


//array_push($m[10], ...$t[10], ...$s[10]);
//print_r($m);
*/