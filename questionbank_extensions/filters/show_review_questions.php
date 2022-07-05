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


/**
 * A search class to control whether hidden / deleted questions are hidden in the list.
 *
 * @package   core_question
 * @copyright 2013 Ray Morris
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_question\bank\search;

use core_question\local\bank\question_version_status;

/**
 * This class controls whether hidden / deleted questions are hidden in the list.
 *
 * @copyright 2013 Ray Morris
 * @author    2021 Safat Shahin <safatshahin@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class show_review_questions extends condition {

    /** @var string SQL fragment to add to the where clause. */
    protected $where;

    protected $onoff;

    /**
     * Constructor.
     */
    public function __construct($onoff=false) {
        $this->onoff = $onoff;

        if($onoff) {
            $this->where = "qs.status = '" . BLOCK_EXAQUEST_QUESTIONSTATUS_TO_ASSESS . "' ";
        }

    }

    /**
     * SQL fragment to add to the where clause.
     *
     * @return string
     */
    public function where() {
        return  $this->where;
    }

    /**
     * Print HTML to display the "Also show old questions" checkbox
     */
    public function display_options_adv() {
        global $PAGE;

        if($this->onoff){
            $checked = 'checked = "checked"';
        } else {
            $checked= '';

        }

        $html = '<div class="show_review_questions">
                 <input type="hidden" name="showreviewquestions" value="0">
                 <input id="showreviewquestions_on" class="searchoptions mr-1" type="checkbox" value="1" name="showreviewquestions" '.$checked.'>
                 <label for="showreviewquestions">show review questions</label>
                 </div>';

        return $html;
    }
}

