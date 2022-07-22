<?php

namespace core_question\local\bank;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/question/editlib.php');

require_once('change_status.php');
require_once('plugin_feature.php');
require_once('edit_action_column_exaquest.php');
require_once('filters/exaquest_filters.php');
require_once('edit_action_column_exaquest.php');
require_once('delete_action_column_exaquest.php');

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
            //'copy_action_column',
            //'tags_action_column',
            'preview_action_column',
            //'history_action_column',
            'delete_action_column',
            //'export_xml_action_column',
           // 'question_status_column',
            //'version_number_column',
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
                        //$newpluginclasscolumns[$columnname] = $columnobject;
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
        $questionbankclasscolumns["change_status"] = $specialplugincolumnobjects[0];
        $questionbankclasscolumns["edit_action_column"] = $specialplugincolumnobjects[1];
        $questionbankclasscolumns["delete_action_column"] = $specialplugincolumnobjects[2];



        return $questionbankclasscolumns;
    }

    public function display($pagevars, $tabname): void {

        $page = $pagevars['qpage'];
        $perpage = $pagevars['qperpage'];
        $cat = $pagevars['cat'];
        $recurse = $pagevars['recurse'];
        $showhidden = $pagevars['showhidden'];
        $showquestiontext = $pagevars['qbshowtext'];
        $tagids = [];
        $filterstatus = $pagevars['filterstatus'];


        if (!empty($pagevars['qtagids'])) {
            $tagids = $pagevars['qtagids'];
        }

        echo \html_writer::start_div('questionbankwindow boxwidthwide boxaligncenter');

        $editcontexts = $this->contexts->having_one_edit_tab_cap($tabname);


        // Show the filters and search options.
        $this->wanted_filters($cat, $tagids, $showhidden, $recurse, $editcontexts, $showquestiontext, $filterstatus);

        // Continues with list of questions.
        $this->display_question_list($this->baseurl, $cat, null, $page, $perpage,
            $this->contexts->having_cap('moodle/question:add'));
        echo \html_writer::end_div();

    }

    /**
     * The filters for the question bank.
     *
     * @param string $cat 'categoryid,contextid'
     * @param array $tagids current list of selected tags
     * @param bool $showhidden whether deleted questions should be displayed
     * @param int $recurse Whether to include subcategories
     * @param array $editcontexts parent contexts
     * @param bool $showquestiontext whether the text of each question should be shown in the list
     */
    public function wanted_filters($cat, $tagids, $showhidden, $recurse, $editcontexts, $showquestiontext, $filterstatus=0): void {
        global $CFG;
        list(, $contextid) = explode(',', $cat);
        $catcontext = \context::instance_by_id($contextid);
        $thiscontext = $this->get_most_specific_context();
        // Category selection form.
        $this->display_question_bank_header();

        // Display tag filter if usetags setting is enabled/enablefilters is true.
        if ($this->enablefilters) {
            if (is_array($this->customfilterobjects)) {
                foreach ($this->customfilterobjects as $filterobjects) {
                    $this->searchconditions[] = $filterobjects;
                }
            } else {
                if ($CFG->usetags) {
                    //array_unshift($this->searchconditions,
                    //    new \core_question\bank\search\tag_condition([$catcontext, $thiscontext], $tagids));
                }

                //array_unshift($this->searchconditions, new \core_question\bank\search\hidden_condition(!$showhidden));
                array_unshift($this->searchconditions, new \core_question\bank\search\exaquest_filters($filterstatus));
                //array_unshift($this->searchconditions, new \core_question\bank\search\category_condition($cat, $recurse, $editcontexts, $this->baseurl, $this->course));
            }
        }
        $this->display_options_form($showquestiontext);
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
        global $OUTPUT, $DB;
        // This function can be moderately slow with large question counts and may time out.
        // We probably do not want to raise it to unlimited, so randomly picking 5 minutes.
        // Note: We do not call this in the loop because quiz ob_ captures this function (see raise() PHP doc).
        \core_php_time_limit::raise(300);

        $editcontexts = $this->contexts->having_one_edit_tab_cap('editq'); // tabname jsut copied for convinience bacasue it won't change
        // If it is required to create sub question categories i have to iterate over it and find the context_coursecat
        if($editcontexts[1] instanceof \context_coursecat){
            // gets the parent course category for this course
            $category = end($DB->get_records('question_categories',['contextid' => $editcontexts[1]->id])); // end gives me the last element
        } else {
            throw new \coding_exception('No parent course category found');
            $category = $this->get_current_category($categoryandcontext);
        }

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
        global $DB;

        $editcontexts = $this->contexts->having_one_edit_tab_cap('editq'); // tabname jsut copied for convinience bacasue it won't change
        // If it is required to create sub question categories i have to iterate over it and find the context_coursecat
        if($editcontexts[1] instanceof \context_coursecat){
            // gets the parent course category for this course
            $category = end($DB->get_records('question_categories',['contextid' => $editcontexts[1]->id])); // end gives me the last element
        } else {
            throw new \coding_exception('No parent course category found');
            $category = $this->get_current_category($categoryandcontext);
        }
    }


    /**
     * Create the SQL query to retrieve the indicated questions, based on
     * \core_question\bank\search\condition filters.
     */
    protected function build_query(): void {
        // Get the required tables and fields.
        $joins = [];
        $fields = ['qv.status', 'qc.id as categoryid', 'qv.version', 'qv.id as versionid', 'qbe.id as questionbankentryid'];
        if (!empty($this->requiredcolumns)) {
            foreach ($this->requiredcolumns as $column) {
                $extrajoins = $column->get_extra_joins();
                foreach ($extrajoins as $prefix => $join) {
                    if (isset($joins[$prefix]) && $joins[$prefix] != $join) {
                        throw new \coding_exception('Join ' . $join . ' conflicts with previous join ' . $joins[$prefix]);
                    }
                    $joins[$prefix] = $join;
                }
                $fields = array_merge($fields, $column->get_required_fields());
            }
        }
        $fields = array_unique($fields);

        // Build the order by clause.
        $sorts = [];
        foreach ($this->sort as $sort => $order) {
            list($colname, $subsort) = $this->parse_subsort($sort);
            $sorts[] = $this->requiredcolumns[$colname]->sort_expression($order < 0, $subsort);
        }

        // Build the where clause.
        $latestversion = 'qv.version = (SELECT MAX(v.version)
                                          FROM {question_versions} v
                                          JOIN {question_bank_entries} be
                                            ON be.id = v.questionbankentryid
                                         WHERE be.id = qbe.id)';
        $tests = ['q.parent = 0', $latestversion];
        $this->sqlparams = [];
        foreach ($this->searchconditions as $searchcondition) {
            if ($searchcondition->where()) {
                $tests[] = '((' . $searchcondition->where() .'))';
            }
            if ($searchcondition->params()) {
                $this->sqlparams = array_merge($this->sqlparams, $searchcondition->params());
            }
        }
        // Build the SQL.
        $sql = ' FROM {question} q ' . implode(' ', $joins);
        $sql .= ' WHERE ' . implode(' AND ', $tests);

        $this->countsql = 'SELECT count(1)' . $sql;
        $this->loadsql = 'SELECT ' . implode(', ', $fields) . $sql . ' ORDER BY ' . implode(', ', $sorts);
    }

    /**
     * Create a new question form.
     *
     * @param false|mixed|\stdClass $category
     * @param bool $canadd
     */
    protected function create_new_question_form($category, $canadd): void {
        global $COURSE;
        if (\core\plugininfo\qbank::is_plugin_enabled('qbank_editquestion') && has_capability('block/exaquest:createquestion', \context_course::instance($COURSE->id))) {
            echo editquestion_helper::create_new_question_button($category->id,
                $this->requiredcolumns['edit_action_column_exaquest']->editquestionurl->params(), $canadd);
        }
    }



}
