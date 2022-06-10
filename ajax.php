<?php

require __DIR__ . '/inc.php';

global $DB;

$questionid = required_param('questionid', PARAM_INT);
$action = required_param('action', PARAM_TEXT);

switch ($action) {
    case ('open_question_for_review'):
        //$DB->record_exists('block_exaquestquestionstatus', array("questionid" => $questionid));
        $data = new stdClass;
        $data->questionid = $questionid;
        $data->status = BLOCK_EXAQUEST_QUESTIONSTATUS_TO_ASSESS;
        $data->id = $DB->get_field('block_exaquestquestionstatus','id', array("questionid" => $questionid));
        $DB->update_record('block_exaquestquestionstatus', $data);

}