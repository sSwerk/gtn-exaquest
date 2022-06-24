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
        break;
    case ('formal_review_done'):
        //$DB->record_exists('block_exaquestquestionstatus', array("questionid" => $questionid))
        $record= $DB->get_record('block_exaquestquestionstatus', array("questionid" => $questionid));
        $data = new stdClass;
        $data->id = $record->id;
        $data->questionid = $questionid;
        if($record->status == BLOCK_EXAQUEST_QUESTIONSTATUS_TECHNICAL_REVIEW_DONE){
            $data->status = BLOCK_EXAQUEST_QUESTIONSTATUS_TECHNICAL_AND_FORMAL_REVIEW_DONE;
        } else {
            $data->status = BLOCK_EXAQUEST_QUESTIONSTATUS_FORMAL_REVIEW_DONE;
        }
        $DB->update_record('block_exaquestquestionstatus', $data);
        break;
    case ('technical_review_done'):
        $record= $DB->get_record('block_exaquestquestionstatus', array("questionid" => $questionid));
        $data = new stdClass;
        $data->id = $record->id;
        $data->questionid = $questionid;
        if($record->status == BLOCK_EXAQUEST_QUESTIONSTATUS_FORMAL_REVIEW_DONE){
            $data->status = BLOCK_EXAQUEST_QUESTIONSTATUS_TECHNICAL_AND_FORMAL_REVIEW_DONE;
        } else {
            $data->status = BLOCK_EXAQUEST_QUESTIONSTATUS_TECHNICAL_REVIEW_DONE;
        }
        $DB->update_record('block_exaquestquestionstatus', $data);
        break;
    case ('release_question'):
        //$DB->record_exists('block_exaquestquestionstatus', array("questionid" => $questionid));
        $data = new stdClass;
        $data->questionid = $questionid;
        $data->status = BLOCK_EXAQUEST_QUESTIONSTATUS_RELEASE;
        $data->id = $DB->get_field('block_exaquestquestionstatus','id', array("questionid" => $questionid));
        $DB->update_record('block_exaquestquestionstatus', $data);
        break;
    case ('rework_question'):
        //$DB->record_exists('block_exaquestquestionstatus', array("questionid" => $questionid));
        $data = new stdClass;
        $data->questionid = $questionid;
        $data->status = BLOCK_EXAQUEST_QUESTIONSTATUS_NEW;
        $data->id = $DB->get_field('block_exaquestquestionstatus','id', array("questionid" => $questionid));
        $DB->update_record('block_exaquestquestionstatus', $data);
        break;

}