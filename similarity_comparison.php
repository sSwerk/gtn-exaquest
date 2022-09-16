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
//\core\notification::add($testMessage, \core\output\notification::NOTIFY_SUCCESS);
//\core\notification::fetch();

/** *********************************
 *  **  Preparation                **
 ** *********************************/

// fill with user input, see /admin/settings.php?section=blocksettingexaquest
$similarityComparisonSettings =
        [
            "algorithm" => get_config("block_exaquest", "config_similarity_algorithm") ?: JaroWinklerStrategy::class, // required
            "threshold" => (float) get_config("block_exaquest", "config_similarity_threshold") ?: 0.8, // required
            "nrOfThreads" => (int) get_config("block_exaquest", "config_similarity_nrofthreads") ?: 1, // optional
            "jwMinPrefixLength" => (int) get_config("block_exaquest", "config_similarity_jwminprefixlength") ?: 4, // optional, JaroWinkler parameter
            "jwPrefixScale" => (float) get_config("block_exaquest", "config_similarity_jwprefixscale") ?: 0.1, // optional, JaroWinkler parameter
            "swgMatchValue" => (float) get_config("block_exaquest", "config_similarity_swgmatchmalue") ?: 1.0, // optional, SmithWatermanGotoh parameter
            "swgMismatchValue" => (float) get_config("block_exaquest", "config_similarity_swgmismatchvalue") ?: -2.0, // optional, SmithWatermanGotoh parameter
            "swgGapValue" => (float) get_config("block_exaquest", "config_similarity_swggapvalue") ?: -0.5 // optional, SmithWatermanGotoh parameter
        ];

$similarityComparisonSettings = verify_settings($similarityComparisonSettings);

$courseID = required_param('courseid', PARAM_INT);
$action = optional_param('action', 'default', PARAM_ALPHANUMEXT);
$sortBy = optional_param('sort', 'similarityDesc', PARAM_ALPHANUMEXT);
$substituteIDs = optional_param('substituteid', false, PARAM_BOOL);
$hidePreviousQ = optional_param('hidepreviousq', false, PARAM_BOOL);
require_login($courseID);
[$thispageurl, $contexts, $cmid, $cm, $module, $pagevars] = question_edit_setup('questions', '/question/edit.php');
$url = new moodle_url('/blocks/exaquest/similarity_comparison.php', array('courseid' => $courseID));
$PAGE->set_url($url);
$PAGE->set_heading(get_string('exaquest:similarity_title', 'block_exaquest'));
$PAGE->set_title(get_string('exaquest:similarity_title', 'block_exaquest'));
//$PAGE->requires->js_call_amd('block_exaquest/helloworld', 'init', [['courseid' => $courseID, 'sortby' => $sortBy]]); // include javascript within ./amd/src/

$mform = new similarity_comparison_form($url, ["courseid" => $courseID, "sort" => $sortBy, "substituteid" => $substituteIDs, "hidepreviousq" => $hidePreviousQ]); // button array
$action = evaluateSimiliarityComparisonFormAction($mform);
$sortBy = evaluateSimiliarityComparisonFormOption($mform, 'sort', $sortBy, similarity_comparison_form::$sortBy);
$substituteIDs = evaluateSimiliarityComparisonFormCheckbox($mform, 'substituteid'); // form checkbox for ID substitution, converts to bool
$hidePreviousQ = evaluateSimiliarityComparisonFormCheckbox($mform, 'hidepreviousq'); // form checkbox for hiding older versions, converts to bool

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

$similarityStatistics = calculateStatistics($moodleQuestions, $allSimilarityRecordWrappers);

/** *********************************
 *  **  Output rendering           **
 ** *********************************/
Logger::debug("block_exaquest_similarity_comparison - starting output rendering");
echo $output->header($courseContext, $courseID, get_string('dashboard', 'block_exaquest'));

handleSimilarityComparisonForm($mform);

// render tabled based on action passed
switch($action) {
    case 'showSimilarityComparison':
    case 'default':
    default:
        renderSimilarityComparison($output, $moodleQuestions, $courseID, $allSimilarityRecordWrappers, $similarityStatistics,
                $sortBy, $substituteIDs, $hidePreviousQ);
        echo $output->footer();
        break;
}

// #################
// ### Functions ###
// #################

// Output/rendering related functions

/**
 * @param similarity_comparison_form $mform
 * @return string the action that the user wants to perform
 * @throws moodle_exception
 */
