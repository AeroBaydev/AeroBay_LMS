<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_videohub_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025083002) {
        $table = new xmldb_table('local_videohub_vid');

        // Add filename field if missing.
        $field = new xmldb_field('filename', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'visibility');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add contenthash field if missing.
        $field2 = new xmldb_field('contenthash', XMLDB_TYPE_CHAR, '40', null, null, null, null, 'filename');
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        upgrade_plugin_savepoint(true, 2025083002, 'local', 'videohub');
    }

    return true;
}
