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

block_exaquest_init_js_css();

$output = $PAGE->get_renderer('block_exaquest');

echo $output->header($context, $courseid, get_string('dashboard', 'block_exaquest'));


$action = optional_param('action', "", PARAM_ALPHAEXT);
if ($action == 'request_questions') {
    // TODO get all the users with role "fragesteller" and send them a notification
    //block_exaquest_send_moodle_notification();
}


echo '<div id="exaquest">';
// TODO: get role
$role = 0;
switch ($role) {
    case 0: // Modulverantwortlicher
        // TODO: button "bitte fragen erstellen!"
        $dashboardcard = new \block_exaquest\output\dashboard('asdfasdf');
        echo $output->render($dashboardcard);
        echo $output->dashboard_request_questions();
        break;
    case 1:
        break;
    default:
        break;
}

echo '</div>';
echo $output->footer();
