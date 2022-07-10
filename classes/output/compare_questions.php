<?php
// Standard GPL and phpdocs
namespace block_exaquest\output;

use DateTime;
use moodle_url;
use renderable;
use renderer_base;
use templatable;
use stdClass;

class compare_questions implements renderable, templatable {
    private $courseid;
    private $allSimilarityRecordArr;
    private $userid;

    public function __construct($userid, $courseid, $allSimilarityRecordArr) {
        $this->courseid = $courseid;
        $this->allSimilarityRecordArr = $allSimilarityRecordArr;
        $this->userid = $userid;
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
        $data->similarity_comparison_url = new moodle_url('/blocks/exaquest/similarity_comparison.php');
        $data->buttons = [
                [
                        "method" => "get",
                        "url" => $PAGE->url->out(false),
                        "primary" => true,
                        "tooltip" => get_string('exaquest:similarity_button_tooltip', 'block_exaquest'),
                        "label" => get_string('exaquest:similarity_button_label', 'block_exaquest'),
                        "attributes"=> [
                            "name"=> "data-attribute",
                            "value"=> "yeah"
                          ],
                        "params"=> [
                            [
                                "name"=>"courseid",
                                "value"=>$this->courseid
                            ],
                            [
                                "name"=>"action",
                                "value"=>"showSimilarityComparison"
                            ]
                        ]
                ]
        ];

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
            'col_qid1' => $dbSimilarityRecord->question_id1,
            'col_qid2' => $dbSimilarityRecord->question_id2,
            'col_issimilar' => $dbSimilarityRecord->is_similar === 1 ? "True" : "False",
            'col_similarity' => $dbSimilarityRecord->similarity,
            'col_timestamp' => userdate_htmltime($dbSimilarityRecord->timestamp_calculation),
            'col_threshold' => $dbSimilarityRecord->threshold,
            'col_algorithm' => $dbSimilarityRecord->algorithm
            ];
    }
}