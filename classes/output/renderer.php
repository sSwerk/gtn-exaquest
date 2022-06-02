<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

// Standard GPL and phpdocs
namespace block_exaquest\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use html_writer;

/*
 * Useful sources for development
 * https://docs.moodle.org/dev/Output_API
 * http://componentlibrary.moodle.com/admin/tool/componentlibrary/docspage.php/moodle/components/dom-modal/
 * https://docs.moodle.org/dev/Templates#Simple_example
 */

class renderer extends plugin_renderer_base {
    /**
     * Defer to template.
     *
     * @param index_page $page
     *
     * @return string html for the page
     */
    public function render_index_page($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('block_exaquest/index_page', $data);
    }

    public function render_dashboard($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('block_exaquest/dashboard', $data);
    }

    // this will be deprecated... use template instead
    public function dashboard_request_questions() {
        global $PAGE, $COURSE;

        $request_questions_text = html_writer::tag('p', get_string('request_questions_label', 'block_exaquest'));

        $input_submit = html_writer::empty_tag('input',
            array('type' => 'submit', 'value' => get_string('request_questions_button', 'block_exaquest'),
                'class' => 'btn btn-primary'));

        $button = html_writer::div(html_writer::tag('form',
            $input_submit,
            array('method' => 'post', 'action' => $PAGE->url->out(false,
                array('action' => 'request_questions', 'sesskey' => sesskey(), 'courseid' => $COURSE->id)),
                'block_excomp_center')));

        //return html_writer::tag("form", $header . $table_html, array("method" => "post", "action" => $PAGE->url->out(false, array('action' => 'delete_selected', 'sesskey' => sesskey())), "id" => "exa-selector"));

        $content = html_writer::tag("div", $request_questions_text . $button, array("class" => ""));

        return html_writer::div($content, "dashboardcard");
    }

}