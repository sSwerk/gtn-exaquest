<?php


use qbank_editquestion\editquestion_helper;

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

        // this is to get the button for creating a new question
        //$this->content->items[] = editquestion_helper::create_new_question_button(2, array('courseid' => $COURSE->id), true);

        $this->content->items[] = html_writer::tag('a', 'get questions', array('href' => $CFG->wwwroot . '/blocks/exaquest/questbank.php?courseid=' . $COURSE->id));



        return $this->content;
    }
    // The PHP tag and the curly bracket for the class definition
    // will only be closed after there is another function added in the next section.
}