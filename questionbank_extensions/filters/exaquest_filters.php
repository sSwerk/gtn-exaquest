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
            case BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_RELEASE:
                $this->where = "qs.status = '" . BLOCK_EXAQUEST_QUESTIONSTATUS_FINALISED . "' ";
                break;
            case BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_RELEASE:
                $this->where = "qs.status = '" . BLOCK_EXAQUEST_QUESTIONSTATUS_FINALISED . "' AND qra.reviewerid = '" . $USER->id. "' ";
                break;
            case BLOCK_EXAQUEST_FILTERSTATUS_All_RELEASED_QUESTIONS:
                $this->where = "qs.status = '" . BLOCK_EXAQUEST_QUESTIONSTATUS_RELEASED . "' ";
                break;
        }
        return  $this->where;
    }

    /**
     * Print HTML to display the "Also show old questions" checkbox
     */
    public function display_options_adv() {
        global $PAGE, $COURSE;

        $selected = array_fill(0, 9, '');
        $selected[$this->filterstatus] = 'selected="selected"';


        $html ='<div><div style="padding:5.5px;float:left">Select Questions:</div><select class="select custom-select searchoptions custom-select" id="id_filterstatus" style="margin-left:5px;margin-bottom:50px" name="filterstatus">';
        if(has_capability('block/exaquest:readallquestions', \context_course::instance($COURSE->id))){
            $html .= '<option ' . $selected[BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS] . ' value="' . BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS . '">' . get_string('show_all_questions', 'block_exaquest') . '</option>';
        }
        $html.= '    <optgroup label="'.get_string('created', 'block_exaquest').'">';
        $html.= '        <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_MY_CREATED_QUESTIONS].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_MY_CREATED_QUESTIONS.'">'.get_string('show_my_created_questions', 'block_exaquest').'</option>';
        $html.= '    </optgroup>';
        $html.= '    <optgroup label="'.get_string('review', 'block_exaquest').'">';
        $html.= '        <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_REVIEW].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_REVIEW.'">'.get_string('show_all_qustions_to_review', 'block_exaquest').'</option>';
        $html.= '        <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_REVIEW].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_REVIEW.'">'.get_string('show_questions_for_me_to_review', 'block_exaquest').'</option>';
        $html.= '    </optgroup>';
        $html.= '    <optgroup label="'.get_string('revise', 'block_exaquest').'">';
        $html.= '        <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_REVISE].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_REVISE.'">'.get_string('show_questions_to_revise', 'block_exaquest').'</option>';
        $html.= '        <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_REVISE].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_REVISE.'">'.get_string('show_questions_for_me_to_revise', 'block_exaquest').'</option>';
        $html.= '    </optgroup>';
        $html.= '    <optgroup label="'.get_string('release', 'block_exaquest').'">';
        $html.= '        <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_RELEASE].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_ALL_QUESTIONS_TO_RELEASE.'">'.get_string('show_questions_to_release', 'block_exaquest').'</option>';
        $html.= '        <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_RELEASE].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_QUESTIONS_FOR_ME_TO_RELEASE.'">'.get_string('show_questions_for_me_to_release', 'block_exaquest').'</option>';
        $html.= '        <option '.$selected[BLOCK_EXAQUEST_FILTERSTATUS_All_RELEASED_QUESTIONS].' value="'.BLOCK_EXAQUEST_FILTERSTATUS_All_RELEASED_QUESTIONS.'">'.get_string('show_all_released_questions', 'block_exaquest').'</option>';
        $html.= '    </optgroup>';
        $html.= '</select></div>';

        return $html;
    }
}

