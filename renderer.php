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


defined('MOODLE_INTERNAL') || die;

// https://docs.moodle.org/dev/Renderer_best_practices

class block_exaquest_renderer extends plugin_renderer_base  {
    public function dashboard_request_questions(){
        global $PAGE;

        $request_questions_text = html_writer::tag('p', get_string('request_questions_label', 'block_exaquest'));

        $input_submit = html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('save', 'admin'), 'class' => 'btn btn-primary'));

        $button = html_writer::div(html_writer::tag('form',
            $input_submit,
            array('action' => 'edit_course.php?&sesskey=' . sesskey(), 'method' => 'post')), 'block_excomp_center');


        $content = html_writer::tag("div", $request_questions_text . $button, array("class" => ""));

        return html_writer::div($content, "dasboardcard");
    }
}