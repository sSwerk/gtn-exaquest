<?php
require __DIR__ . '/inc.php';

global $DB, $CFG, $COURSE, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
require_login($courseid);

//$course = $DB->get_record('course', array('id' => $courseid));
$context = context_course::instance($courseid);

$page_params = array('courseid' => $courseid);

$url = new moodle_url('/blocks/exaquest/dashboard.php', $page_params);
$PAGE->set_url($url);
$PAGE->set_heading(get_string('dashboard', 'block_exaquest'));
$PAGE->set_title(get_string('dashboard', 'block_exaquest'));

echo $OUTPUT->header($context, $courseid, get_string('dashboard', 'block_exaquest'));
echo $OUTPUT->footer();
