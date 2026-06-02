<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for local_dashboard.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_dashboard_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026052000) {
        $table = new xmldb_table('local_dashboard_activity_logs');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('activitytype', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, '');
            $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
            $table->add_field('description', XMLDB_TYPE_TEXT);
            $table->add_field('schoolid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('schoolname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
            $table->add_field('actorid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('actorname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
            $table->add_field('countvalue', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('metadata', XMLDB_TYPE_TEXT);
            $table->add_field('dedupekey', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, '');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

            $table->add_index('activitytype-timecreated', XMLDB_INDEX_NOTUNIQUE, ['activitytype', 'timecreated']);
            $table->add_index('schoolid-timecreated', XMLDB_INDEX_NOTUNIQUE, ['schoolid', 'timecreated']);
            $table->add_index('dedupekey', XMLDB_INDEX_NOTUNIQUE, ['dedupekey']);
            $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026052000, 'local', 'dashboard');
    }

    if ($oldversion < 2026052100) {
        $table = new xmldb_table('local_dashboard_activity_logs');

        if ($dbman->table_exists($table)) {
            $index = new xmldb_index('actorid-timecreated', XMLDB_INDEX_NOTUNIQUE, ['actorid', 'timecreated']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }

            $index = new xmldb_index('activitytype-actorid-timecreated', XMLDB_INDEX_NOTUNIQUE,
                ['activitytype', 'actorid', 'timecreated']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        upgrade_plugin_savepoint(true, 2026052100, 'local', 'dashboard');
    }

    return true;
}
