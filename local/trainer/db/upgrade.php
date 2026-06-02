<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_trainer_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026051400) {
        $table = new xmldb_table('trainer');

        $field = new xmldb_field('createdby', XMLDB_TYPE_INTEGER, '11', null, null, null, null, 'last_number');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('schoolid', XMLDB_TYPE_INTEGER, '11', null, null, null, null, 'createdby');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $mappingtable = new xmldb_table('trainer_course_mapping');
        if (!$dbman->table_exists($mappingtable)) {
            $mappingtable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $mappingtable->add_field('trainerrecordid', XMLDB_TYPE_INTEGER, '10');
            $mappingtable->add_field('traineruserid', XMLDB_TYPE_INTEGER, '11');
            $mappingtable->add_field('pocid', XMLDB_TYPE_INTEGER, '11');
            $mappingtable->add_field('schoolid', XMLDB_TYPE_INTEGER, '11');
            $mappingtable->add_field('gradeid', XMLDB_TYPE_INTEGER, '11');
            $mappingtable->add_field('courseid', XMLDB_TYPE_INTEGER, '11');
            $mappingtable->add_field('poccourseid', XMLDB_TYPE_INTEGER, '11');
            $mappingtable->add_field('status', XMLDB_TYPE_INTEGER, '2', null, null, null, '1');
            $mappingtable->add_field('timecreated', XMLDB_TYPE_INTEGER, '11');
            $mappingtable->add_field('timemodified', XMLDB_TYPE_INTEGER, '11');
            $mappingtable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($mappingtable);
        }

        upgrade_plugin_savepoint(true, 2026051400, 'local', 'trainer');
    }

    return true;
}
