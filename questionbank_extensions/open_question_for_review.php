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

/**
 * A column type for the name of the question creator.
 *
 * @package   qbank_viewcreator
 * @copyright 2009 Tim Hunt
 * @author    2021 Ghaly Marc-Alexandre <marc-alexandreghaly@catalyst-ca.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class open_question_for_review extends column_base {

    public function get_name(): string {
        return 'openquestionforreview';
    }

    public function get_title(): string {
        return "review";

    }

    protected function display_content($question, $rowclasses): void {
        global $USER, $DB;
        echo '<a href="#" class=" btn btn-primary btn-sm review' . $question->id . '" role="button"> Frage zur Begutachtung freigeben</a>';


        $cache = \cache::make('block_exacomp', 'visibility_cache');
        $comptree = $cache->get('comptree');

        ?>

        <script type="text/javascript">

            $(".review<?php echo $question->id; ?>").click(function() {
                alert("Hallo");
            });

            window.jQueryExaquest = jQuery;
            var $ = jQuery;
            jQueryExaquest(function ($) {
                $(".review<?php echo $question->id; ?>").on("click", function () {
                    alert();
                    var data = {
                        action: 'open_question_for_review'
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

                    event.preventDefault();
                    return false;
                });
            })(jQueryExaquest);
        </script>
        <?php

    }

}