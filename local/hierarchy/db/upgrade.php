<?php

function xmldb_local_hierarchy_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

	
    if($oldversion < 2018051804) {
		
		/////////////////////////code to add table dept_enrolments 
		
		// Define table dept_enrolments to be created.
		$table = new xmldb_table('dept_enrolments');

		// Adding fields to table dept_enrolments.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('enrolid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		$table->add_field('deptid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		$table->add_field('roleid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		$table->add_field('timestart', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('timeend', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('modifierid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

		// Adding keys to table dept_enrolments.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

		// Conditionally launch create table for dept_enrolments.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		// Hierarchy savepoint reached.
		upgrade_plugin_savepoint(true, 2018051804, 'local', 'hierarchy');
		
	}
	
	
    if($oldversion < 2019012213) {
		
		/////////////////////////code to add table user_dept_enrolments 
		
		// Define table user_dept_enrolments to be created.
		$table = new xmldb_table('user_dept_enrolments');

		// Adding fields to table user_dept_enrolments.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('deptid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		$table->add_field('modifierid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

		// Adding keys to table user_dept_enrolments.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

		// Conditionally launch create table for user_dept_enrolments.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		// Hierarchy savepoint reached.
		upgrade_plugin_savepoint(true, 2019012213, 'local', 'hierarchy');
		
	}
	
	
    if($oldversion < 2019012215) {
		
		/////////////////////////code to add field 'deleted' in tables 'loc' and 'loc_framework' 
		
		// Define field 'deleted' to be added to 'loc' 
		$table = new xmldb_table('loc');
		$field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '10', null, null, null, 0, 'depthlevel');
		//default value=0, add after field 'depthlevel' 
		
		// Conditionally launch 'add field deleted' 
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		
		
		
		// Define field 'deleted' to be added to 'loc_framework' 
		$table = new xmldb_table('loc_framework');
		$field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '10', null, null, null, 0, 'description');
		
		// Conditionally launch 'add field deleted' 
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// Hierarchy savepoint reached.
		upgrade_plugin_savepoint(true, 2019012215, 'local', 'hierarchy');
		
	}

	if($oldversion < 2019012218) {
		
		/////////////////////////code to add table dept_category_enrolments 
		
		// Define table dept_category_enrolments to be created.
		$table = new xmldb_table('dept_category_enrolments');

		// Adding fields to table dept_category_enrolments
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		$table->add_field('deptid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		$table->add_field('roleid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		$table->add_field('timestart', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('timeend', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('modifierid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
		$table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');

		// Adding keys to table dept_enrolments.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

		// Conditionally launch create table for dept_enrolments.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}


		/////////////////////////code to add field 'branchpoweruser' in table 'user' 
		
		$table = new xmldb_table('user');
		$field = new xmldb_field('branchpoweruser', XMLDB_TYPE_INTEGER, '10', null, null, null, 0, 'dept');
		//default value=0, add after field 'dept'
		
		// Conditionally launch 'add field branchpoweruser' 
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}


		/////////////////////////code to add field 'deptpoweruser' in table 'user' 

		$table = new xmldb_table('user');
		$field = new xmldb_field('deptpoweruser', XMLDB_TYPE_INTEGER, '10', null, null, null, 0, 'branchpoweruser');
		//default value=0, add after field 'branchpoweruser'
		
		// Conditionally launch 'add field deptpoweruser' 
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}


		// Hierarchy savepoint reached.
		upgrade_plugin_savepoint(true, 2019012218, 'local', 'hierarchy');
		
	}

	if($oldversion < 2019012220) {
		
		/////////////////////////code to add field 'designation' in table 'user'
		
		$table = new xmldb_table('user');
		$field = new xmldb_field('designation', XMLDB_TYPE_TEXT, null, null, null, null, null, 'deptpoweruser');
		
		// Conditionally launch 'add field designation'
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}


		/////////////////////////code to add field 'duration' in table 'course'

		$table = new xmldb_table('course');
		$field = new xmldb_field('duration', XMLDB_TYPE_INTEGER, '10', null, null, null, 0, 'enddate');
		
		// Conditionally launch 'add field duration'
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}


		// Hierarchy savepoint reached.
		upgrade_plugin_savepoint(true, 2019012220, 'local', 'hierarchy');
		
	}

    return true;
	
}




?>