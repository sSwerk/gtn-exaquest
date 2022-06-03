<?php
require __DIR__ . '/inc.php';

global $CFG, $COURSE, $PAGE, $OUTPUT;

use core\event\question_category_viewed;


require_once($CFG->dirroot . '/question/editlib.php');
require_once(__DIR__ . '/exaquest_view.php');

list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
    question_edit_setup('questions', '/question/edit.php');

$courseid = required_param('courseid', PARAM_INT);

$page_params = array('courseid' => $courseid);

$url = new moodle_url('/blocks/exaquest/questbank.php', $page_params);

$PAGE->set_url($url);
$PAGE->set_heading('showQuestionBank');
//$streditingquestions = get_string('editquestions', 'question');
//$PAGE->set_title(block_exacomp_get_string($streditingquestions));
$PAGE->set_title('showQuestionBank');

$context = context_course::instance($courseid);
echo $OUTPUT->header($context, $courseid, 'showQuestionBank');



if (($lastchanged = optional_param('lastchanged', 0, PARAM_INT)) !== 0) {
    $url->param('lastchanged', $lastchanged);
}



$questionbank = new core_question\local\bank\exaquest_view($contexts, $url, $COURSE, $cm);

echo '<div class="questionbankwindow boxwidthwide boxaligncenter">';
$questionbank->display($pagevars, 'editq');
echo "</div>\n";

echo $OUTPUT->footer();