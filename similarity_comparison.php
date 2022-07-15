<?php
/*
 * Exaquest similarity comparison extension
 *
 * @package    block_exaquest
 * @copyright  2022 Stefan Swerk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * =========================================
 * = INSTALLATION of required dependencies =
 * =========================================
 * Either run the installation script ./similarity_comparison_INSTALL.sh, or
 *  1. create directory similarity_comparison
 *  2. download a specific version/zip package from https://gitea.swerk.priv.at/stefan/-/packages/composer/stefan%2Fgtn_jku_similarity_comparison/0.0.2
 *  3. extract contents of zip archive into similarity_comparison
 *  4. run composer update
 */
const DB_BLOCK_EXAQUEST_SIMILARITY = 'block_exaquest_similarity';
require __DIR__ . '/inc.php';

global $DB, $CFG, $COURSE, $PAGE, $OUTPUT, $USER;

require_once($CFG->dirroot . '/question/editlib.php');
require_once(__DIR__ . '/questionbank_extensions/exaquest_view.php');
require_once(__DIR__ . '/similarity_comparison/vendor/autoload.php'); // load similarity comparison library
require_once(__DIR__ . '/classes/form/similarity_comparison_form.php');

use GTN\comparator\QuestionOnlyComparator;
use GTN\import\MoodleImporter;
use GTN\Logger;
use GTN\strategy\ComparisonStrategyFactory;
use GTN\strategy\editbased\JaroWinklerStrategy;
use GTN\strategy\editbased\SmithWatermanGotohStrategy;

$testMessage = MoodleImporter::test(); // quick and dirty test whether autoload was successful
\core\notification::add($testMessage, \core\output\notification::NOTIFY_SUCCESS);

/** *********************************
 *  **  Preparation                **
 ** *********************************/

// TODO: fill with user input
$similarityComparisonSettings =
        [
            "algorithm" => JaroWinklerStrategy::class, // required
            "threshold" => 0.8, // required
            "nrOfThreads" => 1, // optional
            "jwMinPrefixLength" => 4, // optional, JaroWinkler parameter
            "jwPrefixScale" => 0.1, // optional, JaroWinkler parameter
            "swgMatchValue" => 1.0, // optional, SmithWatermanGotoh parameter
            "swgMismatchValue" => -2.0, // optional, SmithWatermanGotoh parameter
            "swgGapValue" => -0.5 // optional, SmithWatermanGotoh parameter
        ];

$courseID = required_param('courseid', PARAM_INT);
$action = optional_param('action', 'default', PARAM_ALPHANUMEXT);
$sortBy = optional_param('sort', 'similarity', PARAM_ALPHANUMEXT);
require_login($courseID);
[$thispageurl, $contexts, $cmid, $cm, $module, $pagevars] = question_edit_setup('questions', '/question/edit.php');
$url = new moodle_url('/blocks/exaquest/similarity_comparison.php', array('courseid' => $courseID));
$PAGE->set_url($url);
$PAGE->set_heading(get_string('exaquest:similarity_title', 'block_exaquest'));
$PAGE->set_title(get_string('exaquest:similarity_title', 'block_exaquest'));

$mform = new similaritycomparison_form($url, ["courseid" => $courseID]); // button array
$action = evaluateSimiliarityComparisonForm($mform, $courseID, $action);

block_exaquest_init_js_css();
$output = $PAGE->get_renderer('block_exaquest');

Logger::debug("block_exaquest_similarity_comparison - parsing and validating params"); // will log to stderr on webserver
$action = validateInput('action', $action);
$sortBy = validateInput('sort', $sortBy);
Logger::debug("block_exaquest_similarity_comparison - passed params: ", ["courseid" => $courseID, "action" => $action, "sort" => $sortBy]);

$courseContext = context_course::instance($courseID); // currently optional
$course = get_course($courseID); // currently optional
$allCourseCategories = core_course_category::get_all($options = ["returnhidden"]); // retrieve all course categories, currently optional

$recurse = $pagevars["recurse"];
$categoryList = getQuestionCategories($pagevars, $recurse); // retrieve sub categories if recurse is set
$moodleQuestions = loadMoodleQuestions($categoryList); // core Moodle DB question records (arr of stdClass objects)
Logger::debug("block_exaquest_similarity_comparison - question course context: ", ["qCategories" => $categoryList, "nrOfMoodleQuestions" => count($moodleQuestions)]);

