<?php
$string['pluginname'] = 'Question Management Tool';
$string['exaquest'] = 'Question Management Tool';
$string['exaquest:addinstance'] = 'Add a new exaquest block';
$string['exaquest:myaddinstance'] = 'Add a new exaquest block to the My Moodle page';

// Block
$string['dashboard'] = 'Dashboard';
$string['get_questionbank'] = 'Questionbank';
$string['similarity'] = 'Similarity overview';


$string['request_questions_label'] = 'Request new questions from ...';
$string['request_questions_button'] = 'Request';
$string['revise_questions_label'] = 'The following questions are marked for revision: ';
$string['formal_review_questions_label'] = 'The following questions are marked for formal finalisation: ';
$string['fachlich_review_questions_label'] = 'The following questions are marked for specialist finalisation: ';





// Messages
$string['messageprovider:newquestionsrequest'] = 'New questions have been requested';
$string['please_create_new_questions'] = 'Please create new questions in <a href="{$a->url}">{$a->fullname}</a>';
$string['please_create_new_questions_subject'] = 'Please create new questions in {$a->fullname}';
$string['please_revise_question'] = 'Please revise question';
$string['please_review_question'] = 'Please review question';



// Roles and Capabilities
$string['exaquest:fragenersteller'] = 'Create questions in Exaquest block';
$string['exaquest:modulverantwortlicher'] = 'Responsible for a module';
$string['setuproles'] = 'Set up roles and capabilities';



// Dasboardcard
$string['questions_overview_title'] = 'QUESTIONS';
$string['my_questions_title'] = 'MY QUESTIONS';
$string['examinations_title'] = 'EXAMINATIONS';
$string['todos_title'] = 'TODOs';
$string['statistics_title'] = 'STATISTICS';

$string['questions_overall_count'] = 'questions overall';
$string['questions_reviewed_count'] = 'questions are finalised / reviewed';
$string['questions_to_review_count'] = 'questions have to be reviewed';
$string['questions_finalised_count'] = 'questions finalised';
$string['questions_released_count'] = 'questions released';
$string['questions_released_and_to_review_count'] = 'questions are released and should be reviewed again';

$string['my_questions_count'] = 'questions from me';
$string['my_questions_finalised_count'] = 'of my questions are finalised / reviewed';
$string['my_questions_to_review_count'] = 'of my questions have to be reviewed';


$string['list_of_exams_with_status'] = 'List of exams with status:';
$string['create_new_exam_button'] = 'create new exam';

$string['request_questions'] = 'Request new questions';
$string['questions_for_me_to_review'] = 'Questions for me to review';
$string['questions_for_me_to_revise'] = 'Questions for me to revise';
$string['questions_for_me_to_release'] = 'Questions for me to release';
$string['compare_questions'] = 'Compare questions';


//Questionbank

$string['show_all_questions'] = 'Show all questions';
$string['show_my_created_questions'] = 'Show my created Questions';
$string['show_all_qustions_to_review'] = 'Show all qustions to review';
$string['show_questions_for_me_to_review'] = 'Show questions for me to review';
$string['show_questions_to_revise'] = 'Show questions to revise';
$string['show_questions_for_me_to_revise'] = 'Show questions for me to revise';
$string['show_questions_to_release'] = 'Show questions to release';
$string['show_questions_for_me_to_release'] = 'Show questions for me to release';
$string['show_all_released_questions'] = 'Show all released questions';

$string['created'] = 'Created:';
$string['review'] = 'Review:';
$string['revise'] = 'Revise:';
$string['release'] = 'Release:';

$string['open_question_for_review'] = 'Open question for review';
$string['formal_review_done'] = 'Finish fromal review';
$string['technical_review_done'] = 'Finish technical review';
$string['revise_question'] = 'Revise question';
$string['release_question'] = 'Release question';


