<?php
// Standard GPL and phpdocs
namespace block_exaquest\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

class dashboardcard_formal_review_questions implements renderable, templatable {
    var $questions = null;

    public function __construct($questions) {
        $this->questions = $questions;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE, $COURSE;
        $data = new stdClass();
        $data->questions = array_values($this->questions);
        foreach ($data->questions as $question){
            $question->comma = true;
        }
        if($data->questions){
            end($data->questions)->comma = false;
            foreach ($data->questions as $question){
                $question->editlink = $question->editlink->raw_out(false); // this "false" removes the &amp; which leads to problem in this case
            }
        }
        return $data;
    }
}