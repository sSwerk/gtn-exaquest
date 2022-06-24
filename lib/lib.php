<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * ${PLUGINNAME} file description here.
 *
 * @package    ${PLUGINNAME}
 * @copyright  2022 Richard <${USEREMAIL}>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * DATABSE TABLE NAMES
 */
const BLOCK_EXAQUEST_DB_QUESTIONSTATUS = 'block_exaquestquestionstatus';

/**
 * Question Status
 */
const BLOCK_EXAQUEST_QUESTIONSTATUS_NEW = 0;
const BLOCK_EXAQUEST_QUESTIONSTATUS_TO_ASSESS = 1;
const BLOCK_EXAQUEST_QUESTIONSTATUS_FORMAL_REVIEW_DONE = 2;
const BLOCK_EXAQUEST_QUESTIONSTATUS_TECHNICAL_REVIEW_DONE = 3;
const BLOCK_EXAQUEST_QUESTIONSTATUS_TECHNICAL_AND_FORMAL_REVIEW_DONE = 4;
const BLOCK_EXAQUEST_QUESTIONSTATUS_TO_REVISE = 5;
const BLOCK_EXAQUEST_QUESTIONSTATUS_RELEASE_REVIEW = 6;
const BLOCK_EXAQUEST_QUESTIONSTATUS_RELEASE = 7;
const BLOCK_EXAQUEST_QUESTIONSTATUS_IN_QUIZ = 8;
const BLOCK_EXAQUEST_QUESTIONSTATUS_LOCKED = 9;

function block_exaquest_init_js_css() {
    global $PAGE, $CFG;

    // only allowed to be called once
    static $js_inited = false;
    if ($js_inited) {
        return;
    }
    $js_inited = true;
    $PAGE->requires->jquery();
    $PAGE->requires->js('/blocks/exaquest/javascript/block_exaquest.js', false);

    // main block CSS
    $PAGE->requires->css('/blocks/exaquest/css/block_exaquest.css');

    // page specific js/css
    $scriptName = preg_replace('!\.[^\.]+$!', '', basename($_SERVER['PHP_SELF']));
    if (file_exists($CFG->dirroot . '/blocks/exaquest/css/' . $scriptName . '.css')) {
        $PAGE->requires->css('/blocks/exaquest/css/' . $scriptName . '.css');
    }
    if (file_exists($CFG->dirroot . '/blocks/exaquest/javascript/' . $scriptName . '.js')) {
        $PAGE->requires->js('/blocks/exaquest/javascript/' . $scriptName . '.js', false);
    }

}

function block_exaquest_send_moodle_notification($notificationtype, $userfrom, $userto, $subject, $message, $context,
    $contexturl = null, $dakoramessage = false, $courseid = 0, $customdata = null, $messageformat = FORMAT_HTML) {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/message/lib.php');

    $eventdata = new core\message\message();

    $eventdata->modulename = 'block_exaquest';
    $eventdata->userfrom = $userfrom;
    $eventdata->userto = $userto;
    $eventdata->fullmessage = $message;
    $eventdata->name = $notificationtype;
    $eventdata->subject = $subject;
    $eventdata->fullmessageformat = $messageformat;
    $eventdata->fullmessagehtml = $message;
    $eventdata->smallmessage = $subject;
    $eventdata->component = 'block_exaquest';
    $eventdata->notification = 1;
    $eventdata->contexturl = $contexturl;
    $eventdata->contexturlname = $context;
    $eventdata->courseid = $courseid;
    $eventdata->customdata = $customdata;    // version must be 3.7 or higher, otherwise this field does not yet exist

    message_send($eventdata);
}

/**
 *
 * Returns all fragenersteller of this course
 *
 * @param $courseid
 * @return array
 */
function block_exaquest_get_fragenersteller_by_courseid($courseid) {
    $context = context_course::instance($courseid);
    return get_enrolled_users($context, 'block/exaquest:fragenersteller');
}

/**
 * Returns all questions that have to be revised of this course of this user
 * used e.g. for the fragenersteller to see which questions they should revise
 *
 * @param $courseid
 * @param $userid
 * @return array
 */
function block_exaquest_get_questions_to_revise($courseid, $userid) {
    global $DB;
    $sql = "SELECT q.*
			FROM {" . BLOCK_EXAQUEST_DB_QUESTIONSTATUS . "} qs
			JOIN {question} q ON qs.questionid = q.id
			WHERE q.createdby = :createdby
			AND qs.status = :status";

    $questions = $DB->get_records_sql($sql, array("createdby" => $userid, "status" => BLOCK_EXAQUEST_QUESTIONSTATUS_TO_REVISE));
    foreach ($questions as $question) {
        // TODO: returnurl... like in this function: protected function edit_question_link(question_attempt $qa, question_display_options $options) {
        $question->editlink =
            new \moodle_url('/question/bank/editquestion/question.php', ['courseid' => $courseid, 'id' => $question->id]);
    }

    return $questions;
}

