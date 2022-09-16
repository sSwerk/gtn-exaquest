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
        $this->content->footer = '';

        // this is to get the button for creating a new question
        //$this->content->items[] = editquestion_helper::create_new_question_button(2, array('courseid' => $COURSE->id), true);
        $this->content->items[] = html_writer::tag('a', get_string('dashboard', 'block_exaquest'),
            array('href' => $CFG->wwwroot . '/blocks/exaquest/dashboard.php?courseid=' . $COURSE->id));
        $this->content->items[] = html_writer::tag('a', get_string('get_questionbank', 'block_exaquest'),
            array('href' => $CFG->wwwroot . '/blocks/exaquest/questbank.php?courseid=' . $COURSE->id));
        // TODO: add custom plugin here
        $this->content->items[] = html_writer::tag('a', get_string('similarity', 'block_exaquest'),
            array('href' => $CFG->wwwroot . '/blocks/exaquest/similarity_comparison.php?courseid=' . $COURSE->id));

        return $this->content;
    }

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Return the plugin config settings for external functions.
     *
     * @return stdClass the configs for both the block instance and plugin
     * @throws dml_exception
     * @since Moodle 3.8
     */
    public function get_config_for_external() {
        // Return all settings for all users since it is safe (no private keys, etc..).
        $instanceconfigs = !empty($this->config) ? $this->config : new stdClass();
        $pluginconfigs = get_config('block_exaquest');

        return (object) [
                'instance' => $instanceconfigs,
                'plugin' => $pluginconfigs,
        ];
    }

    // The PHP tag and the curly bracket for the class definition
    // will only be closed after there is another function added in the next section.
}
