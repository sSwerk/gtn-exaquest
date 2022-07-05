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
    if ($oldversion < 2022062401) {

        // TODO add reference to block_exaquestquestionstatus ? or is it enough to have it in the install.xml?

        // Define table block_exaquestreviewassign to be created.
        $table = new xmldb_table('block_exaquestreviewassign');

        // Adding fields to table block_exaquestreviewassign.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('reviewerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('reviewtype', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_exaquestreviewassign.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_exaquestreviewassign.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Exaquest savepoint reached.
        upgrade_block_savepoint(true, 2022062401, 'exaquest');
    }

    if ($oldversion < 2022062404) {
        // add keys block_exaquestquestionstatus and block_exaquestreviewassign
        $table = new xmldb_table('block_exaquestquestionstatus');
        $key = new xmldb_key('questionid', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));
        // Launch add key questionid.
        $dbman->add_key($table, $key);

        $table = new xmldb_table('block_exaquestreviewassign');
        $key = new xmldb_key('questionid', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));
        // Launch add key questionid.
        $dbman->add_key($table, $key);
        $key = new xmldb_key('reviewerid', XMLDB_KEY_FOREIGN, array('reviewerid'), 'user', array('id'));
        // Launch add key reviewerid.
        $dbman->add_key($table, $key);

        // Exaquest savepoint reached.
        upgrade_block_savepoint(true, 2022062404, 'exaquest');
    }

    if ($oldversion < 2022062407) {
        // remove the roles, because of typos
        $DB->delete_records('role', ['shortname' => 'admintechnprufungsdurchf']);
        $DB->delete_records('role', ['shortname' => 'prufungskoordination']);
        $DB->delete_records('role', ['shortname' => 'prufungsstudmis']);
        $DB->delete_records('role', ['shortname' => 'fachlicherprufer']);
        $DB->delete_records('role', ['shortname' => 'prufungsmitwirkende']);
        $DB->delete_records('role', ['shortname' => 'fachlicherzweitprufer']);
        // redo the set_up_roles
        $setuptask = new \block_exaquest\task\set_up_roles();
        // queue it
        \core\task\manager::queue_adhoc_task($setuptask);
        upgrade_block_savepoint(true, 2022062407, 'exaquest');
    }

    if ($oldversion < 2022070500) {
        // rename fields questionid to questionbanentryid
        $table = new xmldb_table('block_exaquestquestionstatus');
        $field = new xmldb_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'questionbankentryid');
        }

        $table = new xmldb_table('block_exaquestreviewassign');
        $field = new xmldb_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'questionbankentryid');
        }


        // drop keys because we want to use questionbankentryid instead of questionid
        $table = new xmldb_table('block_exaquestquestionstatus');
        $key = new xmldb_key('questionid', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));
        // Launch drop key primary.
        $dbman->drop_key($table, $key);

        $table = new xmldb_table('block_exaquestreviewassign');
        $key = new xmldb_key('questionid', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));
        // Launch drop key primary.
        $dbman->drop_key($table, $key);


        // add keys block_exaquestquestionstatus and block_exaquestreviewassign with questionbankentryid instead of questionid
        $table = new xmldb_table('block_exaquestquestionstatus');
        $key = new xmldb_key('questionbankentryid', XMLDB_KEY_FOREIGN, array('questionbankentryid'), 'question_bank_entries', array('id'));
        // Launch add key questionid.
        $dbman->add_key($table, $key);

        $table = new xmldb_table('block_exaquestreviewassign');
        $key = new xmldb_key('questionbankentryid', XMLDB_KEY_FOREIGN, array('questionbankentryid'), 'question_bank_entries', array('id'));
        // Launch add key questionid.
        $dbman->add_key($table, $key);

        upgrade_block_savepoint(true, 2022070500, 'exaquest');
    }

    return $return_result;
}