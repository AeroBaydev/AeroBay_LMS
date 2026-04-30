<?php

require('../../../config.php');
require('../lib.php');
global $CFG, $DB;

require_capability('local/hierarchy:manage', context_system::instance(), null, true, "Capability 'Manage hierarchies' required"); //check capability 

$prefix=required_param('prefix', PARAM_TEXT);
$frameworkid=required_param('frameworkid', PARAM_INT);

require_once($CFG->libdir . '/adminlib.php'); 
admin_externalpage_setup($prefix); 


//////// check what type of user he is
$tenantRole = $DB->get_record('role', array('shortname' => 'tenantadmin'));
$branchunitAdminRole = $DB->get_record('role', array('shortname' => 'branch_unit_admin'));

if( is_siteadmin() || user_has_role_assignment($USER->id, $tenantRole->id) ) {
	//do nothing
} else if( user_has_role_assignment($USER->id, $branchunitAdminRole->id) ) {

	$user=$DB->get_record('user', array('id'=>$USER->id));

	if($user->branch != $frameworkid) {
		redirect(new moodle_url('/my'));
	}	

} else {
	redirect(new moodle_url('/my'));
}
//////////


$table=get_table_prefix($prefix) . '_framework';
$record=$DB->get_record($table, array('id'=>$frameworkid));

$PAGE->set_context(context_system::instance());
$PAGE->set_title($record->fullname);
$url = new moodle_url('/local/hierarchy/nodes/node.php', array('prefix'=>$prefix, 'frameworkid'=>$frameworkid));
$PAGE->set_url($url);

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('site_administration', 'local_hierarchy'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('hierarchies', 'local_hierarchy'), new moodle_url('/admin/category.php?category=hierarchy'));
$PAGE->navbar->add(get_string($prefix . '_frameworks', 'local_hierarchy'), new moodle_url('/local/hierarchy/nodes/node_framework.php?prefix=locate'));
$PAGE->navbar->add($record->fullname);

echo $OUTPUT->header();

echo html_writer::start_tag('a', array('href'=>'node_framework.php?prefix=' . $prefix)) .
	html_writer::start_tag('font', array('size'=>'2')).
		"&lt&lt " . get_string('back_to_' . $prefix . '_frameworks', 'local_hierarchy') . 
	html_writer::end_tag('font') . 
html_writer::end_tag('a');

echo html_writer::start_tag('h2') . $record->fullname . html_writer::end_tag('h2');
//display framework name as heading 
echo html_writer::start_tag('p') . $record->description . html_writer::end_tag('p') . html_writer::start_tag('br');
//display framework description

$url = new moodle_url('/local/hierarchy/nodes/node/edit.php', array('prefix'=>$prefix, 'frameworkid'=>$frameworkid, 'nodeid'=>0));
echo $OUTPUT->single_button($url, get_string('add_new_' . $prefix, 'local_hierarchy'), 'get');
//add new node button 


$htmltable = new html_table();
$htmltable->attributes['class'] = 'table table-striped table-hover';
$htmltable->head = array(get_string('name', 'local_hierarchy'), get_string('users', 'local_hierarchy'), get_string('actions', 'local_hierarchy')); 


$table=get_table_prefix($prefix);
$list = array(); //list that will be passed through reference 
make_child_nodes_list($table, $frameworkid, 0, $list); //function to make a hierarchical list of a node's children 
//passing node_id=0 as parentid of top level nodes is 0 


