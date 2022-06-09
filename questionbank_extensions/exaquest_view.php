<?php

namespace core_question\local\bank;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/question/editlib.php');

require_once('open_question_for_review.php');
require_once('plugin_feature.php');

use core_plugin_manager;
use core_question\bank\search\condition;
use qbank_columnsortorder\column_manager;
use qbank_editquestion\editquestion_helper;
use qbank_managecategories\helper;
use qbank_questiontodescriptor;



class exaquest_view extends view {


    public function __construct($contexts, $pageurl, $course, $cm = null) {
        parent::__construct($contexts, $pageurl, $course, $cm);
    }


    /**
     * Get the list of qbank plugins with available objects for features.
     *
     * @return array
     */
    protected function get_question_bank_plugins(): array {
        $questionbankclasscolumns = [];
        $newpluginclasscolumns = [];
        $corequestionbankcolumns = [
            'checkbox_column',
            'question_type_column',
            'question_name_idnumber_tags_column',
            'edit_menu_column',
            'edit_action_column',
            'copy_action_column',
            'tags_action_column',
            'preview_action_column',
            'history_action_column',
            'delete_action_column',
            'export_xml_action_column',
            'question_status_column',
            'version_number_column',
            'creator_name_column',
            'comment_count_column'
        ];
        if (question_get_display_preference('qbshowtext', 0, PARAM_BOOL, new \moodle_url(''))) {
            $corequestionbankcolumns[] = 'question_text_row';
        }

        foreach ($corequestionbankcolumns as $fullname) {
            $shortname = $fullname;
            if (class_exists('core_question\\local\\bank\\' . $fullname)) {
                $fullname = 'core_question\\local\\bank\\' . $fullname;
                $questionbankclasscolumns[$shortname] = new $fullname($this);
            } else {
                $questionbankclasscolumns[$shortname] = '';
            }
        }
        $plugins = \core_component::get_plugin_list_with_class('qbank', 'plugin_feature', 'plugin_feature.php');
        foreach ($plugins as $componentname => $plugin) {
            $pluginentrypointobject = new $plugin();
            $plugincolumnobjects = $pluginentrypointobject->get_question_columns($this);
            // Don't need the plugins without column objects.
            if (empty($plugincolumnobjects)) {
                unset($plugins[$componentname]);
                continue;
            }
            foreach ($plugincolumnobjects as $columnobject) {
                $columnname = $columnobject->get_column_name();
                foreach ($corequestionbankcolumns as $key => $corequestionbankcolumn) {
                    if (!\core\plugininfo\qbank::is_plugin_enabled($componentname)) {
                        unset($questionbankclasscolumns[$columnname]);
                        continue;
                    }
                    // Check if it has custom preference selector to view/hide.
                    if ($columnobject->has_preference()) {
                        if (!$columnobject->get_preference()) {
                            continue;
                        }
                    }
                    if ($corequestionbankcolumn === $columnname) {
                        $questionbankclasscolumns[$columnname] = $columnobject;
                    } else {
                        // Any community plugin for column/action.
                        $newpluginclasscolumns[$columnname] = $columnobject;
                    }
                }
            }
        }

        // New plugins added at the end of the array, will change in sorting feature.
        foreach ($newpluginclasscolumns as $key => $newpluginclasscolumn) {
            $questionbankclasscolumns[$key] = $newpluginclasscolumn;
        }

        // Check if qbank_columnsortorder is enabled.
        if (array_key_exists('columnsortorder', core_plugin_manager::instance()->get_enabled_plugins('qbank'))) {
            $columnorder = new column_manager();
            $questionbankclasscolumns = $columnorder->get_sorted_columns($questionbankclasscolumns);
        }

        // Mitigate the error in case of any regression.
        foreach ($questionbankclasscolumns as $shortname => $questionbankclasscolumn) {
            if (empty($questionbankclasscolumn)) {
                unset($questionbankclasscolumns[$shortname]);
            }
        }

        $specialpluginentrypointobject = new \qbank_openquestionforreview\plugin_feature();
        $specialplugincolumnobjects = $specialpluginentrypointobject->get_question_columns($this);
        $questionbankclasscolumns["open_question_for_review"] = $specialplugincolumnobjects[0];

        return $questionbankclasscolumns;
    }


    /**
     * Prints the table of questions in a category with interactions
     *
     * @param \moodle_url $pageurl     The URL to reload this page.
     * @param string     $categoryandcontext 'categoryID,contextID'.
     * @param int        $recurse     Whether to include subcategories.
     * @param int        $page        The number of the page to be displayed
     * @param int        $perpage     Number of questions to show per page
     * @param array      $addcontexts contexts where the user is allowed to add new questions.
     */
    protected function display_question_list($pageurl, $categoryandcontext, $recurse = 1, $page = 0,
                                             $perpage = 100, $addcontexts = []): void {
        global $OUTPUT;
        // This function can be moderately slow with large question counts and may time out.
        // We probably do not want to raise it to unlimited, so randomly picking 5 minutes.
        // Note: We do not call this in the loop because quiz ob_ captures this function (see raise() PHP doc).
        \core_php_time_limit::raise(300);

        $category = $this->get_current_category($categoryandcontext);

        list($categoryid, $contextid) = explode(',', $categoryandcontext);
        $catcontext = \context::instance_by_id($contextid);

        $canadd = has_capability('moodle/question:add', $catcontext);

        $this->create_new_question_form($category, $canadd);

        $this->build_query();
        $totalnumber = $this->get_question_count();
        if ($totalnumber == 0) {
            return;
        }
        $questionsrs = $this->load_page_questions($page, $perpage);
        $questions = [];
        foreach ($questionsrs as $question) {
            if (!empty($question->id)) {
                $questions[$question->id] = $question;
            }
        }
        $questionsrs->close();
        foreach ($this->requiredcolumns as $name => $column) {
            $column->load_additional_data($questions);
        }

        $pageingurl = new \moodle_url($pageurl, $pageurl->params());
        $pagingbar = new \paging_bar($totalnumber, $page, $perpage, $pageingurl);
        $pagingbar->pagevar = 'qpage';

        $this->display_top_pagnation($OUTPUT->render($pagingbar));

        // This html will be refactored in the bulk actions implementation.
        echo \html_writer::start_tag('form', ['action' => $pageurl, 'method' => 'post', 'id' => 'questionsubmit']);
        echo \html_writer::start_tag('fieldset', ['class' => 'invisiblefieldset', 'style' => "display: block;"]);
        echo \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo \html_writer::input_hidden_params($this->baseurl);

        $this->display_questions($questions);

        $this->display_bottom_pagination($OUTPUT->render($pagingbar), $totalnumber, $perpage, $pageurl);

        $this->display_bottom_controls($catcontext);

        echo \html_writer::end_tag('fieldset');
        echo \html_writer::end_tag('form');
    }


    /**
     * Create a new question form in dashboard.
     *
     * @param false|mixed|\stdClass $category
     * @param bool $canadd
     */
    function create_new_question_form_dashboard($category, $canadd): void {
        $this->create_new_question_form($category, $canadd);
    }

    function get_current_category_dashboard($categoryandcontext) {
        return $this->get_current_category($categoryandcontext);
    }

}
