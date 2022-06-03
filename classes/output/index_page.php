<?php
// Standard GPL and phpdocs
namespace block_exaquest\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

class index_page implements renderable, templatable {
    /** @var string $sometext Some text to show how to pass data to a template. */
    var $sometext = null;

    public function __construct($sometext) {
        $this->sometext = $sometext;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->sometext = $this->sometext;
        $obj1 = new stdClass();
        $obj1->a = "obj1 a";
        $obj1->b = "obj1 b";
        $obj2 = new stdClass();
        $obj2->a = 111;
        $obj2->b = 222;

        $data->grades = [$obj1, $obj2];

        return $data;
    }
}