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
use GTN\Logger;
use GTN\model\AnswerRowEntity;
use GTN\model\NoHTMLRowEntity;
use GTN\model\QuestionRowEntity;
use GTN\model\RawRowEntity;

require_once(dirname(__FILE__, 4).'/vendor/autoload.php');

class MoodleImporter {
    // as used in Moodle 4.0
    private const EXPECTED_HEADERS = array('id', 'category', 'parent', 'name', 'questiontext', 'questiontextformat', 'generalfeedback',
            'generalfeedbackformat', 'defaultmark', 'penalty', 'qtype', 'length', 'stamp', 'version', 'timecreated',
            'timemodified', 'createdby', 'modifiedby',
            'status', 'versionid', 'questionbankentryid', 'contextid'); // new in moodle 4.0, also categoryobject, options, hints

    // internal registry for creating child -> parent mappings. array of arrays [parentIntId -> [childIntId1,childIntId2,...]]
    private array $childParentRefMap;
    private array $knownRawRowEntities; //[elementId -> RawRowEntity]
    private array $knownQuestionRowEntites; //[elementId -> QuestionRowEntity]
    private array $knownAnswerRowEntites;//[elementId -> AnswerRowEntity]

    public function __construct() {
        Logger::debug('Construction MoodleImporter');

        $this->childParentRefMap = array();
        $this->knownRawRowEntities = array();
        $this->knownQuestionRowEntites = array();
        $this->knownAnswerRowEntites = array();
    }

    public static function test(): string {
        Logger::info("TESTTODO");
        return "MOODLE_IMPORT_SUCCESS";
    }

