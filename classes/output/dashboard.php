<?php
// Standard GPL and phpdocs
namespace block_exaquest\output;

use renderable;
use renderer_base;
use stdClass;
use templatable;
use moodle_url;

class dashboard implements renderable, templatable {
    var $questions = null;
    private $capabilities;
    private $courseid;
    private $userid;
    /**
     * @var popup_request_questions
     */
    private $request_questions_popup;

    public function __construct($userid, $courseid, $capabilities, $fragenersteller) {
        $this->courseid = $courseid;
        $this->capabilities = $capabilities;
        $this->userid = $userid;
        //$this->questions = $questions;
        // when using subtemplates: call them HERE and add the capabilities and other data that is needed in the parameters
        // ... see "class search_form implements renderable, templatable {"
        //$this->fragenersteller = $fragenersteller; // not needed here, since it is given to popup_request_questions
        $this->request_questions_popup = new popup_request_questions($fragenersteller);

    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE, $COURSE;
        $data = new stdClass();
        $data->capabilities = $this->capabilities;
        $data->questions_count = block_exaquest_get_questionbankentries_by_courseid_count($this->courseid);
        $data->questions_to_review_count = block_exaquest_get_questionbankentries_to_be_reviewed_count($this->courseid);
        $data->questions_finalised_count = block_exaquest_get_finalised_questionbankentries_count($this->courseid);
        $data->questions_released_count = block_exaquest_get_released_questionbankentries_count($this->courseid);
        $data->questions_released_and_to_review_count = block_exaquest_get_released_and_to_review_questionbankentries_count($this->courseid);

        $data->my_questions_count =
            block_exaquest_get_questionbankentries_by_courseid_and_userid_count($this->userid, $this->courseid);
        $data->my_questions_to_review_count = 0;
        $data->my_questions_finalised_count = 0;

        $data->questions_for_me_to_review_link = new moodle_url('/blocks/exaquest/questbank.php',
            array('courseid' => $this->courseid, "filterstatus" => BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_REVIEW));
        $data->questions_for_me_to_review_link = $data->questions_for_me_to_review_link->raw_out(false);

        $data->questions_for_me_to_revise_link = new moodle_url('/blocks/exaquest/questbank.php',
            array('courseid' => $this->courseid, "filterstatus" => BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_REVISE));
        $data->questions_for_me_to_revise_link = $data->questions_for_me_to_revise_link->raw_out(false);

        $data->questions_for_me_to_release_link = new moodle_url('/blocks/exaquest/questbank.php',
            array('courseid' => $this->courseid, "filterstatus" => BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_RELEASE));
        $data->questions_for_me_to_release_link = $data->questions_for_me_to_release_link->raw_out(false);

        // REQUEST NEW QUESTIONS
        // this adds the subtemplate. The data, in this case fragenersteller, does not have to be given to THIS data, because it is in the data for request_questions_popup already
        $data->request_questions_popup = $this->request_questions_popup->export_for_template($output);

        // similarity comparison button
        $data->buttons = [
                compare_questions::createShowOverviewButton(new moodle_url('/blocks/exaquest/similarity_comparison.php',
                                                            array('courseid' => $this->courseid,
                                                                  'substituteid' => 0, 'hidepreviousq' => 0, 'sort' => 0)), $this->courseid)
        ];

        return $data;
    }
}