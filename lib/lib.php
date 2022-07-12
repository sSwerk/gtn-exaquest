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
const BLOCK_EXAQUEST_DB_REVIEWASSIGN = 'block_exaquestreviewassign';

/**
 * Question Status
 */
const BLOCK_EXAQUEST_QUESTIONSTATUS_NEW = 0;
const BLOCK_EXAQUEST_QUESTIONSTATUS_TO_ASSESS = 1;
const BLOCK_EXAQUEST_QUESTIONSTATUS_FORMAL_REVIEW_DONE = 2;
const BLOCK_EXAQUEST_QUESTIONSTATUS_TECHNICAL_REVIEW_DONE = 3;
const BLOCK_EXAQUEST_QUESTIONSTATUS_TECHNICAL_AND_FORMAL_REVIEW_DONE = 4;
const BLOCK_EXAQUEST_QUESTIONSTATUS_TO_REVISE = 5;
const BLOCK_EXAQUEST_QUESTIONSTATUS_TO_RELEASE = 6;
const BLOCK_EXAQUEST_QUESTIONSTATUS_RELEASED = 7;
const BLOCK_EXAQUEST_QUESTIONSTATUS_IN_QUIZ = 8;
const BLOCK_EXAQUEST_QUESTIONSTATUS_LOCKED = 9;

/**
 * Misc
 */
const BLOCK_EXAQUEST_DB_REVIEWTYPE_FORMAL = 0;
const BLOCK_EXAQUEST_DB_REVIEWTYPE_FACHLICH = 1;

/**
 * Filter Status
 */
const BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS = 0;
const BLOCK_EXAQUEST_FILTERSTATUS_MY_CREATED_QUESTIONS = 1;
const BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_REVIEW = 2;
const BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_REVIEW = 3;
const BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_REVISE = 4;
const BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_REVISE = 5;
const BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_RELEASE = 6;
const BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_release = 7;
const BLOCK_EXAQUEST_FILTERSTATUS_All_RELEASED_QUESTIONS = 8;



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
 * Returns count of questionbankentries that have to be revised of this course of this user
 * used e.g. for the fragenersteller to see which questions they should revise
 *
 * @param $courseid
 * @param $userid
 * @return array
 */
function block_exaquest_get_questionbankentries_to_revise_count($courseid, $userid) {
    global $DB;
    $sql = "SELECT q.*
			FROM {" . BLOCK_EXAQUEST_DB_QUESTIONSTATUS . "} qs
			JOIN {question_bank_entries} qe ON qs.questionbankentryid = qe.id
			WHERE qe.ownerid = :ownerid
			AND qs.status = :status";

    $questions =
        count($DB->get_records_sql($sql, array("ownerid" => $userid, "status" => BLOCK_EXAQUEST_QUESTIONSTATUS_TO_REVISE)));

    return $questions;
}

/**
 * Returns all count of questionbankentries that have to be formally reviewed
 * used e.g. for the prüfungscoordination or the studmis to see which questions they should revise
 *
 * @param $courseid
 * @param $userid
 * @return array
 */
function block_exaquest_get_questionbankentries_to_formal_review_count($courseid, $userid) {
    global $DB;
    $sql = "SELECT q.*
			FROM {" . BLOCK_EXAQUEST_DB_REVIEWASSIGN . "} ra
			JOIN {question_bank_entries} qe ON ra.questionbankentryid = qe.id
			WHERE ra.reviewerid = :reviewerid
			AND ra.reviewtype = :reviewtype";

    $questions =
        count($DB->get_records_sql($sql, array("reviewerid" => $userid, "reviewtype" => BLOCK_EXAQUEST_DB_REVIEWTYPE_FORMAL)));

    return $questions;
}

/**
 * Returns count of questionbankentries that have to be fachlich reviewed
 * used e.g. for the fachlicherreviewer to see which questions they should revise
 *
 * @param $courseid
 * @param $userid
 * @return array
 */
function block_exaquest_get_questionbankentries_to_fachlich_review_count($courseid, $userid) {
    global $DB;
    $sql = "SELECT q.*
			FROM {" . BLOCK_EXAQUEST_DB_REVIEWASSIGN . "} ra
			JOIN {question_bank_entries} qe ON ra.questionbankentryid = qe.id
			WHERE ra.reviewerid = :reviewerid
			AND ra.reviewtype = :reviewtype";

    $questions =
        count($DB->get_records_sql($sql, array("reviewerid" => $userid, "reviewtype" => BLOCK_EXAQUEST_DB_REVIEWTYPE_FACHLICH)));

    return $questions;
}

/**
 * Returns count of all questionbankentries (all entries in exaquestqeustionstatus)
 *
 * @param $courseid
 * @return array
 */
function block_exaquest_get_questionbankentries_by_courseid_count($courseid) {
    global $DB;
    $sql = "SELECT qs.id
			FROM {" . BLOCK_EXAQUEST_DB_QUESTIONSTATUS . "} qs
			WHERE qs.courseid = :courseid";

    // we simply count the exaquestquestionstatus entries for this course, so we do not need to have the category, do not read unneccesary entries in the question_bank_entries etc

    $questions = count($DB->get_records_sql($sql, array("courseid" => $courseid)));

    // TODO: check the questionlib for functions like get_question_bank_entry( that could be useful

    return $questions;
}

