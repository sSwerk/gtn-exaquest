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

namespace qbank_openquestionforreview;

use core_question\local\bank\column_base;

require_once(__DIR__ . '/../classes/output/popup_change_status.php');

$PAGE->requires->js('/blocks/exaquest/javascript/jquery.js',true);
/**
 * A column type for the name of the question creator.
 *
 * @package   qbank_viewcreator
 * @copyright 2009 Tim Hunt
 * @author    2021 Ghaly Marc-Alexandre <marc-alexandreghaly@catalyst-ca.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class change_status extends column_base {

    public function get_name(): string {
        return 'changestatus';
    }

    public function get_title(): string {
        return "Status verändern";

    }

    protected function display_content($question, $rowclasses): void {
        global $USER, $DB, $COURSE, $PAGE;
        //echo '<div class="container"><div class="row"><div class="col-md-12 text-right">';
        $output = $PAGE->get_renderer('block_exaquest');
        $questioncreator = new \stdClass();
        $questioncreator->firstname = $question->creatorfirstname;
        $questioncreator->lastname = $question->creatorlastname;
        $questioncreator->id = $question->createdby;
        $questioncreators= array($questioncreator, $questioncreator);

        switch(intval($question->teststatus)){

            case BLOCK_EXAQUEST_QUESTIONSTATUS_NEW:
            case BLOCK_EXAQUEST_QUESTIONSTATUS_TO_REVISE:
                if(intval($question->createdby) == $USER->id && has_capability('block/exaquest:setstatustoreview', \context_course::instance($COURSE->id))){
                    echo $output->render(new \block_exaquest\output\popup_change_status($questioncreators, 'open_question_for_review', get_string('open_question_for_review', 'block_exaquest'), $question->questionbankentryid));
                }
                //echo '<a href="#" class="changestatus'.$question->questionbankentryid.' btn btn-primary btn-sm" role="button" value="open_question_for_review"> '.get_string('open_question_for_review', 'block_exaquest').'</a>';
                break;
            case BLOCK_EXAQUEST_QUESTIONSTATUS_TO_ASSESS:
                if(has_capability('block/exaquest:technicalreview', \context_course::instance($COURSE->id))) {
                    echo '<button href="#" class="changestatus' . $question->questionbankentryid . ' btn btn-primary" role="button" value="technical_review_done"> ' . get_string('technical_review_done', 'block_exaquest') . '</button>';
                }
                if(has_capability('block/exaquest:setstatustoreview', \context_course::instance($COURSE->id))) {
                    echo '<button href="#" class="changestatus' . $question->questionbankentryid . ' btn btn-primary" role="button" value="formal_review_done"> ' . get_string('formal_review_done', 'block_exaquest') . '</button>';
                }
                if(has_capability('block/exaquest:reviseownquestion', \context_course::instance($COURSE->id))) {
                    echo $output->render(new \block_exaquest\output\popup_change_status($questioncreators, 'rework_question', get_string('revise_question', 'block_exaquest'), $question->questionbankentryid));
                }
                break;
            case BLOCK_EXAQUEST_QUESTIONSTATUS_FORMAL_REVIEW_DONE:
                if(has_capability('block/exaquest:technicalreview', \context_course::instance($COURSE->id))) {
                    echo '<button href="#" class="changestatus' . $question->questionbankentryid . ' btn btn-primary" role="button" value="technical_review_done"> ' . get_string('technical_review_done', 'block_exaquest') . '</button>';
                }
                if(has_capability('block/exaquest:reviseownquestion', \context_course::instance($COURSE->id))) {
                    echo $output->render(new \block_exaquest\output\popup_change_status($questioncreators, 'rework_question', get_string('revise_question', 'block_exaquest'), $question->questionbankentryid));
                }
                break;
            case BLOCK_EXAQUEST_QUESTIONSTATUS_TECHNICAL_REVIEW_DONE:
                echo '<button href="#" class="changestatus'.$question->questionbankentryid.' btn btn-primary" role="button" value="formal_review_done"> '.get_string('formal_review_done', 'block_exaquest').'</button>';
                if(has_capability('block/exaquest:reviseownquestion', \context_course::instance($COURSE->id))) {
                    echo $output->render(new \block_exaquest\output\popup_change_status($questioncreators, 'rework_question', get_string('revise_question', 'block_exaquest'), $question->questionbankentryid));
                }
                break;
            case BLOCK_EXAQUEST_QUESTIONSTATUS_FINALISED:
                if(has_capability('block/exaquest:releaseexam', \context_course::instance($COURSE->id))) {
                    echo '<button href="#" class="changestatus' . $question->questionbankentryid . ' btn btn-primary" role="button" value="release_question"> ' . get_string('release_question', 'block_exaquest') . '</button>';
                }
                if(has_capability('block/exaquest:reviseownquestion', \context_course::instance($COURSE->id))) {
                    echo $output->render(new \block_exaquest\output\popup_change_status($questioncreators, 'rework_question', get_string('revise_question', 'block_exaquest'), $question->questionbankentryid));
                }
                break;
            case BLOCK_EXAQUEST_QUESTIONSTATUS_FINALISED:
                break;
            case BLOCK_EXAQUEST_QUESTIONSTATUS_RELEASED:
                break;
            case BLOCK_EXAQUEST_QUESTIONSTATUS_IN_QUIZ:
                break;
            case BLOCK_EXAQUEST_QUESTIONSTATUS_LOCKED:
                break;
        }

        ?>

        <script type="text/javascript">

            $(document).ready(function() {
                $(".changestatus<?php echo $question->questionbankentryid; ?>").click(function () {
                    var data = {
                        action: $(this).val(),
                        questionbankentryid: <?php echo $question->questionbankentryid; ?>,
                        questionid: <?php echo $question->id; ?>,
                        courseid: <?php echo $COURSE->id; ?>,
                        users: $('.userselectioncheckbox<?php echo $question->questionbankentryid; ?>:checkbox:checked').map(function(){ return $(this).val(); }).get(),
                        commenttext: $('.commenttext<?php echo $question->questionbankentryid; ?>').val(),
                    };
                    var ajax = $.ajax({
                        method: "POST",
                        url: "ajax.php",
                        data: data
                    }).done(function () {
                        //console.log(data.action, 'ret', ret);
                        location.reload();
                    }).fail(function (ret) {
                        var errorMsg = '';
                        if (ret.responseText[0] == '<') {
                            // html
                            errorMsg = $(ret.responseText).find('.errormessage').text();
                        }
                        console.log("Error in action '" + data.action + "'", errorMsg, 'ret', ret);
                    });
                });
            });

        </script>
        <?php

    }

    public function load_additional_data(array $questions) {
        global $DB;

        $questionstatusdb = $DB->get_records("block_exaquestquestionstatus");
        $questionstatus = array();
        foreach($questionstatusdb as $qs){
            $questionstatus[$qs->questionbankentryid] = $qs->status;
        }

        foreach($questions as $question){
            $question->teststatus = $questionstatus[$question->questionbankentryid];
        }

    }

    public function get_extra_joins(): array {
        return ['qs' => 'JOIN {block_exaquestquestionstatus} qs ON qbe.id = qs.questionbankentryid',
                'qra' => 'LEFT JOIN {block_exaquestreviewassign} qra ON qbe.id = qra.questionbankentryid'];
    }


}