if(count($list) > 0) {
	
	foreach ($list as $record){
		
		$htmlrow = new html_table_row(); 

		$htmlrow->id = $record->id;
		
		$margin = ($record->depthlevel - 1) * 40;
		
		
		if($record->description!='') { 
			$get_description=get_string('description', 'local_hierarchy');
			$description=$record->description;
		} else { //don't show description if it's null 
			$get_description='';
			$description='';
		}
		
		
		$htmlrow->cells[] = new html_table_cell( 
			
			html_writer::start_tag('div', array('style'=>'margin-left:' . $margin . 'px')) .

				html_writer::start_tag ('i', array('class'=>'fa fa-minus')).
				html_writer::end_tag('i').
			
				html_writer::start_tag('a', array('href'=>'node/display.php?prefix=' . $prefix . '&nodeid=' . $record->id . '&frameworkid=' . $record->frameworkid)) . $record->fullname . html_writer::end_tag('a') .
				
				html_writer::start_tag('p', array('style'=>'font-weight:bold')) . $get_description . 
				html_writer::end_tag('p') .
				
				html_writer::start_tag('p') . $description . 
				html_writer::end_tag('p') .
				
			html_writer::end_tag('div')
		); 


		$count=$DB->count_records('user_dept_enrolments', array('deptid'=>$record->id));

		$htmlrow->cells[] = new html_table_cell( 

			html_writer::start_tag('a', array('href'=>'user_list.php?deptid=' . $record->id)) . 
			html_writer::start_tag('p') . $count . html_writer::end_tag('p') . 
			html_writer::end_tag('a')
			
		);





			
		$htmlrow->cells[] = new html_table_cell( //start cell

		html_writer::start_tag('a', array('href'=>'node/edit.php?prefix=' . $prefix . '&nodeid=' . $record->id . '&frameworkid=' . $frameworkid, 'title'=>'Edit Group')) .  html_writer::start_tag('img', array('src'=>'../images/edit.png', 'height'=>'20', 'width'=>'20')) . html_writer::end_tag('a').
		//when form is to be loaded, send the prefix, nodeid and frameworkid in the url 
		
		"&nbsp"."&nbsp".

		html_writer::start_tag('a', array('href'=>'node/delete.php?prefix=' . $prefix . '&nodeid=' . $record->id . '&frameworkid=' . $frameworkid, 'title'=>'Delete Group')) .  html_writer::start_tag('img', array('src'=>'../images/delete.png', 'height'=>'20', 'width'=>'20')) . html_writer::end_tag('a').
		
		"&nbsp"."&nbsp".
		
		// html_writer::start_tag('a', array('href'=>'../dept_assignment/assignment.php?deptid=' . $record->id, 'title'=>'Course enrolment')) .  html_writer::start_tag('img', array('src'=>'../images/assign.png', 'height'=>'22', 'width'=>'22')) . html_writer::end_tag('a').

		// "&nbsp"."&nbsp".

		// html_writer::start_tag('a', array('href'=>'../dept_category_assignment/assignment.php?deptid=' . $record->id, 'title'=>'Category enrolment')) .  html_writer::start_tag('img', array('src'=>'../images/dept_category_assignment.png', 'height'=>'22', 'width'=>'22')) . html_writer::end_tag('a').

		// "&nbsp"."&nbsp".
		
		html_writer::start_tag('a', array('href'=>'../dept_assignment/user_management/manage_users.php?deptid=' . $record->id, 'title'=>'Manage users')) .  html_writer::start_tag('img', array('src'=>'../images/manage_users.png', 'height'=>'23', 'width'=>'23')) . html_writer::end_tag('a')

		// "&nbsp"."&nbsp".
		
		// html_writer::start_tag('a', array('href'=>'../poweruser_assignment/dept/assignment.php?deptid=' . $record->id, 'title'=>'Manage power users')) .  html_writer::start_tag('img', array('src'=>'../images/poweruser_assignment.png', 'height'=>'23', 'width'=>'23')) . html_writer::end_tag('a')

		); //end cell

		$htmltable->data[] = $htmlrow; //add the row to table

	}
	
	echo html_writer::table($htmltable);
	
} else {
	echo html_writer::start_tag('p') . get_string('no_' . $prefix . 's', 'local_hierarchy') . html_writer::end_tag('p');
}



echo $OUTPUT->footer();

?>