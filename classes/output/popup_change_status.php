<?php
// Standard GPL and phpdocs
namespace block_exaquest\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

class popup_change_status implements renderable, templatable {
    /** @var string $fragenersteller Part of the data that should be passed to the template. */
    var $selectusers = null;
    var $name = null;
    var $questionbankentryid = null;
    var $action = null;

    public function __construct($selectusers, $action, $name, $questionbankentryid) {
        $this->selectusers = $selectusers;
        $this->name = $name;
        $this->questionbankentryid = $questionbankentryid;
        $this->action = $action;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE, $COURSE;
        $data = new stdClass();
        $data->name = $this->name;
        $data->selectusers = array_values($this->selectusers);
        $data->questionbankentryid = $this->questionbankentryid;
        if($this->action == 'rework_question'){
            $data->require = true;
        }



        $data->action = $this->action;
        $data->sesskey = sesskey();
        return $data;
    }
}