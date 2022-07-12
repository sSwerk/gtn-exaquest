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

    // Those functions are deprecated... we use mustache instead
    ///**
    // * Defer to template.
    // *
    // * @param index_page $page
    // *
    // * @return string html for the page
    // */
    //public function render_index_page($page) {
    //    $data = $page->export_for_template($this);
    //    return parent::render_from_template('block_exaquest/index_page', $data);
    //}
    //
    //public function render_dashboardcard_request_questions($page) {
    //    $data = $page->export_for_template($this);
    //    return parent::render_from_template('block_exaquest/dashboardcard_request_questions', $data);
    //}
    //
    //public function render_dashboardcard_revise_questions($page) {
    //    $data = $page->export_for_template($this);
    //    return parent::render_from_template('block_exaquest/dashboardcard_revise_questions', $data);
    //}
    //
    //// this will be deprecated... use template instead
    //public function dashboard_request_questions() {
    //    global $PAGE, $COURSE;
    //
    //    $request_questions_text = html_writer::tag('p', get_string('request_questions_label', 'block_exaquest'));
    //
    //    $input_submit = html_writer::empty_tag('input',
    //        array('type' => 'submit', 'value' => get_string('request_questions_button', 'block_exaquest'),
    //            'class' => 'btn btn-primary'));
    //
    //    $button = html_writer::div(html_writer::tag('form',
    //        $input_submit,
    //        array('method' => 'post', 'action' => $PAGE->url->out(false,
    //            array('action' => 'request_questions', 'sesskey' => sesskey(), 'courseid' => $COURSE->id)),
    //            'block_excomp_center')));
    //
    //    //return html_writer::tag("form", $header . $table_html, array("method" => "post", "action" => $PAGE->url->out(false, array('action' => 'delete_selected', 'sesskey' => sesskey())), "id" => "exa-selector"));
    //
    //    $content = html_writer::tag("div", $request_questions_text . $button, array("class" => ""));
    //
    //    return html_writer::div($content, "dashboardcard");
    //}

    public function header($context = null, $courseid = 0, $page_identifier = "", $tabtree = null) {
        global $PAGE;

        block_exaquest_init_js_css($courseid);

        $extras = "";
        if ($PAGE->pagelayout == 'embedded') {
            ob_start();

            $title = $PAGE->heading ?: $PAGE->title;
            ?>
            <script type="text/javascript">
                if (window.parent && window.parent.block_exacomp && window.parent.block_exacomp.last_popup) {
                    // set popup title
                    window.parent.block_exacomp.last_popup.set('headerContent', <?php echo json_encode($title); ?>);
                }
            </script>
            <style>
                body {
                    /* because moodle embedded pagelayout always adds padding/margin on top */
                    padding: 0 !important;
                    margin: 0 !important;
                }
            </style>
            <?php

            if ($PAGE->heading) {
                ?>
                <!--  moodle doesn't print a title for embedded layout -->
                <h2><?php echo $PAGE->heading; ?></h2>
                <?php
            }

            $extras .= ob_get_clean();
        } else {
            if (class_exists('\block_exa2fa\api')) {
                $extras .= \block_exa2fa\api::render_timeout_info('block_exacomp');
            }
        }

        if ($tabtree === null) {
            $tabtree = $PAGE->pagelayout != 'embedded';
        }

        return
            parent::header() .
            $extras .
            (($tabtree && $context) ? parent::tabtree(block_exaquest_build_navigation_tabs($context, $courseid), $page_identifier) : '') .
            $this->wrapperdivstart();
    }

    public function wrapperdivstart() {
        return html_writer::start_tag('div', array('id' => 'block_exaquest'));
    }

    public function wrapperdivend() {
        return html_writer::end_tag('div');
    }

    public function header_simple() {
        return parent::header() . $this->wrapperdivstart();
    }

    public function footer() {
        return
            $this->wrapperdivend() .
            parent::footer();
    }
}