// switch page functionality/content, based on user input
$allSimilarityRecordWrappers = match ($action) {
    'computeSimilarity' => computeSimilarityComparisonTableData($moodleQuestions, $similarityComparisonSettings), // compute Similarity and display, but do not store in DB
    'computeSimilarityStore' => computeSimilarityComparisonTableData($moodleQuestions, $similarityComparisonSettings, $DB), // compute and store results in DB
    default => getSimilarityRecordsWithID($DB, array_column($moodleQuestions, 'id')), // show existing DB results only
};


/** *********************************
 *  **  Output rendering           **
 ** *********************************/
echo $output->header($courseContext, $courseID, get_string('dashboard', 'block_exaquest'));

handleSimilarityComparisonForm($mform);

// render tabled based on action passed
switch($action) {
    case 'showSimilarityComparison':
    case 'default':
    default:
        renderSimilarityComparison($output, $USER, $courseID, $allSimilarityRecordWrappers, $sortBy);
        echo $output->footer();
        break;
}

// #################
// ### Functions ###
// #################

// Output/rendering related functions

/**
 * @param similaritycomparison_form $mform
 * @param mixed $courseID
 * @param string $action
 * @return string the action that the user wants to perform
 * @throws moodle_exception
 */
function evaluateSimiliarityComparisonForm(similaritycomparison_form $mform, mixed $courseID, string $action): string {
    if ($mdata = $mform->get_data()) { // contains all relevant form data/fields that were set by the user
        require_sesskey();
        $redirectUrl = new moodle_url('/blocks/exaquest/similarity_comparison.php', array('courseid' => $courseID));

        if (isset($mdata->showSimilarityOverviewButton)) {
            $redirectUrl->param('action', 'showSimilarityComparison');
            $action = 'showSimilarityComparison';
            //redirect($redirectUrl); // TODO: do we have to redirect? it loses the courseid GET param after form submission, but seems to work nevertheless?
        } else if (isset($mdata->computeSimilarityButton)) {
            $redirectUrl->param('action', 'computeSimilarity');
            $action = 'computeSimilarity';
        } else if (isset($mdata->computeSimilarityStoreButton)) {
            $redirectUrl->param('action', 'computeSimilarityStore');
            $action = 'computeSimilarityStore';
        }

        // TODO: do we have to redirect? it loses the courseid GET param after form submission, but seems to work nevertheless?
        //redirect($redirectUrl);
    }
    return $action;
}

/**
 * Displays/Renders the similarity comparison form
 * May be used to set default values that the user sees initially
 *
 * @param similaritycomparison_form $mform
 * @return void
 * @throws moodle_exception
 */
function handleSimilarityComparisonForm(similaritycomparison_form $mform) {
    if($mform->is_cancelled()) {

    } else if($fromform = $mform->get_data()) {
        $mdata = $fromform;
        //$url1 = new moodle_url('/blocks/exaquest/similarity_comparison.php', array('courseid' => $courseID));
        //redirect($url1);
        //$PAGE->set_url($url1);
        $mform->display();
    } else {
        $mform->set_data(array("test" => "123")); // default values
        $mform->display();
    }
}

/**
 * Renders a Table view of the given similarity records
 *
 * @param renderer_base $output
 * @param $USER
 * @param $courseID
 * @param array $allSimilarityRecordArr
 * @param string $sortBy key for sorting TODO list possible values
 * @return void
 * @throws coding_exception
 */
function renderSimilarityComparison(renderer_base $output, $USER, $courseID, array $allSimilarityRecordArr, string $sortBy="similarityDesc") {
    // Instantiate mustache companion class
    $dashboard = new \block_exaquest\output\compare_questions($USER, $courseID, $allSimilarityRecordArr, $sortBy);
    // Render HTML output
    echo $output->render($dashboard);
}

