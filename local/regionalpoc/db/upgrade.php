<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_regionalpoc_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026052200) {
        $table = new xmldb_table('regionalpoc_arm_school');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
            $table->add_field('schoolid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
            $table->add_field('assignedby', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('userid-schoolid', XMLDB_INDEX_UNIQUE, ['userid', 'schoolid']);
            $table->add_index('schoolid', XMLDB_INDEX_NOTUNIQUE, ['schoolid']);
            $table->add_index('assignedby', XMLDB_INDEX_NOTUNIQUE, ['assignedby']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026052200, 'local', 'regionalpoc');
    }

    if ($oldversion < 2026052201) {
        $pocrole = $DB->get_record('role', ['shortname' => 'pocschool']);
        $armrole = $DB->get_record('role', ['shortname' => 'arm']);
        if ($pocrole && $armrole) {
            $systemcontext = context_system::instance();
            $capabilities = $DB->get_records('role_capabilities', [
                'roleid' => $pocrole->id,
                'contextid' => $systemcontext->id,
            ]);

            foreach ($capabilities as $capability) {
                assign_capability(
                    $capability->capability,
                    (int) $capability->permission,
                    (int) $armrole->id,
                    $systemcontext->id,
                    true
                );
            }
        }

        upgrade_plugin_savepoint(true, 2026052201, 'local', 'regionalpoc');
    }

    if ($oldversion < 2026052202) {
        $table = new xmldb_table('regionalpoc');
        $field = new xmldb_field('usertype', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'role');

        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026052202, 'local', 'regionalpoc');
    }

    if ($oldversion < 2026052203) {
        upgrade_plugin_savepoint(true, 2026052203, 'local', 'regionalpoc');
    }

    return true;
}
