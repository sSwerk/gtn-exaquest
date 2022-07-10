<?php
require __DIR__ . '/inc.php';

global $DB, $CFG, $COURSE, $PAGE, $OUTPUT, $USER;

require_once($CFG->dirroot . '/question/editlib.php');
require_once(__DIR__ . '/questionbank_extensions/exaquest_view.php');

require_once(__DIR__ . '/similarity_comparison/vendor/autoload.php'); // load similarity comparison library

use GTN\import\MoodleImporter;
use GTN\Logger;
$testMessage = MoodleImporter::test(); // quick and dirty test whether autoload was successful
\core\notification::add($testMessage, \core\output\notification::NOTIFY_SUCCESS);
Logger::info("TEST123"); // will log to stderr on webserver

$courseID = required_param('courseid', PARAM_INT);
$action = optional_param('action', 'default', PARAM_TEXT);
require_login($courseID);
[$thispageurl, $contexts, $cmid, $cm, $module, $pagevars] = question_edit_setup('questions', '/question/edit.php');
//$course = $DB->get_record('course', array('id' => $courseid));
$courseContext = context_course::instance($courseID);

$course = get_course($courseID);
$allCourseCategories = core_course_category::get_all($options = ["returnhidden"]); // retrieve all course categories

$recurse = $pagevars["recurse"];
$categoryList = getQuestionCategories($pagevars, $recurse); // retrieve sub categories if recurse is set

$qFinder = question_finder::get_instance(); // question_bank finder instance
$qList = $qFinder->get_questions_from_categories($categoryList, null); // retrieve all question IDs from the categories
$questions = question_load_questions($qList); // load the questions, array of stdclass objects

// TODO: present all existing records in our database, only when the user requires an update -> recompute
// TODO: pass question data objects to the similarity_comparison library (only if we want to compute the values)
$questionRowEntities = importQuestions($questions);
$comparator = getComparator();
$comparator->addQuestionRowEntities($questionRowEntities); // performs computations
$distanceMatrix = $comparator->getDistanceMatrix(); // retrieve results
$similarQuestionMatrix = $comparator->createSimilarQuestionsMatrix();
// TODO: store results in database table block_exaquest_similarity
$allSimilarityRecordArr = createAllSimilarityRecordObjectWrapperArr($comparator);

function createAllSimilarityRecordObjectWrapperArr(\GTN\comparator\QuestionOnlyComparator $comparator) : array {
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

function createSimilarityRecordObjectWrapper(int $questionID1, int $questionID2, \GTN\comparator\QuestionOnlyComparator $comparator) : ?stdClass {
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

function storeSimilarityComparisonResult(moodle_database $DB, stdClass $similarityRecordObjectWrapper , bool $overwriteExisting=true) : bool {
    $qID1 = $similarityRecordObjectWrapper->question_id1;
    $qID2 = $similarityRecordObjectWrapper->question_id2;

    $isExisting = existsSimilarityRecord($DB, $qID1, $qID2);
    if(!$overwriteExisting && $isExisting) {
        Logger::warning("Unable to store similarity record - There is already an existing record with the given IDs,"
                ." and overwrite toggle has been disabled", ["qID1" => $qID1, "qID2" => $qID2]);
        return false;
    }

    if($isExisting) {
        $newID = $DB->insert_record('block_exaquest_similarity', $similarityRecordObjectWrapper, true);
    } else {
        $existingRecord = getSimilarityRecord($DB, $qID1, $qID2);
        $newID = $existingRecord->id;
        $similarityRecordObjectWrapper->id = $newID;
        $DB->update_record('block_exaquest_similarity', $similarityRecordObjectWrapper);
    }

    Logger::debug("New/updated similarity database record stored: ", ["ID" => $newID, "similarityRecord" => $similarityRecordObjectWrapper]);
    return true;
}

// TODO: retrieve results from database table before recalculating

function existsSimilarityRecord(moodle_database $DB, int $qID1, int $qID2) : bool {
    $exists = $DB->record_exists('block_exaquest_similarity', ['question_id1' => $qID1, 'question_id2' => $qID2]);
    // check reverse IDs as well
    return $exists || $DB->record_exists('block_exaquest_similarity', ['question_id1' => $qID2, 'question_id2' => $qID1]);
}

function getSimilarityRecord(moodle_database $DB, int $qID1, int $qID2) : stdClass|bool {
    $similarityRecord = $DB->get_record('block_exaquest_similarity', ['question_id1'  => $qID1, 'question_id2'  => $qID2], IGNORE_MISSING);
    // check reverse mapping as well, in case it does not exist
    if(!$similarityRecord) {
        $similarityRecord = $DB->get_record('block_exaquest_similarity', ['question_id1'  => $qID2, 'question_id2'  => $qID1], IGNORE_MISSING);
    }

    return $similarityRecord;
}

function getAllSimilarityRecords(moodle_database $DB) : array {
    return $DB->get_records('block_exaquest_similarity');
}

/**
 * Includes only those records, whose question_id1 and question_id2 are both present in the given idArr
 * @param moodle_database $DB
 * @param array $questionIDArr
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
    $rt = $DB->get_records_select('block_exaquest_similarity',
            //'userid = :userid AND criteriaid = :criteriaid AND valueid '.$sql,
            $columnName . ' ' . $sql,
            $queryparams);

    return $rt;
}



$url = new moodle_url('/blocks/exaquest/similarity_comparison.php', array('courseid' => $courseID));
$PAGE->set_url($url);
$PAGE->set_heading(get_string('exaquest:similarity_title', 'block_exaquest'));
$PAGE->set_title(get_string('exaquest:similarity_title', 'block_exaquest'));

block_exaquest_init_js_css();

$output = $PAGE->get_renderer('block_exaquest');

echo $output->header($courseContext, $courseID, get_string('dashboard', 'block_exaquest'));

// Instantiate mustache companion class
$dashboard = new \block_exaquest\output\compare_questions($USER, $courseID, $allSimilarityRecordArr);
// Render HTML output
echo $output->render($dashboard);
echo $output->footer();

function getComparator() : \GTN\comparator\QuestionOnlyComparator {
    // TODO: set and pass parameters/algorithm to use
    $strategy = \GTN\strategy\ComparisonStrategyFactory::createJaroWinklerStrategy();
    return new \GTN\comparator\QuestionOnlyComparator($strategy);
}

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

function getQuestionCategories(array $pagevars, bool $recurse=false) : array {
    $qCatID = question_get_category_id_from_pagevars($pagevars); // retrieve question category id, is different from course cat

    if($recurse) {
        return question_categorylist($qCatID); // retrieve sub categories as well
    }

    return array([$qCatID]);

}