function evaluateSimiliarityComparisonFormAction(similarity_comparison_form $mform): string {
    $action = "default";

    if ($mdata = $mform->get_data()) { // contains all relevant form data/fields that were set by the user
        require_sesskey();
        //$redirectUrl = new moodle_url('/blocks/exaquest/similarity_comparison.php', array('courseid' => $courseID));

        if (isset($mdata->showSimilarityOverviewButton)) {
            //$redirectUrl->param($paramname, 'showSimilarityComparison');
            $action = 'showSimilarityComparison';
            //redirect($redirectUrl); // TODO: do we have to redirect? it loses the courseid GET param after form submission, but seems to work nevertheless?
        } else if (isset($mdata->computeSimilarityButton)) {
            //$redirectUrl->param($paramname, 'computeSimilarity');
            $action = 'computeSimilarity';
        } else if (isset($mdata->computeSimilarityStoreButton)) {
            //$redirectUrl->param($paramname, 'computeSimilarityStore');
            $action = 'computeSimilarityStore';
        }

        // TODO: do we have to redirect? it loses the courseid GET param after form submission, but seems to work nevertheless?
        //redirect($redirectUrl);
    }
    return $action;
}

/**
 * @param similarity_comparison_form $mform
 * @param string $checkboxID
 * @return bool
 */
function evaluateSimiliarityComparisonFormCheckbox(similarity_comparison_form $mform, string $checkboxID): bool {
    $checkboxVal = $mform->optional_param($checkboxID, false, PARAM_BOOL);

    if ($mdata = $mform->get_data()) { // contains all relevant form data/fields that were set by the user
        require_sesskey();
        $mdata = get_object_vars($mdata);
        if (isset($mdata[$checkboxID])) {
            $checkboxVal = $mdata[$checkboxID] == 1;
        }
    }

    return $checkboxVal;
}

/**
 * @param similarity_comparison_form $mform
 * @param string $paramName
 * @param string $defaultValue
 * @param array $options
 * @return string the action that the user wants to perform
 */
function evaluateSimiliarityComparisonFormOption(similarity_comparison_form $mform, string $paramName, string $defaultValue, array $options): string {
    $value = $defaultValue;

    if ($mdata = $mform->get_data()) { // contains all relevant form data/fields that were set by the user
        require_sesskey();
        $mdataArr = get_object_vars($mdata);
        if (isset($mdataArr[$paramName])) {
            $idx = $mdataArr[$paramName]; // is an index
            $value = $options[$idx];
        }
    }
    return $value; // must not return an index
}

/**
 * Displays/Renders the similarity comparison form
 * May be used to set default values that the user sees initially
 *
 * @param similarity_comparison_form $mform
 * @return void
 * @throws moodle_exception
 */