    /**
     * @param \stdClass[] $qObjArr
     * @throws Exception
     */
    public function importAll(array $qObjArr) : array {
        Logger::info("Starting data import", ["objCount" => count($qObjArr)]);
        $allRowEntities = array();

        foreach ($qObjArr as $qObj){
            $rowEntity = $this->createRawRowEntity($qObj, true);
            $allRowEntities[] = $rowEntity;
            Logger::DEBUG("Created raw row entity: ", ['type' => get_class($rowEntity), 'id' => $rowEntity->getId(),
                    'name' => $rowEntity->getName(), 'parentId' => $rowEntity?->getParentEntity()?->getId(),
                    'text' => $rowEntity->getText()]);

        }

        Logger::info("All data rows read, mapping children to their parents", ['totalRowEntities' => count($allRowEntities)]);

        $this->processChildParentRegistry();

        Logger::info("All data raw row entities imported", ['totalRowEntities' => count($allRowEntities), 'nrQuestions' => count($this->knownQuestionRowEntites), 'nrAnswers' => count($this->knownAnswerRowEntites)]);

        return $allRowEntities;
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
     * @param \stdClass $qObj
     * @param bool $stripHTML true -> return NoHTMLRowEntity, false -> return RawRowEntity
     * @return RawRowEntity|null
     * @throws Exception if the required headers are not defined as keys within the given dataRow
     */
    public function createRawRowEntity(\stdClass $qObj, bool $stripHTML = true) : RawRowEntity|null {
        if(!$this->verifyHeaderSemantic($qObj)) {
            Logger::error('Error: expected header does not exist in the given header map',
                    [ 'passedObj' => $qObj]);
            throw new Exception("Headers are semantically invalid, please check your data model and error log");
        }

        $propertyVars = get_object_vars($qObj);

        $rowEntity = $stripHTML ? new NoHTMLRowEntity() : new RawRowEntity();
        $rowEntity = $this->isQuestion($propertyVars) ? new QuestionRowEntity($rowEntity) : new AnswerRowEntity($rowEntity);

        $rowEntity->setId($propertyVars[self::EXPECTED_HEADERS[0]]); // int
        $rowEntity->setCategory($propertyVars[self::EXPECTED_HEADERS[1]]); // int
        // add to known entities map
        $this->mapKnownRawRowEntity($rowEntity);

        $rowEntity->setParentEntity(null); // initialize with null ref
        $parentRefInt = $propertyVars[self::EXPECTED_HEADERS[2]]; // -> int id ref to parent entity, if 0 no ref? TODO


        if (isset($this->childParentRefMap[$parentRefInt]) && is_array($this->childParentRefMap[$parentRefInt])) {
            // push to array if it does exist
            $this->childParentRefMap[$parentRefInt][] = $rowEntity->getId();
        } else {
            $this->childParentRefMap[$parentRefInt] = array($rowEntity->getId());
        }

        $rowEntity->setName($propertyVars[self::EXPECTED_HEADERS[3]]);
        $rowEntity->setText($propertyVars[self::EXPECTED_HEADERS[4]]);
        $format = $propertyVars[self::EXPECTED_HEADERS[5]];
        $rowEntity->setFormat($format ?? -1);
        //$rowEntity->setGeneralFeedback($parsedDataRow[self::EXPECTED_HEADERS[6]]); // TODO irrelevant?
        //$rowEntity->setGeneralFeedbackFormat($parsedDataRow[self::EXPECTED_HEADERS[7]]); // TODO irrelevant?
        $rowEntity->setDefaultMark((float) $propertyVars[self::EXPECTED_HEADERS[8]]); // ENHANCEMENT maybe perform type verification
        $rowEntity->setPenalty((float) $propertyVars[self::EXPECTED_HEADERS[9]]); // ENHANCEMENT maybe perform type verification
        $rowEntity->setQtype($propertyVars[self::EXPECTED_HEADERS[10]] ?? '');
        $rowEntity->setLength($propertyVars[self::EXPECTED_HEADERS[11]] ?? 0);
        $rowEntity->setStamp($propertyVars[self::EXPECTED_HEADERS[12]] ?? '');
        $rowEntity->setVersion($propertyVars[self::EXPECTED_HEADERS[13]] ?? '');
        // no longer in moodle 4.0
        //$rowEntity->setHidden((bool) ($propertyVars[self::EXPECTED_HEADERS[14]] ?? true)); // ENHANCEMENT maybe perform type verification

        $timestampCreated = $propertyVars[self::EXPECTED_HEADERS[14]] ?? 0.0; // float
        $rowEntity->setTimeCreatedDate($this->parseDataTimeFromFloat($timestampCreated));
        $timestampModified = $propertyVars[self::EXPECTED_HEADERS[15]] ?? 0.0; // float
        $rowEntity->setTimeModifiedDate($this->parseDataTimeFromFloat($timestampModified));

        $rowEntity->setCreatedBy($propertyVars[self::EXPECTED_HEADERS[16]] ?? -1);
        $rowEntity->setModifiedBy($propertyVars[self::EXPECTED_HEADERS[17]] ?? -1);
        //$rowEntity->setIdnumber($parsedDataRow[self::EXPECTED_HEADERS[19]]); // TODO irrelevant?
        // TODO: add new moodle 4.0 headers if necessary

        return $rowEntity;
    }

    /**
     * Performs initial semantic verifications for the provided dataset (Excel sheet)
     *
     * -> There must be exactly 20 Headers
     * -> all Headers as defined in the constant EXPECTED_HEADERS must be present in the parsed Header-Row
     *
     * @param \stdClass $qObj
     * @return bool
     */
    public function verifyHeaderSemantic(\stdClass $qObj) : bool {
        $headerCount = count(self::EXPECTED_HEADERS);
        $propertyVars = get_object_vars($qObj);
        $propertyVarsKeys = array_keys($propertyVars);
        if(count($propertyVarsKeys) < $headerCount) {
            Logger::error('Error: header count mismatch',
                    [ 'expectedHeaders' => $headerCount, 'actualHeaders' => count($propertyVarsKeys)]);

            return false;
        }

        foreach (self::EXPECTED_HEADERS as $expectedHeader) {
            if (!in_array($expectedHeader, $propertyVarsKeys, false)) {
                Logger::error('Error: expected header does not exist in the given header map',
                        [ 'expectedHeader' => $expectedHeader, 'actualHeaders' => $propertyVarsKeys]);
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $parsedDataRow
     * @return bool true, iff the raw parent id equals 0
     */
    private function isQuestion(array $parsedDataRow) : bool {
        $parentRefId = $parsedDataRow[self::EXPECTED_HEADERS[2]];

        return $parentRefId === "0";
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