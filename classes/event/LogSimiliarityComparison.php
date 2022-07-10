<?php

/**
 * The LogSimilarityComparison event.
 *
 * @package    FULLPLUGINNAME
 * @copyright  2014 YOUR NAME
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_exaquest\event;
defined('MOODLE_INTERNAL') || die();
/**
 * The LogSimilarityComparison event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - PUT INFO HERE
 * }
 *
 * @since     Moodle MOODLEVERSION
 * @copyright 2022 Stefan Swerk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class LogSimilarityComparison extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'block_exaquest_similarity';
    }

    public static function get_name() {
        return get_string('eventLogSimilarityComparison', 'block_exaquest');
    }

    public function get_description() {
        return "The user with id {$this->userid} created ... ... ... with id {$this->objectid}.";
    }
/*
    public function get_url() {
        return new \moodle_url('....', array('parameter' => 'value', ...));
    }
*/
}