function handleSimilarityComparisonForm(similarity_comparison_form $mform): void {
    Logger::debug("block_exaquest_similarity_comparison - displaying similarity form options and buttons");
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
 * @param $questions
 * @param $courseID
 * @param array $allSimilarityRecordArr
 * @param array $statisticsArr
 * @param string $sortBy key for sorting TODO list possible values
 * @param bool $substituteIDs
 * @param bool $hidePreviousQ
 * @return void
 * @throws coding_exception
 */
function renderSimilarityComparison(renderer_base $output, $questions, $courseID, array $allSimilarityRecordArr, array $statisticsArr,
                                    string $sortBy="similarityDesc", bool $substituteIDs=false, bool $hidePreviousQ=false): void {
    Logger::debug("block_exaquest_similarity_comparison - rendering mustache template compare_questions",["courseid" => $courseID,
            "sortby" => $sortBy, "substituteid" => json_encode($substituteIDs), "hidepreviousq" => json_encode($hidePreviousQ)]);
    // Instantiate mustache companion class
    $dashboard = new \block_exaquest\output\compare_questions($questions, $courseID, $allSimilarityRecordArr, $statisticsArr,
            $sortBy, $substituteIDs, $hidePreviousQ);
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
 * @param string $value
 * @return string
 */
function validateInput(string $param, string $value) : string {
    $allowedActions = ["default", "computeSimilarity", "computeSimilarityStore", "showSimilarityComparison"];
    $allowedSort = ["default", "similarityDesc", "similarityAsc"];

    switch($param) {
        case 'action':
            $idx = array_search($value, $allowedActions, false);
            if($idx) {
                return $allowedActions[$idx];
            }
            Logger::debug("block_exaquest_similarity_comparison - unknown action: ", ["action" =>$value, "knownActions" => $allowedActions]);
            break;
        case 'sort':
            $idx = array_search($value, $allowedSort, false);
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

function calculateStatistics(array $moodleQuestions, array $allSimilarityRecordWrappers) : array {
    $statistics = array();

    $totalNrOfQuestions = count($moodleQuestions);
    $moodleQuestionsLatestVersion = filterLatestVersion($moodleQuestions);
    $totalNrOfQuestionsLatest = count($moodleQuestionsLatestVersion);

    $allSimilarQuestions = filterQuestions($moodleQuestions, $allSimilarityRecordWrappers, true);
    $allSimilarQuestionsCount = count($allSimilarQuestions);
    $allDissimilarQuestions = array_diff_key($moodleQuestions, $allSimilarQuestions);
    $allDissimilarQuestionsCount = count($allDissimilarQuestions);

    // filter by similar latest version questions
    $allSimilarQuestionsLatest = filterQuestions($moodleQuestionsLatestVersion, $allSimilarityRecordWrappers, true);
    $allDissimilarQuestionsLatest = array_diff_key($moodleQuestionsLatestVersion, $allSimilarQuestionsLatest);
    $moodleQuestionsSimilarLatestVersion = array_intersect_key($moodleQuestionsLatestVersion, $allSimilarQuestionsLatest);
    $similarLatestVersionCount = count($moodleQuestionsSimilarLatestVersion);
    $moodleQuestionsDissimilarLatestVersion = array_intersect_key($moodleQuestionsLatestVersion, $allDissimilarQuestionsLatest);
    $dissimilarLatestVersionCount = count($moodleQuestionsDissimilarLatestVersion);

    $ratioSimilar = $allSimilarQuestionsCount / ($totalNrOfQuestions ?: 1);
    $ratioDissimilar = $allDissimilarQuestionsCount / ($totalNrOfQuestions ?: 1);
    $ratioSimilarLatestVersion = $similarLatestVersionCount / ($totalNrOfQuestionsLatest ?: 1);
    $ratioDissimilarLatestVersion = $dissimilarLatestVersionCount / ($totalNrOfQuestionsLatest ?: 1);

    $statistics["totalQCount"] = $totalNrOfQuestions;
    $statistics["totalLatestQCount"] = $totalNrOfQuestionsLatest;
    $statistics["totalSimilarQ"] = $allSimilarQuestionsCount;
    $statistics["totalDissimilarQ"] = $allDissimilarQuestionsCount;
    $statistics["totalLatestSimilarQ"] = $similarLatestVersionCount;
    $statistics["totalLatestDissimilarQ"] = $dissimilarLatestVersionCount;
    $statistics["ratioSimilarQ"] = $ratioSimilar;
    $statistics["ratioDissimilarQ"] = $ratioDissimilar;
    $statistics["ratioLatestSimilarQ"] = $ratioSimilarLatestVersion;
    $statistics["ratioLatestDissimilarQ"] = $ratioDissimilarLatestVersion;
    return $statistics;
}

/**
 * @param array $moodleQuestions
 * @param array $allSimilarityRecordWrappers
 * @param bool $similar if true, returns only similar questions, if false, returns only dissimilar questions
 * @return array
 */
function filterQuestions(array $moodleQuestions, array $allSimilarityRecordWrappers, bool $similar): array {
    $moodleQuestionsFiltered = array();
    foreach ($allSimilarityRecordWrappers as $qr) {
        if((int) $qr->is_similar === (int) $similar
                && array_key_exists($qr->question_id1, $moodleQuestions) && array_key_exists($qr->question_id2, $moodleQuestions)) {
            $moodleQuestionsFiltered[$qr->question_id1] = $moodleQuestions[$qr->question_id1];
            $moodleQuestionsFiltered[$qr->question_id2] = $moodleQuestions[$qr->question_id2];
        }
    }

    return $moodleQuestionsFiltered;
}

/**
 * @param array $moodleQuestions
 * @return array
 */
function filterLatestVersion(array $moodleQuestions): array {
    $moodleQuestionsLatestVersion = array();
    foreach ($moodleQuestions as $q) {
        if (is_latest($q->version, $q->questionbankentryid)) {
            $moodleQuestionsLatestVersion[$q->id] = $q;
        }
    }

    return $moodleQuestionsLatestVersion;
}

/**
 * Semantic verification of the user supplied configuration settings
 * @param array $similarityComparisonSettings
 * @return array, the potentially adjusted (default values in case of invalid settings) comparison settings
 */
function verify_settings(array $similarityComparisonSettings) : array {
    Logger::info("block_exaquest_similarity_comparison - verifying user settings"); // will log to stderr on webserver

    $algorithm = $similarityComparisonSettings["algorithm"];
    $threshold = $similarityComparisonSettings["threshold"];
    $nrOfThreads = $similarityComparisonSettings["nrOfThreads"];
    $jwminprefixlength = $similarityComparisonSettings["jwMinPrefixLength"];
    $jwprefixscale = $similarityComparisonSettings["jwPrefixScale"];
    $swgmatchvalue = $similarityComparisonSettings["swgMatchValue"];
    $swgmismatchvalue = $similarityComparisonSettings["swgMismatchValue"];
    $swggapvalue = $similarityComparisonSettings["swgGapValue"];

    if(!isset($algorithm) || ($algorithm != JaroWinklerStrategy::class && $algorithm != SmithWatermanGotohStrategy::class)) {
        Logger::warning("block_exaquest_similarity_comparison - setting algorithm invalid or missing, using default");
        $similarityComparisonSettings["algorithm"] = JaroWinklerStrategy::class;
    }
    Logger::debug("block_exaquest_similarity_comparison - setting algorithm to " . $similarityComparisonSettings["algorithm"] );

    if(!isset($threshold) || $threshold < 0.0 || $threshold > 1.0) {
        Logger::warning("block_exaquest_similarity_comparison - setting threshold invalid or missing, using default");
        $similarityComparisonSettings["threshold"] = 0.8;
    }
    Logger::debug("block_exaquest_similarity_comparison - setting threshold to " . $similarityComparisonSettings["threshold"]);

    if(!isset($nrOfThreads) || $nrOfThreads < 1 || $nrOfThreads > 1000) {
        Logger::warning("block_exaquest_similarity_comparison - setting nrofthreads invalid or missing, using default");
        $similarityComparisonSettings["nrOfThreads"] = 1;
    }
    Logger::debug("block_exaquest_similarity_comparison - setting nrofthreads to " . $similarityComparisonSettings["nrOfThreads"]);

    if(!isset($jwminprefixlength) || $jwminprefixlength < 2) {
        Logger::warning("block_exaquest_similarity_comparison - setting jwminprefixlength invalid or missing, using default");
        $similarityComparisonSettings["jwMinPrefixLength"] = 4;
    }
    Logger::debug("block_exaquest_similarity_comparison - setting jwMinPrefixLength to " . $similarityComparisonSettings["jwMinPrefixLength"]);

    if(!isset($jwprefixscale) || $jwprefixscale < 0.0 || $jwprefixscale > (1/$similarityComparisonSettings["jwMinPrefixLength"])) {
        Logger::warning("block_exaquest_similarity_comparison - setting jwprefixscale invalid or missing, using default");
        $similarityComparisonSettings["jwPrefixScale"] = 0.1;
    }
    Logger::debug("block_exaquest_similarity_comparison - setting jwprefixscale to " . $similarityComparisonSettings["jwPrefixScale"]);

    if(!isset($swgmatchvalue) || $swgmatchvalue < 0.0) {
        Logger::warning("block_exaquest_similarity_comparison - setting swgmatchvalue invalid or missing, using default");
        $similarityComparisonSettings["swgMatchValue"] = 1.0;
    }
    Logger::debug("block_exaquest_similarity_comparison - setting swgMatchValue to " . $similarityComparisonSettings["swgMatchValue"]);

    if(!isset($swgmismatchvalue) || $swgmismatchvalue >= $similarityComparisonSettings["swgMatchValue"]) {
        Logger::warning("block_exaquest_similarity_comparison - setting swgmismatchvalue invalid or missing, using default");
        $similarityComparisonSettings["swgMismatchValue"] = -2.0;
    }
    Logger::debug("block_exaquest_similarity_comparison - setting swgMismatchValue to " . $similarityComparisonSettings["swgMismatchValue"]);

    if(!isset($swggapvalue) || $swggapvalue > 0) {
        Logger::warning("block_exaquest_similarity_comparison - setting swggapvalue invalid or missing, using default");
        $similarityComparisonSettings["swgGapValue"] = -0.5;
    }
    Logger::debug("block_exaquest_similarity_comparison - setting swgGapValue to " . $similarityComparisonSettings["swgGapValue"]);

    return $similarityComparisonSettings;
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