<?php
/*
 * Exaquest similarity comparison extension
 *
 * @package    block_exaquest
 * @copyright  2022 Stefan Swerk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_exaquest\output;

use DateTime;
use moodle_url;
use renderable;
use renderer_base;
use templatable;
use stdClass;

class compare_questions implements renderable, templatable {
    private $courseid;
    private array $allSimilarityRecordArr;
    private $userid;
    private string $sortBy;

    public function __construct($userid, $courseid, $allSimilarityRecordArr, $sortBy="similarityDesc") {
        $this->courseid = $courseid;
        $this->allSimilarityRecordArr = $allSimilarityRecordArr;
        $this->userid = $userid;
        $this->sortBy = $sortBy;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE, $COURSE;
        $data = new stdClass();
        $data->sectionname = 'My section';
        $data->classes = 'sectionclass';
        $data->checkboxes = [
                [
                        'name' => 'Item 1',
                        'id' => 'itemid1',
                        'checked' => true,
                        'value' => "1",
                        'label' => "Reset options"
                ]
        ];
        $data->similarity_comparison_url = new moodle_url('/blocks/exaquest/similarity_comparison.php',array('courseid' => $this->courseid));

        $data->buttons = [
                self::createShowOverviewButton($data->similarity_comparison_url, $this->courseid, 'exaquest:similarity_refresh_button_label')
        ];

        // first, sort the data
        $this->sortSimilarityRecords();
        $data->similarityrecords = array();
        $data->similarityrecords[] = [ // table header entry
            'isHeader' => true,
            'col_qid1' => 'col_qid1',
            'col_qid2' => 'col_qid2',
            'col_issimilar' => 'col_issimilar',
            'col_similarity' => 'col_similarity',
            'col_timestamp' => 'col_timestamp',
            'col_threshold' => 'col_threshold',
            'col_algorithm' => 'col_algorithm'
        ];
        foreach($this->allSimilarityRecordArr as $similarityRecord) {
            $data->similarityrecords[] = $this->prepareSimilarityRecord($similarityRecord);
        }

        return $data;
    }

    private function prepareSimilarityRecord(stdClass $dbSimilarityRecord) : array {
        if(!isset($dbSimilarityRecord)) {
            return [];
        }
        // TODO: check required properties exist

        return [
            'isHeader' => false,
            'col_qid1' => s($dbSimilarityRecord->question_id1),
            'col_qid2' => s($dbSimilarityRecord->question_id2),
            'col_issimilar' => $dbSimilarityRecord->is_similar == 1 ? get_string("exaquest:similarity_true", "block_exaquest") : get_string("exaquest:similarity_false", "block_exaquest"),
            'col_similarity' => number_format($dbSimilarityRecord->similarity,2),
            'col_timestamp' => userdate_htmltime($dbSimilarityRecord->timestamp_calculation),
            'col_threshold' => number_format($dbSimilarityRecord->threshold, 2),
            'col_algorithm' => s($dbSimilarityRecord->algorithm),
            'hightlightClass' => $this->getCssClassForSimilarity($dbSimilarityRecord)
            ];
    }

    private function sortSimilarityRecords() {
        // gather keys to sort
        $similarity  = array_column($this->allSimilarityRecordArr, 'similarity');
        $algorithm = array_column($this->allSimilarityRecordArr, 'algorithm');

        // Sort the data with similarity descending, algorithm ascending
        switch($this->sortBy) {
            case "similarityAsc":
                array_multisort($similarity, SORT_ASC, $algorithm, SORT_ASC, $this->allSimilarityRecordArr);
                break;
            case "similarityDesc":
            default:
                array_multisort($similarity, SORT_DESC, $algorithm, SORT_ASC, $this->allSimilarityRecordArr);
                break;
        }
    }

    /**
     * @param moodle_url $url
     * @param int $courseid
     * @return array
     * @throws \coding_exception
     */
    public static function createShowOverviewButton(moodle_url $url, int $courseid, string $buttonLabel='exaquest:similarity_button_label'): array {
        return [
                "method" => "get",
                "url" => $url->out(false),
                "primary" => true,
                "tooltip" => get_string('exaquest:similarity_button_tooltip', 'block_exaquest'),
                "label" => get_string($buttonLabel, 'block_exaquest'),
                "attributes" => [
                        "name" => "data-attribute",
                        "value" => "yeah"
                ],
                "params" => [
                        [
                                "name" => "courseid",
                                "value" => $courseid
                        ],
                        [
                                "name" => "action",
                                "value" => "showSimilarityComparison"
                        ]
                ]
        ];
    }

    /**
     * @param stdClass $dbSimilarityRecord
     * @return string
     */
    public function getCssClassForSimilarity(stdClass $dbSimilarityRecord): string {
        $s = $dbSimilarityRecord->similarity;
        if ($s >= 1.0) {
            $cssClass = "identical";
        } else if ($s >= 0.9) {
            $cssClass = "extremelysimilar";
        } else if ($s >= 0.8) {
            $cssClass = "verysimilar";
        } else if ($s >= 0.7) {
            $cssClass = "similar";
        } else if ($s >= 0.5) {
            $cssClass = "notquitesimilar";
        } else if ($s >= 0.25) {
            $cssClass = "notsimilar";
        } else {
            $cssClass = "different";
        }
        return $cssClass;
    }
}