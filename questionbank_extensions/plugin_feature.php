<?php


namespace qbank_openquestionforreview;

use core_question\local\bank\plugin_features_base;
use core_question\local\bank\menu_action_column_base;

/**
 * Class plugin_feature is the entrypoint for the columns.
 *
 * @package    qbank_questiontodescriptor
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Ghaly Marc-Alexandre <marc-alexandreghaly@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugin_feature extends \core_question\local\bank\plugin_features_base {

    public function get_question_columns(\core_question\local\bank\view $qbank): array {
        return [
            new change_status($qbank),
            new \qbank_editquestion\edit_action_column_exaquest($qbank),
        ];
    }
}
