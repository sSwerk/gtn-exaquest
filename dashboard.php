<?php
require __DIR__ . '/inc.php';

global $DB, $CFG, $COURSE, $PAGE, $OUTPUT, $USER;

require_once($CFG->dirroot . '/question/editlib.php');
require_once(__DIR__ . '/questionbank_extensions/exaquest_view.php');

$courseid = required_param('courseid', PARAM_INT);
require_login($courseid);

list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
    question_edit_setup('questions', '/question/edit.php');

//$course = $DB->get_record('course', array('id' => $courseid));
$context = context_course::instance($courseid);

$page_params = array('courseid' => $courseid);

$url = new moodle_url('/blocks/exaquest/dashboard.php', $page_params);
$PAGE->set_url($url);
$PAGE->set_heading(get_string('dashboard', 'block_exaquest'));
$PAGE->set_title(get_string('dashboard', 'block_exaquest'));

block_exaquest_init_js_css();

$output = $PAGE->get_renderer('block_exaquest');

echo $output->header($context, $courseid, get_string('dashboard', 'block_exaquest'));
$frageneersteller= array();
$action = optional_param('action', "", PARAM_ALPHAEXT);
if ($action == 'request_questions') {
    // get all the users with role "fragesteller" and send them a notification
    $allfragenersteller = block_exaquest_get_fragenersteller_by_courseid($courseid);
    if(array_key_exists("selectedusers", $_POST)){
        $selectedfragenersteller = $_POST["selectedusers"];
        if($selectedfragenersteller){
            $frageneersteller = array_intersect_key($allfragenersteller, $selectedfragenersteller);
            foreach ($frageneersteller as $ersteller) {
                $messageobject = new stdClass();
                $messageobject->fullname = $COURSE->fullname;
                $messageobject->url = new moodle_url('/blocks/exaquest/dashboard.php', ['courseid' => $COURSE->id]);
                $messageobject->url = $messageobject->url->raw_out(false);
                $message = get_string('please_create_new_questions', 'block_exaquest', $messageobject);
                $message = get_string('please_create_new_questions_subject', 'block_exaquest', $messageobject);
                block_exaquest_send_moodle_notification("newquestionsrequest", $USER->id, $ersteller->id, $message, $message,
                    "Frageerstellung");
            }
        }
    }

}

echo '<div id="exaquest">';
if (is_enrolled($context, $USER, "block/exaquest:createquestion")) {
    // ADD QUESTION
    echo '<div class="dashboardcard">';
    $questionbank = new core_question\local\bank\exaquest_view($contexts, $url, $COURSE, $cm);
    $categoryandcontext = $pagevars["cat"];
    list($categoryid, $contextid) = explode(',', $categoryandcontext);
    $catcontext = \context::instance_by_id($contextid);
    $category = $questionbank->get_current_category_dashboard($categoryandcontext);
    $canadd = has_capability('moodle/question:add', $catcontext);
    $questionbank->create_new_question_form_dashboard($category, $canadd);
    echo '</div>';
}
if (is_enrolled($context, $USER, "block/exaquest:modulverantwortlicher")) {
    if (!isset($frageneersteller) || empty($data->fragenersteller)) {
        $frageneersteller = block_exaquest_get_fragenersteller_by_courseid($courseid);
    }
    $dashboardcard = new \block_exaquest\output\dashboardcard_request_questions($frageneersteller);
    echo $output->render($dashboardcard);
}
if (is_enrolled($context, $USER, "block/exaquest:fragenersteller")) {
        // QUESTIONS TO REVIEW
    $questions = block_exaquest_get_questions_to_revise($courseid, $USER->id);
    if (!isset($questions)) {
        $questions = [];
    }
    $dashboardcard = new \block_exaquest\output\dashboardcard_revise_questions($questions);
    echo $output->render($dashboardcard);
}
if (is_enrolled($context, $USER, "block/exaquest:fachlfragenreviewer")) {
    // QUESTIONS TO REVIEW
    $questions = block_exaquest_get_questions_to_revise($courseid, $USER->id);
    if (!isset($questions)) {
        $questions = [];
    }
    $dashboardcard = new \block_exaquest\output\dashboardcard_revise_questions($questions);
    echo $output->render($dashboardcard);
}
if (is_enrolled($context, $USER, "block/exaquest:pruefungskoordination") || is_enrolled($context, $USER, "block/exaquest:pruefungsstudmis")) {
    // QUESTIONS TO REVIEW
    $questions = block_exaquest_get_questions_to_revise($courseid, $USER->id);
    if (!isset($questions)) {
        $questions = [];
    }
    $dashboardcard = new \block_exaquest\output\dashboardcard_revise_questions($questions);
    echo $output->render($dashboardcard);
}

echo '</div>';
echo $output->footer();