function getComparator(array $similarityComparisonSettings) : QuestionOnlyComparator {
    $threshold = $similarityComparisonSettings['threshold'] ?? 0.8;
    $nrOfThreads = $similarityComparisonSettings['nrOfTreads'] ?? 1;
    $multihreadingEnabled = $nrOfThreads > 1;

    switch($similarityComparisonSettings['algorithm']) {
        case SmithWatermanGotohStrategy::class:
            $matchValue = $similarityComparisonSettings['swgMatchValue'] ?? 1.0;
            $mismatchValue = $similarityComparisonSettings['swgMismatchValue'] ?? -2.0;
            $gapValue = $similarityComparisonSettings['swgGapValue'] ?? -0.5;
            $strategy = ComparisonStrategyFactory::createSmithWatermanGotohStrategy($matchValue, $mismatchValue, $gapValue);
            break;
        case JaroWinklerStrategy::class:
        default:
            $minPrefix = $similarityComparisonSettings['jwMinPrefixLength'] ?? 4;
            $prefixScale = $similarityComparisonSettings['jwPrefixScale'] ?? 0.1;
            $strategy = ComparisonStrategyFactory::createJaroWinklerStrategy($minPrefix, $prefixScale);
            break;
    }

    return new QuestionOnlyComparator($strategy, $threshold, $multihreadingEnabled, $nrOfThreads);
}

// ##########################
// Import related functions
// ##########################

function importQuestions(array $questions) : array {
    $moodle_importer = getMoodleImporterInstance();

    if(empty($questions)) {
        Logger::error("similarity_comparison: Unable to import questions, passed array is invalid or empty");
        return array();
    }

    $moodle_importer->importAll($questions);

    return $moodle_importer->getKnownQuestionRowEntites();
}

function getMoodleImporterInstance() : MoodleImporter {
    return new MoodleImporter();
}

/**
 * Returns either the currently selected question category id, optionally including any sub category ids
 * @param array $pagevars
 * @param bool $recurse
 * @return array[]
 * @throws coding_exception
 */
function getQuestionCategories(array $pagevars, bool $recurse=false) : array {
    $qCatID = question_get_category_id_from_pagevars($pagevars); // retrieve question category id, is different from course cat

    if($recurse) {
        return question_categorylist($qCatID); // retrieve sub categories as well
    }

    return array($qCatID);
}

/**
 * Validates passed parameters
 * TODO: allowed values
 *
 * @param string $param
 * @param mixed $value
 * @return string
 */
function validateInput(string $param, mixed $value) : string {
    $allowedActions = ["default", "computeSimilarity", "computeSimilarityStore", "showSimilarityComparison"];
    $allowedSort = ["default", "similarityDesc", "similarityAsc"];

    switch($param) {
        case 'action':
            $idx = array_search($value, $allowedActions, true);
            if($idx) {
                return $allowedActions[$idx];
            }
            Logger::debug("block_exaquest_similarity_comparison - unknown action: ", ["action" =>$value, "knownActions" => $allowedActions]);
            break;
        case 'sort':
            $idx = array_search($value, $allowedSort, true);
            if($idx) {
                return $allowedSort[$idx];
            }
            Logger::debug("block_exaquest_similarity_comparison - unknown sort: ", ["sort" =>$value, "knownSort" => $allowedSort]);
            break;
        default:
            Logger::debug("block_exaquest_similarity_comparison - unknown param: ", ["param" =>$param]);
            break;
    }

    return "default";
}

/** *********************************
 *  **  Similarity Computation     **
 ** *********************************/

/**
 * @param array $moodleQuestions Moodle Core Question DB Records
 * @param moodle_database|null $DB if null => will not store results into DB table
 * @return array Wrapper array of stdClass objects, that can be used for rendering the view
 * @throws dml_exception
 */
function computeSimilarityComparisonTableData(array $moodleQuestions, array $comparatorSettings, ?moodle_database $DB=null) : array {
    Logger::debug("block_exaquest_similarity_comparison - computing similarity");

    $questionRowEntities = importQuestions($moodleQuestions);
    $comparator = getComparator($comparatorSettings);
    $comparator->addQuestionRowEntities($questionRowEntities); // performs computations

    $allDataRecords = createAllSimilarityRecordObjectWrapperArr($comparator);

    if(isset($DB)) { // store results in database table block_exaquest_similarity
        Logger::debug("block_exaquest_similarity_comparison - updating database table with results");
        foreach($allDataRecords as $dataRecord) {
            // TODO overwrite existing toggle?
            storeSimilarityComparisonResult($DB, $dataRecord);
        }
    }
    return $allDataRecords;
}

/**
 * Creates an array of stdClass objects that may be used for processing all computation results.
 * It will not return a complete 2D matrix in order to avoid duplicates
 *
 * @param QuestionOnlyComparator $comparator
 * @return array
 * @throws Exception
 */
