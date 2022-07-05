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

defined('MOODLE_INTERNAL') || die();
require_once __DIR__ . './../inc.php'; // otherwise the course_module_completion_updated does not have access to the exaquest functions in some cases

/**
 * Event observer for block_exaquest.
 */
class block_exaquest_observer {

    /**
     * Observer for \core\event\question_created event.
     *
     * @param \core\event\question_created $event
     * @return void
     */
    public static function question_created(\core\event\question_created $event) {
        global $DB;
        // first check if for this questionbankentry there already exists an entry in our table:
        $questionbankentry = get_question_bank_entry($event->objectid); // $event->objecid is the questionid
        if(!$DB->record_exists(BLOCK_EXAQUEST_DB_QUESTIONSTATUS, ["questionbankentryid" => $questionbankentry->id])){
            $insert = new stdClass();
            // get the questionbankentry via a moodle function (simply a join from questions over the versions to the banke_entry)
            $insert->questionbankentryid = $questionbankentry->id;
            $insert->status = BLOCK_EXAQUEST_QUESTIONSTATUS_NEW;
            $insert->courseid = $event->courseid;
            $DB->insert_record(BLOCK_EXAQUEST_DB_QUESTIONSTATUS, $insert);
        }
    }

}
