<?php
// Standard GPL and phpdocs
namespace block_exaquest\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

class popup_change_status implements renderable, templatable {
    /** @var string $fragenersteller Part of the data that should be passed to the template. */
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
        $data->fragenersteller = array_values($this->fragenersteller);
        foreach ($data->fragenersteller as $fragensteller){
            $fragensteller->comma = true;
        }
        if(isset($data->fragenersteller) && !empty($data->fragenersteller)){
            end($data->fragenersteller)->comma = false;
        }

        $data->action =
            $PAGE->url->out(false, array('action' => 'request_questions', 'sesskey' => sesskey(), 'courseid' => $COURSE->id));
        $data->sesskey = sesskey();
        return $data;
    }
}