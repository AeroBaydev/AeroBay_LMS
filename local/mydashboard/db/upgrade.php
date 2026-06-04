<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for local_mydashboard.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_mydashboard_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026060400) {
        $table = new xmldb_table('local_mydashboard_streak');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('currentstreak', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('longeststreak', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('restoreused', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('lastlogindate', XMLDB_TYPE_INTEGER, '8', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('userid', XMLDB_INDEX_UNIQUE, ['userid']);

            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_mydashboard_streak_log');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('logindate', XMLDB_TYPE_INTEGER, '8', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('userid-logindate', XMLDB_INDEX_UNIQUE, ['userid', 'logindate']);
            $table->add_index('logindate', XMLDB_INDEX_NOTUNIQUE, ['logindate']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026060400, 'local', 'mydashboard');
    }

    return true;
}
