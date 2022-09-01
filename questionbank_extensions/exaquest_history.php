

<?php


require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once('exaquest_history_view.php');


require_login();
core_question\local\bank\helper::require_plugin_enabled('qbank_history');

$entryid = required_param('entryid', PARAM_INT);
$returnurl = required_param('returnurl', PARAM_RAW);

list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
    question_edit_setup('questions', '/blocks/exaquest/questionbank_extensions/exaquest_history.php');

$url = new moodle_url($thispageurl, ['entryid' => $entryid, 'returnurl' => $returnurl]);
$PAGE->set_url($url);
$questionbank = new \qbank_history\exaquest_history_view($contexts, $url, $COURSE, $entryid, $returnurl, $cm);

$streditingquestions = get_string('history_header', 'qbank_history');
$PAGE->set_title($streditingquestions);
$PAGE->set_heading($streditingquestions);
$context = $contexts->lowest();
$PAGE->set_context($context);
$PAGE->navbar->add(get_string('question'), new moodle_url($returnurl));
$PAGE->navbar->add($streditingquestions, $url);

echo $OUTPUT->header();
// Print the question area.
$questionbank->display($pagevars, 'questions');
echo $OUTPUT->footer();