/**
 * Sets up the roles in install.php and upgrade.php
 */
function block_exaquest_set_up_roles() {
    global $DB;
    $context = \context_system::instance();
    $options = array(
        'shortname'     => 0,
        'name'          => 0,
        'description'   => 0,
        'permissions'   => 1,
        'archetype'     => 0,
        'contextlevels' => 1,
        'allowassign'   => 1,
        'allowoverride' => 1,
        'allowswitch'   => 1,
        'allowview'   => 1);
    if (!$DB->record_exists('role', ['shortname' => 'fragenersteller'])) {
        $roleid = create_role('Fragenersteller', 'fragenersteller', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype, $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'fragenersteller'])->id;
    }
    assign_capability('block/exaquest:fragenersteller', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:createquestion', CAP_ALLOW, $roleid, $context);

    if (!$DB->record_exists('role', ['shortname' => 'admintechnpruefungsdurchf'])) {
        $roleid = create_role('admin./techn. Prüfungsdurchf.', 'admintechnpruefungsdurchf', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype, $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'admintechnpruefungsdurchf'])->id;
    }
    assign_capability('block/exaquest:admintechnpruefungsdurchf', CAP_ALLOW, $roleid, $context);

    if (!$DB->record_exists('role', ['shortname' => 'pruefungskoordination'])) {
        $roleid = create_role('Prüfungskoordination', 'pruefungskoordination', '', 'manager');
        $archetype = $DB->get_record('role', ['shortname' => 'manager'])->id; // manager archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype, $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'pruefungskoordination'])->id;
    }
    assign_capability('block/exaquest:pruefungskoordination', CAP_ALLOW, $roleid, $context);

    if (!$DB->record_exists('role', ['shortname' => 'pruefungsstudmis'])) {
        $roleid = create_role('PrüfungsStudMis', 'pruefungsstudmis', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype, $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'pruefungsstudmis'])->id;
    }
    assign_capability('block/exaquest:pruefungsstudmis', CAP_ALLOW, $roleid, $context);

    if (!$DB->record_exists('role', ['shortname' => 'modulverantwortlicher'])) {
        $roleid = create_role('Modulverantwortlicher', 'modulverantwortlicher', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype, $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'modulverantwortlicher'])->id;
    }
    assign_capability('block/exaquest:modulverantwortlicher', CAP_ALLOW, $roleid, $context);

    if (!$DB->record_exists('role', ['shortname' => 'fachlfragenreviewer'])) {
        $roleid = create_role('fachl. Fragenreviewer', 'fachlfragenreviewer', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype, $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'fachlfragenreviewer'])->id;
    }
    assign_capability('block/exaquest:fachlfragenreviewer', CAP_ALLOW, $roleid, $context);

    if (!$DB->record_exists('role', ['shortname' => 'beurteilungsmitwirkende'])) {
        $roleid = create_role('Beurteilungsmitwirkende', 'beurteilungsmitwirkende', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype, $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'beurteilungsmitwirkende'])->id;
    }
    assign_capability('block/exaquest:beurteilungsmitwirkende', CAP_ALLOW, $roleid, $context);

    if (!$DB->record_exists('role', ['shortname' => 'fachlicherpruefer'])) {
        $roleid = create_role('fachlicher Prüfer', 'fachlicherpruefer', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype, $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'fachlicherpruefer'])->id;
    }
    assign_capability('block/exaquest:fachlicherpruefer', CAP_ALLOW, $roleid, $context);

    if (!$DB->record_exists('role', ['shortname' => 'pruefungsmitwirkende'])) {
        $roleid = create_role('Prüfungsmitwirkende', 'pruefungsmitwirkende', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype, $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'pruefungsmitwirkende'])->id;
    }
    assign_capability('block/exaquest:pruefungsmitwirkende', CAP_ALLOW, $roleid, $context);

    if (!$DB->record_exists('role', ['shortname' => 'fachlicherzweitpruefer'])) {
        $roleid = create_role('Fachlicher Zweitprüfer', 'fachlicherzweitpruefer', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype, $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'fachlicherzweitpruefer'])->id;
    }
    assign_capability('block/exaquest:fachlicherzweitpruefer', CAP_ALLOW, $roleid, $context);

    //
    //role_assign($roleid, $USER->id, $contextid);

    //if ($roleid = $DB->get_field('role', 'id', array('shortname' => 'custom_role')){
    //$context = \context_system::instance(){;
    //assign_capability('block/custom_block:custom_capability', CAP_ALLOW,
    //    $roleid, $context);
    //}
}




