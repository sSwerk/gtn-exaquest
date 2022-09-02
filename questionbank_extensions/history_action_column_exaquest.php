<?php

namespace qbank_history;

use core_question\local\bank\menu_action_column_base;

/**
 * Question bank column for the history action icon.
 *
 * @package    qbank_history
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class history_action_column_exaquest extends history_action_column {

    protected function get_url_icon_and_label(\stdClass $question): array {
        if (!\question_bank::is_qtype_installed($question->qtype)) {
            // It sometimes happens that people end up with junk questions
            // in their question bank of a type that is no longer installed.
            // We cannot do most actions on them, because that leads to errors.
            return [null, null, null];
        }

        if (question_has_capability_on($question, 'use')) {

            $params = [
                'entryid' => $question->questionbankentryid,
                'returnurl' => $this->qbank->returnurl,
                'courseid' => $this->qbank->course->id
            ];
            $url = new \moodle_url('/blocks/exaquest/questionbank_extensions/exaquest_history.php', $params);
            return [$url, 't/log', $this->strpreview];
        }

        return [null, null, null];
    }

}