// Similarity Comparison
$string['exaquest:similarity_title'] = 'Similarity Comparison';
$string['exaquest:similarity_button_tooltip'] = "Go to the Similarity Comparison overview";
$string['exaquest:similarity_button_label'] = "Question Similarity Comparison overview";
$string['exaquest:similarity_refresh_button_label'] = "Refresh Similarity Comparison overview";
$string['exaquest:similarity_update_button_label'] = "Save & Update";
$string['exaquest:similarity_compute_button_label'] = "Compute similarity";
$string['exaquest:similarity_persist_button_label'] = "Compute and persist Similarity";
$string['exaquest:similarity_substitute_checkbox_label'] = "Substitute IDs";
$string['exaquest:similarity_hide_checkbox_label'] = "Hide previous versions";
$string['exaquest:similarity_sort_select_label'] = "Sort By";
$string['exaquest:similarity_true'] = "True";
$string['exaquest:similarity_false'] = "False";
$string['exaquest:similarity_col_qid1'] = "Q1";
$string['exaquest:similarity_col_qid2'] = "Q2";
$string['exaquest:similarity_col_issimilar'] = "Similar";
$string['exaquest:similarity_col_similarity'] = "Similarity";
$string['exaquest:similarity_col_timestamp'] = "Timestamp";
$string['exaquest:similarity_col_threshold'] = "Threshold";
$string['exaquest:similarity_col_algorithm'] = "Algorithm";
$string['exaquest:similarity_edit_question_button'] = 'Edit question';
$string['exaquest:similarity_stat_total_count'] = "Total questions (unique ID)";
$string['exaquest:similarity_stat_total_latest_count'] = "Total (Latest, unique ID)";
$string['exaquest:similarity_stat_total_similar_q'] = "Total similar questions (unique ID)";
$string['exaquest:similarity_stat_total_dissimilar_q'] = "Total dissimilar (unique ID)";
$string['exaquest:similarity_stat_total_latest_similar_q'] = "Total similar (Latest, unique ID)";
$string['exaquest:similarity_stat_total_latest_dissimilar_q'] = "Total dissimilar (Latest, unique ID)";
$string['exaquest:similarity_stat_ratio_similar'] = "Ratio similar (unique ID)";
$string['exaquest:similarity_stat_ratio_dissimilar'] = "Ratio dissimilar (unique ID)";
$string['exaquest:similarity_stat_ratio_latest_similar'] = "Ratio similar (Latest, unique ID)";
$string['exaquest:similarity_stat_ratio_latest_dissimilar'] = "Ratio dissimilar (Latest, unique ID)";
$string['exaquest:similarity_settings_algorithm'] = 'Similarity Comparison Algorithm';
$string['exaquest:similarity_settings_algorithm_desc'] = 'Similarity Comparison Algorithm to use';
$string['exaquest:similarity_settings_algorithm_jarowinkler'] = 'Jaro Winkler';
$string['exaquest:similarity_settings_algorithm_smithwaterman'] = 'Smith Waterman Gotoh';
$string['exaquest:similarity_settings_threshold'] = 'Threshold [0.0-1.0]';
$string['exaquest:similarity_settings_threshold_desc'] = 'Defines the threshold for considering two questions similar, range [0.0-1.0]';
$string['exaquest:similarity_settings_jwminprefixlength'] = 'Jaro Winkler - Minimum Prefix Length';
$string['exaquest:similarity_settings_jwminprefixlength_desc'] = 'Jaro–Winkler similarity uses a prefix scale p which gives more favorable ratings to strings that match from the beginning for a set prefix length';
$string['exaquest:similarity_settings_jwprefixscale'] = 'Jaro Winkler - Minimum Prefix Scale';
$string['exaquest:similarity_settings_jwprefixscale_desc'] = 'The prefix scale should not exceed 1/minPrefixLength, otherwise the similarity may be greater than 1, i.e. for a prefix length of 4, the scale should not exceed 0.25';
$string['exaquest:similarity_settings_swgmatchmalue'] = 'Smith Waterman Gotoh - Match Value';
$string['exaquest:similarity_settings_swgmatchmalue_desc'] = 'value when characters are equal (must be greater than mismatchValue)';
$string['exaquest:similarity_settings_swgmismatchvalue'] = 'Smith Waterman Gotoh - Mismatch Value';
$string['exaquest:similarity_settings_swgmismatchvalue_desc'] = 'penalty when characters are not equal';
$string['exaquest:similarity_settings_swggapvalue'] = 'Smith Waterman Gotoh - Gap Value';
$string['exaquest:similarity_settings_swggapvalue_desc'] = 'a non-positive gap penalty';
$string['exaquest:similarity_settings_nrofthreads'] = 'Number of threads';
$string['exaquest:similarity_settings_nrofthreads_desc'] = 'if this value is greater than 1, it will utilize a multi-threaded implementation to compute the similarity, which should be much more performant for greater datasets';
