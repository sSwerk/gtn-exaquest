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
 * Class for question bank edit question column.
 *
 * @package   qbank_editquestion
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace qbank_editquestion;

use core_question\local\bank\menu_action_column_base;
use moodle_url;

/**
 * Class for question bank edit question column.
 *
 * @copyright 2009 Tim Hunt
 * @author    2021 Safat Shahin <safatshahin@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_action_column_exaquest extends edit_action_column {


    protected function get_url_icon_and_label(\stdClass $question): array {
        global $COURSE, $USER, $DB;
        if (!\question_bank::is_qtype_installed($question->qtype)) {
            // It sometimes happens that people end up with junk questions
            // in their question bank of a type that is no longer installed.
            // We cannot do most actions on them, because that leads to errors.
            return [null, null, null];
        }
        $questionStatus = $DB->get_field(BLOCK_EXAQUEST_DB_QUESTIONSTATUS, 'status', array('questionbankentryid' => $question->questionbankentryid));

        if (question_has_capability_on($question, 'edit')
            && $questionStatus < BLOCK_EXAQUEST_QUESTIONSTATUS_RELEASED
            && (($question->createdby == $USER->id
                    && has_capability('block/exaquest:setstatustoreview', \context_course::instance($COURSE->id))
                    && ($questionStatus == BLOCK_EXAQUEST_QUESTIONSTATUS_NEW || $questionStatus == BLOCK_EXAQUEST_QUESTIONSTATUS_TO_REVISE))
            || ((has_capability('block/exaquest:modulverantwortlicher', \context_course::instance($COURSE->id)))
                    || has_capability('block/exaquest:pruefungskoordination', \context_course::instance($COURSE->id))))

        ) {
            return [$this->edit_question_moodle_url($question->id), 't/edit', $this->stredit];
        } else {
            return [null, null, null];
        }
    }
}