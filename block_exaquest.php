<?php
class block_exaquest extends block_list {
    public function init() {
        $this->title = get_string('exaquest', 'block_exaquest');
    }

    public function get_content() {
        global $CFG, $COURSE, $PAGE;
        if ($this->content !== null) {
            return $this->content;
        }

        $PAGE->requires->css('/blocks/exaquest/css/block_exaquest.css', true);
        //$PAGE->requires->jquery();
        //$PAGE->requires->js("/blocks/exaquest/javascript/block_exaquest.js", true);

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = 'Footer here...';

        $this->content->items[] = html_writer::tag('a', get_string('dashboard', 'block_exaquest'), array('href' => $CFG->wwwroot . '/blocks/exaquest/dashboard.php?courseid=' . $COURSE->id));
        $this->content->items[] = html_writer::tag('a', get_string('get_questions', 'block_exaquest'), array('href' => $CFG->wwwroot . '/blocks/exaquest/questbank.php?courseid=' . $COURSE->id));



        return $this->content;
    }
    // The PHP tag and the curly bracket for the class definition
    // will only be closed after there is another function added in the next section.
}