function createAllSimilarityRecordObjectWrapperArr(QuestionOnlyComparator $comparator) : array {
    $allSimilarityRecordWrappers = array();
    $questionList = $comparator->getQuestionList();

    $from = 0;
    $jMax = $to = count($questionList);
    for($i=$from;$i<$to;$i++) {
        $firstQuestion = $questionList[$i];
        $firstID = $firstQuestion->getId();

        for ($j = $i; $j < $jMax; $j++) { // calculate only upper part (half of matrix)
            $secondQuestion = $questionList[$j];
            $secondID = $secondQuestion->getId();

            if($firstID !== $secondID) { // do not consider identity
                $allSimilarityRecordWrappers[] = createSimilarityRecordObjectWrapper($firstID, $secondID, $comparator);
            }
        }
    }

    return $allSimilarityRecordWrappers;
}

/**
 * Creates an stdClass object that represents the computation result, it contains:
 * question_id1, question_id2, is_similar, similarity, timestamp_calculation, threshold, algorithm.
 * TODO: add type constraints
 *
 * The caller has to ensure Database table consistency/uniqueness.
 *
 * @param int $questionID1
 * @param int $questionID2
 * @param QuestionOnlyComparator $comparator
 * @return stdClass|null
 * @throws Exception
 */
function createSimilarityRecordObjectWrapper(int $questionID1, int $questionID2, QuestionOnlyComparator $comparator) : ?stdClass {
    $newSimilarityRecord = new stdClass();
    $strategy = $comparator->getComparisonStrategy();
    $distanceMatrix = $comparator->getDistanceMatrix();
    $distanceThresholdMatrix = $comparator->getDistanceThresholdMatrix();
    $timestamp_calculation = new DateTime("now", core_date::get_server_timezone_object());

    if(!array_key_exists($questionID1, $distanceMatrix) || !array_key_exists($questionID2, $distanceMatrix[$questionID1])) {
        Logger::warning("Unable to store non existing similarity comparison result - has it been computed yet?",
                ["qID1" => $questionID1, "qID2" => $questionID2]);
        return null;
    }

    $newSimilarityRecord->question_id1 = $questionID1;
    $newSimilarityRecord->question_id2 = $questionID2;
    $newSimilarityRecord->is_similar = $distanceThresholdMatrix[$questionID1][$questionID2] ? 1 : 0;
    $newSimilarityRecord->similarity = $distanceMatrix[$questionID1][$questionID2];
    $newSimilarityRecord->timestamp_calculation = $timestamp_calculation->getTimestamp();
    $newSimilarityRecord->threshold = $comparator->getDistanceThreshold();
    $newSimilarityRecord->algorithm = get_class($strategy);

    return $newSimilarityRecord;
}

/** *********************************
 *  **  Database                   **
 ** *********************************/

/**
 * Load DB records from the Moodle Core Question Table
 * @param array $categoryList
 * @return array
 */
function loadMoodleQuestions(array $categoryList) : array {
    $qFinder = question_finder::get_instance(); // question_bank finder instance
    $qList = $qFinder?->get_questions_from_categories($categoryList, null); // retrieve all question IDs from the categories
    return question_load_questions($qList); // load the questions, array of stdclass objects
}

/**
 * Store a single computation as a record into the table DB_BLOCK_EXAQUEST_SIMILARITY
 * @param moodle_database $DB
 * @param stdClass $similarityRecordObjectWrapper
 * @param bool $overwriteExisting false -> do not modify existing records
 * @return bool false, iff $overwriteExisting is false and there is a record with matching foreign keys question_id1 and question_id2
 * @throws dml_exception
 */
function storeSimilarityComparisonResult(moodle_database $DB, stdClass $similarityRecordObjectWrapper, bool $overwriteExisting=true) : bool {
    $qID1 = $similarityRecordObjectWrapper->question_id1;
    $qID2 = $similarityRecordObjectWrapper->question_id2;

    $isExisting = existsSimilarityRecord($DB, $qID1, $qID2);
    if(!$overwriteExisting && $isExisting) {
        Logger::warning("Unable to store similarity record - There is already an existing record with the given IDs,"
                ." and overwrite toggle has been disabled", ["qID1" => $qID1, "qID2" => $qID2]);
        return false;
    }

    if($isExisting) {
        $existingRecord = getSimilarityRecord($DB, $qID1, $qID2);
        $newID = $existingRecord->id;
        $similarityRecordObjectWrapper->id = $newID;
        $DB->update_record( DB_BLOCK_EXAQUEST_SIMILARITY, $similarityRecordObjectWrapper);
    } else {
        $newID = $DB->insert_record(DB_BLOCK_EXAQUEST_SIMILARITY, $similarityRecordObjectWrapper, true);
    }

    Logger::debug("New/updated similarity database record stored: ", ["ID" => $newID, "similarityRecord" => $similarityRecordObjectWrapper]);
    return true;
}

