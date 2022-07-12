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
class exaquest_filters extends condition {

    /** @var string SQL fragment to add to the where clause. */
    protected $where;

    protected $filterstatus;

    /**
     * Constructor.
     */
    public function __construct($filterstatus=0) {
        $this->filterstatus = $filterstatus;

    }

    /**
     * SQL fragment to add to the where clause.
     *
     * @return string
     */
    public function where() {
        global $USER;
        switch($this->filterstatus){
            case BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS:
                break;
            case BLOCK_EXAQUEST_FILTERSTATUS_MY_CREATED_QUESTIONS:
                $this->where="qbe.ownerid = '".$USER->id."' ";
                break;
            case BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_REVIEW:
                $this->where = "qs.status = '" . BLOCK_EXAQUEST_QUESTIONSTATUS_TO_ASSESS . "' ";
                break;
            case BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_REVIEW:
                $this->where = "qs.status = '" . BLOCK_EXAQUEST_QUESTIONSTATUS_TO_ASSESS . "' AND qra.reviewerid = '" . $USER->id. "' ";
                break;
            case BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_REVISE:
                $this->where = "qs.status = '" . BLOCK_EXAQUEST_QUESTIONSTATUS_TO_REVISE . "' ";
                break;
            case BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_REVISE:
                $this->where = "qs.status = '" . BLOCK_EXAQUEST_QUESTIONSTATUS_TO_REVISE . "' AND qra.reviewerid = '" . $USER->id. "' ";
                break;
            case BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_FINALISE:
                $this->where = "qs.status = '" . BLOCK_EXAQUEST_QUESTIONSTATUS_RELEASE_REVIEW . "' ";
                break;
            case BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_FINALISE:
                $this->where = "qs.status = '" . BLOCK_EXAQUEST_QUESTIONSTATUS_RELEASE_REVIEW . "' AND qra.reviewerid = '" . $USER->id. "' ";
                break;
            case BLOCK_EXAQUEST_FILTERSTATUS_All_RELEASED_QUESTIONS:
                $this->where = "qs.status = '" . BLOCK_EXAQUEST_QUESTIONSTATUS_RELEASE . "' ";
                break;
        }
        return  $this->where;
    }

    /**
     * Print HTML to display the "Also show old questions" checkbox
     */
    public function display_options_adv() {
        global $PAGE;

        $selected = array_fill(0, 9, '');
        $selected[$this->filterstatus] = 'selected="selected"';


        $html ='<div><div style="padding:5.5px;float:left">Select Questions:</div><select class="select custom-select searchoptions custom-select" id="id_filterstatus" style="margin-left:5px;margin-bottom:50px" name="filterstatus">
                    <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS.'">Show all questions</option>
                    <optgroup label="Created:">
                        <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_MY_CREATED_QUESTIONS].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_MY_CREATED_QUESTIONS.'">Show my created Questions</option>
                    </optgroup>
                    <optgroup label="Review:">
                        <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_REVIEW].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_REVIEW.'">Show all qustions to review</option>
                        <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_REVIEW].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_REVIEW.'">Show questions for me to review</option>
                    </optgroup>
                    <optgroup label="Revise:">
                        <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_REVISE].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_REVISE.'">Show questions to revise</option>
                        <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_REVISE].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_REVISE.'">Show questions for me to revise</option>
                    </optgroup>
                    <optgroup label="Release:">
                        <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_FINALISE].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_FINALISE.'">Show questions to release</option>
                        <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_FINALISE].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_FINALISE.'">Show questions for me to release</option>
                        <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_All_RELEASED_QUESTIONS].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_All_RELEASED_QUESTIONS.'">Show all released questions</option>
                    </optgroup>
                </select></div>';

        return $html;
    }
}

