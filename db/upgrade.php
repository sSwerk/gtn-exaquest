<?php

require_once __DIR__ . '/../inc.php';

function xmldb_block_exaquest_upgrade($oldversion)
{
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    $return_result = true;

    if ($oldversion < 2022060300) {

        $table = new xmldb_table('block_exaquestquestionstatus');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        // Exaquest savepoint reached.
        upgrade_block_savepoint(true, 2022060300, 'exaquest');
    }
    
    if ($oldversion < 2022060902) {
        // Creating roles and assigning capabilities
        // Done as a task AFTER the installation/upgrade, because the capabilities only exist at the end/after the installation/upgrade.
        // create the instance
        $setuptask = new \block_exaquest\task\set_up_roles();
        // queue it
        \core\task\manager::queue_adhoc_task($setuptask);
        // Exaquest savepoint reached.
        upgrade_block_savepoint(true, 2022060902, 'exaquest');
    }
    
    if ($oldversion < 2022062400) {
        // Define table block_exaquest_similarity to be created.
        $table = new xmldb_table('block_exaquest_similarity');

        // Adding fields to table block_exaquest_similarity.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('question_id1', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('question_id2', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('is_similar', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('similarity', XMLDB_TYPE_NUMBER, '20, 19', null, null, null, null);
        $table->add_field('timestamp_calculation', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('threshold', XMLDB_TYPE_NUMBER, '20, 19', null, null, null, null);
        $table->add_field('algorithm', XMLDB_TYPE_CHAR, '20', null, null, null, null);

        // Adding keys to table block_exaquest_similarity.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_questionid_1', XMLDB_KEY_FOREIGN, ['question_id1'], 'question', ['id']);
        $table->add_key('fk_questionid_2', XMLDB_KEY_FOREIGN, ['question_id2'], 'question', ['id']);

        // Conditionally launch create table for block_exaquest_similarity.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Exaquest savepoint reached
        upgrade_block_savepoint(true, 2022062400, 'exaquest');
    }
    
    return $return_result;
}

