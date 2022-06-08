<?php
// Standard GPL and phpdocs
namespace block_exaquest\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

class dashboardcard_request_questions implements renderable, templatable {
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
        // https://www.sitepoint.com/community/t/help-accessing-deep-level-json-in-mustache-template-solved/290780
        // https://stackoverflow.com/questions/35999024/how-to-iterate-an-array-of-objects-in-mustache
        //$data->fragenersteller_selfmade = [(object)["username"  => array_pop($this->fragenersteller)->username], (object)["username"  =>array_pop($this->fragenersteller)->username]];
        // this would work, but is not feasable to write like this
        // The problem with $data->fragenersteller = $this->fragenersteller; is that there is an associative array, e.g. 3 => stdClass(), 10 => stdClass() etc.... it MUST start counting at 0, otherwise it will break mustache
        $data->fragenersteller = array_values($this->fragenersteller);
        foreach ($data->fragenersteller as $fragensteller){
            $fragensteller->comma = true;
        }
        if(isset($data->fragenersteller)){
            end($data->fragenersteller)->comma = false;
        }

        $data->action =
            $PAGE->url->out(false, array('action' => 'request_questions', 'sesskey' => sesskey(), 'courseid' => $COURSE->id));
        $data->sesskey = sesskey();
        return $data;
    }
}