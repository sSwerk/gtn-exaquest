<?php
// Standard GPL and phpdocs
namespace block_exaquest\output;

use renderable;
use renderer_base;
use stdClass;
use templatable;

class dashboard implements renderable, templatable {
    var $questions = null;
    private $capabilities;

    public function __construct($capabilities, $fragenersteller) {
        $this->capabilities = $capabilities;
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
        $data->questions_count = 0;
        $data->questions_reviewed_count = 0;
        $data->questions_to_review_count = 0;
        $data->questions_finalised_count = 0;
        $data->questions_released_count = 0;
        $data->questions_released_to_review_count = 0;

        $data->my_questions_count = 0;
        $data->my_questions_reviewed_count = 0;
        $data->my_questions_to_review_count = 0;
        $data->my_questions_finalised_count = 0;

        // REQUEST NEW QUESTIONS
        // this adds the subtemplate. The data, in this case fragenersteller, does not have to be given to THIS data, because it is in the data for request_questions_popup already
        $data->request_questions_popup = $this->request_questions_popup->export_for_template($output);

        return $data;
    }
}