/**
 * Returns count of all questionbankentries (all entries in exaquestqeustionstatus)
 *
 * @param $courseid
 * @return array
 */
function block_exaquest_get_questionbankentries_by_courseid_and_userid_count($courseid, $userid) {
    global $DB;
    $sql = "SELECT qs.id
              FROM {" . BLOCK_EXAQUEST_DB_QUESTIONSTATUS . "} qs
              JOIN {question_bank_entries} qbe ON qbe.id = qs.questionbankentryid 
             WHERE qs.courseid = :courseid
             AND qbe.ownerid = :ownerid";

    $questions = count($DB->get_records_sql($sql, array("courseid" => $courseid, "ownerid" => $userid)));

    return $questions;
}

/**
 * Returns count of
 *
 * @param $courseid
 * @return array
 */
function block_exaquest_get_reviewed_questionbankentries_count($courseid) {
    global $DB;
    $sql = "SELECT qs.id
			FROM {" . BLOCK_EXAQUEST_DB_QUESTIONSTATUS . "} qs
			WHERE qs.courseid = :courseid
			AND qs.status = :status";

    $questions = count($DB->get_records_sql($sql,
        array("courseid" => $courseid, "status" => BLOCK_EXAQUEST_QUESTIONSTATUS_TECHNICAL_AND_FORMAL_REVIEW_DONE)));

    return $questions;
}

/**
 * Returns count of
 *
 * @param $courseid
 * @return array
 */
function block_exaquest_get_questionbankentries_to_be_reviewed_count($courseid) {
    global $DB;
    $sql = "SELECT qs.id
			FROM {" . BLOCK_EXAQUEST_DB_QUESTIONSTATUS . "} qs
			WHERE qs.courseid = :courseid
			AND qs.status = :fachlichreviewdone
			OR qs.status = :formalreviewdone
			OR qs.status = :toassess";

    $questions = count($DB->get_records_sql($sql,
        array("courseid" => $courseid, "fachlichreviewdone" => BLOCK_EXAQUEST_QUESTIONSTATUS_TECHNICAL_REVIEW_DONE,
            "formalreviewdone" => BLOCK_EXAQUEST_QUESTIONSTATUS_FORMAL_REVIEW_DONE,
            "toassess" => BLOCK_EXAQUEST_QUESTIONSTATUS_TO_ASSESS)));

    return $questions;
}

/**
 * Returns count of
 *
 * @param $courseid
 * @return array
 */
function block_exaquest_get_questionbankentries_released_count($courseid) {
    global $DB;
    $sql = "SELECT qs.id
			FROM {" . BLOCK_EXAQUEST_DB_QUESTIONSTATUS . "} qs
			WHERE qs.courseid = :courseid
			AND qs.status = :released";

    $questions = count($DB->get_records_sql($sql,
        array("courseid" => $courseid, "released" => BLOCK_EXAQUEST_QUESTIONSTATUS_RELEASED)));

    return $questions;
}

/**
 * Sets up the roles in install.php and upgrade.php
 */
