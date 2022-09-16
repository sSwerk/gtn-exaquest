<?php
/*
 * Exaquest similarity comparison extension
 *
 * @package    block_exaquest
 * @copyright  2022 Stefan Swerk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_exaquest\output;

use GTN\Logger;
use moodle_url;
use renderable;
use renderer_base;
use templatable;
use stdClass;

class compare_questions implements renderable, templatable {
    private $courseid;
    private array $allSimilarityRecordArr;
    private string $sortBy;
    private bool $substituteIDs;
    private bool $hidePreviousQ;
    private array $questions;
    private array $similarityStatisticsArr;
    private moodle_url $overview_url;

    public function __construct($questions, $courseid, $allSimilarityRecordArr, $statisticsArr,
            $sortBy="similarityDesc", $substituteIDs=false, $hidePreviousQ=false) {
        $this->courseid = $courseid;
        $this->allSimilarityRecordArr = $allSimilarityRecordArr;
        $this->questions = $questions;
        $this->similarityStatisticsArr = $statisticsArr;
        $this->sortBy = $sortBy;
        $this->substituteIDs = $substituteIDs;
        $this->hidePreviousQ = $hidePreviousQ;

        Logger::debug("block_exaquest_compare_questions_renderer - construction",
                ["courseid" => $courseid, "sortby" => $sortBy, "substituteid" => json_encode($substituteIDs),
                        "hidepreviousq" => json_encode($hidePreviousQ)]);


        $this->overview_url = new moodle_url('/blocks/exaquest/similarity_comparison.php',
                array(  'courseid' => $this->courseid,
                        'substituteid' => $this->substituteIDs ? 1 : 0,
                        'hidepreviousq' => $this->hidePreviousQ ? 1 : 0,
                        'sort' => $this->sortBy));

    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     * @throws \coding_exception
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE, $COURSE;
        Logger::debug("block_exaquest_compare_questions_renderer - export renderer template"); // will log to stderr on webserver

        $data = new stdClass();
        $data->sectionname = 'My section';
        $data->classes = 'sectionclass';
        /* moved to form, since it is easier to handle the submitted value
        $data->checkboxes = [
                [
                        'name' => 'Substitute IDs',
                        'id' => 'substituteid1',
                        'checked' => true,
                        'value' => "1",
                        'label' => "Substitute IDs"
                ]
        ];*/
        $data->similarity_comparison_url = $this->overview_url;

        $data->buttons = [
                self::createShowOverviewButton($data->similarity_comparison_url, $this->courseid, 'exaquest:similarity_refresh_button_label')
        ];

        //TODO: add pagination? see paging_bar in outputcomponents.php

        // add statistics
        $data->statistics = $this->similarityStatisticsArr;


        // first, sort the data
        $this->sortSimilarityRecords();
        // second, load questions from moodle db
        //$uniqueIDs = array_unique(array_merge(array_column($this->allSimilarityRecordArr, 'question_id1'),
        //        array_column($this->allSimilarityRecordArr, 'question_id2')), SORT_REGULAR);
        //$this->questions = question_load_questions(array_values($uniqueIDs));
        $data->similarityrecords = array();
        $data->similarityrecords[] = [ // table header entry
            'isHeader' => true,
            'col_qid1' => get_string("exaquest:similarity_col_qid1", "block_exaquest"),
            'col_qid2' => get_string("exaquest:similarity_col_qid2", "block_exaquest"),
            'col_issimilar' => get_string("exaquest:similarity_col_issimilar", "block_exaquest"),
            'col_similarity' => get_string("exaquest:similarity_col_similarity", "block_exaquest"),
            'col_timestamp' => get_string("exaquest:similarity_col_timestamp", "block_exaquest"),
            'col_threshold' => get_string("exaquest:similarity_col_threshold", "block_exaquest"),
            'col_algorithm' => get_string("exaquest:similarity_col_algorithm", "block_exaquest")
        ];
        foreach($this->allSimilarityRecordArr as $similarityRecord) {
            // do not create records for older versions if the user wants to hide them
            if($this->hidePreviousQ) {
                // only include latest versions
                $q1 = $this->questions[$similarityRecord->question_id1];
                $q2 = $this->questions[$similarityRecord->question_id2];
                if(is_latest($q1->version, $q1->questionbankentryid) && is_latest($q2->version, $q2->questionbankentryid)) {
                    $data->similarityrecords[] = $this->prepareSimilarityRecord($similarityRecord);
                }
            } else { // user wants to show older versions as well
                $data->similarityrecords[] = $this->prepareSimilarityRecord($similarityRecord);
            }

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
            'col_qid1' => $this->substituteID($dbSimilarityRecord->question_id1),
            'col_qid2' => $this->substituteID($dbSimilarityRecord->question_id2),
            'col_issimilar' => $dbSimilarityRecord->is_similar == 1 ? get_string("exaquest:similarity_true", "block_exaquest") : get_string("exaquest:similarity_false", "block_exaquest"),
            'col_similarity' => number_format($dbSimilarityRecord->similarity,2),
            'col_timestamp' => userdate_htmltime($dbSimilarityRecord->timestamp_calculation),
            'col_threshold' => number_format($dbSimilarityRecord->threshold, 2),
            'col_algorithm' => s($dbSimilarityRecord->algorithm),
            'hightlightClass' => $this->getCssClassForSimilarity($dbSimilarityRecord),
            'edit_q1_button' => $this->createEditQuestionButton($dbSimilarityRecord->question_id1, $this->substituteID($dbSimilarityRecord->question_id1)),
            'edit_q2_button' => $this->createEditQuestionButton($dbSimilarityRecord->question_id2, $this->substituteID($dbSimilarityRecord->question_id2))
            ];
    }

    private function sortSimilarityRecords() {
        // gather keys to sort
        $similarity  = array_column($this->allSimilarityRecordArr, 'similarity');
        $algorithm = array_column($this->allSimilarityRecordArr, 'algorithm');

        // Sort the data with similarity descending, algorithm ascending
        switch($this->sortBy) {
            case "similarityAsc":
                Logger::debug("block_exaquest_compare_questions_renderer - sorting records in ascending similarity order");
                array_multisort($similarity, SORT_ASC, $algorithm, SORT_ASC, $this->allSimilarityRecordArr);
                break;
            case "similarityDesc":
            default:
                Logger::debug("block_exaquest_compare_questions_renderer - sorting records in descending similarity order");
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
                "primary" => false,
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
                        ],
                        [
                                "name" => "substituteid",
                                "value" => $url->get_param("substituteid")
                        ],
                        [
                                "name" => "hidepreviousq",
                                "value" => $url->get_param("hidepreviousq")
                        ],
                        [
                                "name" => "sort",
                                "value" => $url->get_param("sort")
                        ]
                ]
        ];
    }


    private function createEditQuestionButton(int $qid, string $buttonLabel): array {
        $question_url = new moodle_url('/question/bank/editquestion/question.php');

        return [
                "method" => "get",
                "url" => $question_url->out(false),
                "primary" => false,
                "tooltip" => get_string('exaquest:similarity_edit_question_button', 'block_exaquest'),
                "label" => $buttonLabel,
                "classes" => "exaquest-similarity-edit-question-btn",
                "attributes" => [
                        "name" => "data-attribute",
                        "value" => "yeah"
                ],
                "params" => [
                        [
                                "name" => "id",
                                "value" => $qid
                        ],
                        [
                                "name" => "courseid",
                                "value" => $this->courseid
                        ],
                        [
                                "name" => "returnurl",
                                "value" => $this->overview_url->out_as_local_url(false)
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


    public function substituteID(int $qid): string {
        if($this->substituteIDs && array_key_exists($qid, $this->questions)) {
            return s($this->questions[$qid]->name . ' [' . $this->questions[$qid]->id . '.V'.$this->questions[$qid]->version.']');
        }

        return s($qid);
    }

    public static function createAdminSettingsButton(string $settingsSection, string $buttonLabel, moodle_url $return): array {
        $settings_url = new moodle_url('/admin/settings.php');

        return [
                "method" => "get",
                "url" => $settings_url->out(false),
                "primary" => false,
                "tooltip" => get_string('exaquest:similarity_edit_question_button', 'block_exaquest'),
                "label" => $buttonLabel,
                "classes" => "exaquest-similarity-settings-btn",
                "attributes" => [
                        "name" => "data-attribute",
                        "value" => "yeah"
                ],
                "params" => [
                        [
                                "name" => "section",
                                "value" => $settingsSection
                        ],
                        [
                                "name" => "returnurl",
                                "value" => $return->out_as_local_url(false)
                        ]
                ]
        ];
    }

}