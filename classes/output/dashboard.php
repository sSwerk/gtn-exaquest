<?php
// Standard GPL and phpdocs
namespace block_exaquest\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

class dashboard implements renderable, templatable {
    var $questions = null;

    public function __construct($capabilities, $fragenersteller) {
        $this->capabilities = $capabilities;
        $this->fragenersteller = $fragenersteller;
        //$this->questions = $questions;
        // IF we use subtemplates: call them HERE and add the capabilities and other data that is needed in the parameters
        // ... see "class search_form implements renderable, templatable {"
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
        $data->fragenersteller = $this->fragenersteller;
        $data->questions_count = 0;
        $data->questions_reviewed_count = 0;
        $data->questions_to_review_count = 0;
        $data->questions_finalised_count = 0;
        $data->questions_released_count = 0;
        $data->questions_released_to_review_count = 0;

        //$data->questions = array_values($this->questions);
        //foreach ($data->questions as $question){
        //    $question->comma = true;
        //}
        //if($data->questions){
        //    end($data->questions)->comma = false;
        //    foreach ($data->questions as $question){
        //        $question->editlink = $question->editlink->raw_out(false); // this "false" removes the &amp; which leads to problem in this case
        //    }
        //}
        return $data;
    }
}