function block_exaquest_set_up_roles() {
    global $DB;
    $context = \context_system::instance();
    $options = array(
        'shortname' => 0,
        'name' => 0,
        'description' => 0,
        'permissions' => 1,
        'archetype' => 0,
        'contextlevels' => 1,
        'allowassign' => 1,
        'allowoverride' => 1,
        'allowswitch' => 1,
        'allowview' => 1);



    if (!$DB->record_exists('role', ['shortname' => 'admintechnpruefungsdurchf'])) {
        $roleid = create_role('admin./techn. Prüfungsdurchf.', 'admintechnpruefungsdurchf', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype,
            $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'admintechnpruefungsdurchf'])->id;
    }
    assign_capability('block/exaquest:admintechnpruefungsdurchf', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:technicalreview', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:executeexam', CAP_ALLOW, $roleid, $context);

    if (!$DB->record_exists('role', ['shortname' => 'pruefungskoordination'])) {
        $roleid = create_role('Prüfungskoordination', 'pruefungskoordination', '', 'manager');
        $archetype = $DB->get_record('role', ['shortname' => 'manager'])->id; // manager archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype,
            $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'pruefungskoordination'])->id;
    }
    assign_capability('block/exaquest:pruefungskoordination', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:readallquestions', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:readquestionstatistics', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:changestatusofreleasedquestions', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:createquestion', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:setstatustoreview', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:setstatustofinalised', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:showquestionstoreview', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:editquestiontoreview', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:showfinalisedquestions', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:showquestionstorevise', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:editallquestions', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:addquestiontoexam', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:assignsecondexaminator', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:definequestionblockingtime', CAP_ALLOW, $roleid, $context);

    if (!$DB->record_exists('role', ['shortname' => 'pruefungsstudmis'])) {
        $roleid = create_role('PrüfungsStudMis', 'pruefungsstudmis', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype,
            $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'pruefungsstudmis'])->id;
    }
    assign_capability('block/exaquest:pruefungsstudmis', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:readquestionstatistics', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:showquestionstorevise', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:addquestiontoexam', CAP_ALLOW, $roleid, $context);


    if (!$DB->record_exists('role', ['shortname' => 'modulverantwortlicher'])) {
        $roleid = create_role('Modulverantwortlicher', 'modulverantwortlicher', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype,
            $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'modulverantwortlicher'])->id;
    }
    assign_capability('block/exaquest:modulverantwortlicher', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:readallquestions', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:readquestionstatistics', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:changestatusofreleasedquestions', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:reviseownquestion', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:setstatustofinalised', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:editquestiontoreview', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:showfinalisedquestions', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:showquestionstorevise', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:releasequestion', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:editallquestions', CAP_ALLOW, $roleid, $context);



    if (!$DB->record_exists('role', ['shortname' => 'fragenersteller'])) {
        $roleid = create_role('Fragenersteller', 'fragenersteller', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype,
            $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'fragenersteller'])->id;
    }
    assign_capability('block/exaquest:fragenersteller', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:createquestion', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:readallquestions', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:changestatusofreleasedquestions', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:setstatustoreview', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:reviseownquestion', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:showownrevisedquestions', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:showquestionstorevise', CAP_ALLOW, $roleid, $context);


    if (!$DB->record_exists('role', ['shortname' => 'fachlfragenreviewer'])) {
        $roleid = create_role('fachl. Fragenreviewer', 'fachlfragenreviewer', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype,
            $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'fachlfragenreviewer'])->id;
    }
    assign_capability('block/exaquest:fachlfragenreviewer', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:readallquestions', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:changestatusofreleasedquestions', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:showquestionstoreview', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:editquestiontoreview', CAP_ALLOW, $roleid, $context);

    if (!$DB->record_exists('role', ['shortname' => 'beurteilungsmitwirkende'])) {
        $roleid = create_role('Beurteilungsmitwirkende', 'beurteilungsmitwirkende', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype,
            $options); // overwrites everything that is set in the options. The rest stays.
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
        $definitiontable->force_duplicate($archetype,
            $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'fachlicherpruefer'])->id;
    }
    assign_capability('block/exaquest:fachlicherpruefer', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:changestatusofreleasedquestions', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:addquestiontoexam', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:releaseexam', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:assignsecondexaminator', CAP_ALLOW, $roleid, $context);

    if (!$DB->record_exists('role', ['shortname' => 'pruefungsmitwirkende'])) {
        $roleid = create_role('Prüfungsmitwirkende', 'pruefungsmitwirkende', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype,
            $options); // overwrites everything that is set in the options. The rest stays.
        $definitiontable->read_submitted_permissions(); // just to not throw a warning because some array is null
        $definitiontable->save_changes();
    } else {
        $roleid = $DB->get_record('role', ['shortname' => 'pruefungsmitwirkende'])->id;
    }
    assign_capability('block/exaquest:pruefungsmitwirkende', CAP_ALLOW, $roleid, $context);
    assign_capability('block/exaquest:addquestiontoexam', CAP_ALLOW, $roleid, $context);

    if (!$DB->record_exists('role', ['shortname' => 'fachlicherzweitpruefer'])) {
        $roleid = create_role('Fachlicher Zweitprüfer', 'fachlicherzweitpruefer', '', 'editingteacher');
        $archetype = $DB->get_record('role', ['shortname' => 'editingteacher'])->id; // editingteacher archetype
        $definitiontable = new core_role_define_role_table_advanced($context, $roleid); //
        $definitiontable->force_duplicate($archetype,
            $options); // overwrites everything that is set in the options. The rest stays.
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


/**
 * Build navigtion tabs, depending on role and version
 *
 * @param object $context
 * @param int $courseid
 */
function block_exaquest_build_navigation_tabs($context, $courseid) {
    global $USER;

    //$globalcontext = context_system::instance();

    //$courseSettings = block_exacomp_get_settings_by_course($courseid);
    //$ready_for_use = block_exacomp_is_ready_for_use($courseid);

    //$de = false;
    //$lang = current_language();
    //if (isset($lang) && substr($lang, 0, 2) === 'de') {
    //    $de = true;
    //}
    //
    //$rows = array();

    //$isTeacher = block_exacomp_is_teacher($context) && $courseid != 1;
    //$isStudent = has_capability('block/exacomp:student', $context) && $courseid != 1 && !has_capability('block/exacomp:admin', $context);
    //$isTeacherOrStudent = $isTeacher || $isStudent;


    $rows[] = new tabobject('tab_dashboard',
        new moodle_url('/blocks/exaquest/dashboard.php', array("courseid" => $courseid)),
        get_string('dashboard', 'block_exaquest'), null, true);

    $rows[] = new tabobject('tab_get_questions',
        new moodle_url('/blocks/exaquest/questbank.php', array("courseid" => $courseid)),
        get_string('get_questions', 'block_exaquest'), null, true);


    return $rows;
}


