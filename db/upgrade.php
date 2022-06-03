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
    return $return_result;
}