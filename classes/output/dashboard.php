<?php
// Standard GPL and phpdocs
namespace block_exaquest\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

class dashboard implements renderable, templatable {
    /** @var string $sometext Some text to show how to pass data to a template. */
    var $fragenersteller = null;

    public function __construct($fragenersteller) {
        $this->fragenersteller = $fragenersteller;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE, $COURSE;
        $data = new stdClass();
        $data->fragenersteller = json_encode($this->fragenersteller);

        // https://www.sitepoint.com/community/t/help-accessing-deep-level-json-in-mustache-template-solved/290780

        $data->grades = <<<EOD
                        {
                            "grades": [
                                {
                                    "course": "Arithmetic",
                                    "grade": "8/10"
                                },
                                {
                                    "course": "Geometry",
                                    "grade": "10/10"
                                },
                                {
                                    "course": "ASDF",
                                    "grade": "22/22"
                                }
                            ]
                        }
                        EOD;

        $data->action =
            $PAGE->url->out(false, array('action' => 'request_questions', 'sesskey' => sesskey(), 'courseid' => $COURSE->id));
        $data->sesskey = sesskey();
        return $data;
    }
}