/**
 * This method also swaps foreign keys qID1 with qID2, in order to avoid duplicate rows
 * @param moodle_database $DB
 * @param int $qID1
 * @param int $qID2
 * @return bool true, iff there is an existing record with matching foreign keys
 * @throws dml_exception
 */
function existsSimilarityRecord(moodle_database $DB, int $qID1, int $qID2) : bool {
    $exists = $DB->record_exists(DB_BLOCK_EXAQUEST_SIMILARITY, ['question_id1' => $qID1, 'question_id2' => $qID2]);
    // check reverse IDs as well
    return $exists || $DB->record_exists(DB_BLOCK_EXAQUEST_SIMILARITY, ['question_id1' => $qID2, 'question_id2' => $qID1]);
}

/**
 * This method also swaps foreign keys qID1 with qID2, in order to avoid duplicate rows
 * @param moodle_database $DB
 * @param int $qID1
 * @param int $qID2
 * @return stdClass|bool false, if there is no such record, stdClass record otherwise
 * @throws dml_exception
 */
function getSimilarityRecord(moodle_database $DB, int $qID1, int $qID2) : stdClass|bool {
    $similarityRecord = $DB->get_record(DB_BLOCK_EXAQUEST_SIMILARITY, ['question_id1'  => $qID1, 'question_id2'  => $qID2], '*', IGNORE_MISSING);
    // check reverse mapping as well, in case it does not exist
    if(!$similarityRecord) {
        $similarityRecord = $DB->get_record(DB_BLOCK_EXAQUEST_SIMILARITY, ['question_id1'  => $qID2, 'question_id2'  => $qID1], '*',IGNORE_MISSING);
    }

    return $similarityRecord;
}

/**
 * Retrieves all records in the DB table, does not check for duplicates/consistency
 * @param moodle_database $DB
 * @return array
 * @throws dml_exception
 */
function getAllSimilarityRecords(moodle_database $DB) : array {
    return $DB->get_records(DB_BLOCK_EXAQUEST_SIMILARITY);
}

/**
 * Includes only those records, whose question_id1 and question_id2 are both present in the given idArr
 *
 * @param moodle_database $DB
 * @param array $questionIDArr an array of integer IDs corresponding to moodle core question IDs
 * @return array
 * @throws dml_exception
 */
function getSimilarityRecordsWithID(moodle_database $DB, array $questionIDArr) : array {

    $allRecords = getAllSimilarityRecords($DB);
    $filteredRecords = array();
    foreach($allRecords as $similarityRecord) {
        if(in_array($similarityRecord->question_id1, $questionIDArr, false)
                && in_array($similarityRecord->question_id2, $questionIDArr, false)) {
            $filteredRecords[] = $similarityRecord;
        }
    }
    return $filteredRecords;

    /** TODO use a proper select SQL statement for performance $queryparams
    $queryparams = array();
    [$sql, $params] = $DB->get_in_or_equal($questionIDArr, SQL_PARAMS_NAMED);
    $queryparams += $params;
    $rt = $DB->get_records_select('block_exaquest_similarity',
    //'userid = :userid AND criteriaid = :criteriaid AND valueid '.$sql,
    'question_id1 ' . $sql . ' AND question_id2 ' . $sql,
    $queryparams);
     **/
}

function getSimilarityRecordsWithColumnId(moodle_database $DB, bool $toggleColumn, array $questionIDArr) : array{
    // get records with specific question ids
    $columnName = $toggleColumn ? 'question_id2' : 'question_id1';
    //$queryparams = ['userid' => 123, 'criteriaid' => 223];
    $queryparams = [];
    // Values for valueid.
    [$sql, $params] = $DB->get_in_or_equal($questionIDArr, SQL_PARAMS_NAMED);
    $queryparams += $params;
    $rt = $DB->get_records_select(DB_BLOCK_EXAQUEST_SIMILARITY,
            //'userid = :userid AND criteriaid = :criteriaid AND valueid '.$sql,
            $columnName . ' ' . $sql,
            $queryparams);

    return $rt;
}