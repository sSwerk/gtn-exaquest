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


$PAGE->requires->js('/blocks/exaquest/javascript/jquery.js',true);
/**
 * A column type for the name of the question creator.
 *
 * @package   qbank_viewcreator
 * @copyright 2009 Tim Hunt
 * @author    2021 Ghaly Marc-Alexandre <marc-alexandreghaly@catalyst-ca.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class technical_review_done extends column_base {

    public function get_name(): string {
        return 'technicalreviewdone';
    }

    public function get_title(): string {
        return "technical";

    }

    protected function display_content($question, $rowclasses): void {
        global $USER, $DB;
        echo '<a href="#" id="techincal'.$question->id.'" class=" btn btn-primary btn-sm" role="button"> Fachliches Review finalisieren</a>';


        $cache = \cache::make('block_exacomp', 'visibility_cache');
        $comptree = $cache->get('comptree');

        ?>

        <script type="text/javascript">

            $(document).ready(function() {
                $("#techincal<?php echo $question->id; ?>").click(function () {
                    var data = {
                        action: 'technical_review_done',
                        questionid: <?php echo $question->id; ?>
                    };

                    var ajax = $.ajax({
                        method: "POST",
                        url: "ajax.php",
                        data: data
                    }).done(function () {
                        //console.log(data.action, 'ret', ret);
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

}
