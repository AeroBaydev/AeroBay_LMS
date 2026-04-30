<?php

require_once __DIR__ . '/../../../config.php'; //this way it works in case of nested file inclusions

require('../lib.php');
global $CFG, $DB;

require_capability('local/hierarchy:manage', context_system::instance(), null, true, "Capability 'Manage hierarchies' required"); //check capability 

$prefix=required_param('prefix', PARAM_TEXT);

require_once($CFG->libdir . '/adminlib.php'); 
admin_externalpage_setup($prefix); 


//////check role and show page accordingly
$can_view=false;
$can_add=false;
$can_edit=false;
$can_delete=false;
$can_manage_powerusers=false;
$can_view_all_branches=false;

$tenantRole = $DB->get_record('role', array('shortname' => 'tenantadmin'));
$branchunitAdminRole = $DB->get_record('role', array('shortname' => 'branch_unit_admin'));

if( is_siteadmin() || user_has_role_assignment($USER->id, $tenantRole->id) ) {
	
	$can_view=true;
	$can_add=true;
	$can_edit=true;
	$can_delete=true;
	$can_manage_powerusers=true;
	$can_view_all_branches=true;

} else if( user_has_role_assignment($USER->id, $branchunitAdminRole->id) ) {
	
	$can_view=true;
	$can_add=false;
	$can_edit=false;
	$can_delete=false;
	$can_manage_powerusers=false;
	$can_view_all_branches=false;

}


if($can_edit || $can_delete || $can_manage_powerusers) {
	$has_action=true;
} else {
	$has_action=false;
}
////////

$page=optional_param('page', 0, PARAM_INT);
$perpage=optional_param('perpage', 10, PARAM_INT);
$paging=$page*$perpage;

$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string($prefix . '_frameworks', 'local_hierarchy'));

$url = new moodle_url('/local/hierarchy/nodes/node_framework.php', array('prefix'=>$prefix, 'page'=> $page, 'perpage'=>$perpage));
$PAGE->set_url($url);

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('site_administration', 'local_hierarchy'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('hierarchies', 'local_hierarchy'), new moodle_url('/admin/category.php?category=hierarchy'));
$PAGE->navbar->add(get_string($prefix . '_frameworks', 'local_hierarchy'));

echo $OUTPUT->header();

echo html_writer::start_tag('h2') . get_string($prefix . '_frameworks', 'local_hierarchy') . html_writer::end_tag('h2');
echo html_writer::start_tag('br');

if($can_add) {

	$url = new moodle_url('/local/hierarchy/nodes/node_framework/edit.php', array('prefix'=>$prefix, 'frameworkid'=>0));
	echo $OUTPUT->single_button($url, get_string('add_new_' . $prefix . '_framework', 'local_hierarchy'), 'get');
	//'add new node framework' button 

}


$table=get_table_prefix($prefix) . '_framework';
$total_count=0;
if($can_view && $can_view_all_branches) {

	$result=$DB->get_records($table, array('deleted'=>0), $sort='', $fields='*', $limitfrom=$paging, $limitnum=$perpage); 

	$total_records = $DB->get_records($table, array('deleted'=>0)); 
	$total_count = count($total_records);

} else if( $can_view && !($can_view_all_branches) ) {

	$user=$DB->get_record('user', array('id'=>$USER->id));

	if($user->branch != 0) {
		$result=$DB->get_records($table, array('id'=>$user->branch, 'deleted'=>0));
		$total_count=1;
	} else {
		$result=array();
		$total_count=0;
	}

}

$htmltable = new html_table();
$htmltable->attributes['class'] = 'table table-striped table-hover table-bordered';

if($has_action) {
	$htmltable->head = array(get_string('name', 'local_hierarchy'), get_string($prefix . 's', 'local_hierarchy'), get_string('actions', 'local_hierarchy')); 
} else {
	$htmltable->head = array(get_string('name', 'local_hierarchy'), get_string($prefix . 's', 'local_hierarchy')); 
}


if(count($result) > 0) {

	foreach ($result as $record) {

		$htmlrow = new html_table_row(); 

		$htmlrow->id= $record->id;
	
		$htmlrow->cells[] = new html_table_cell(html_writer::start_tag('a', array('href'=>'node.php?prefix=' . $prefix . '&frameworkid=' . $record->id)) . $record->fullname . html_writer::end_tag('a')); 

		$table=get_table_prefix($prefix);
		$count=$DB->count_records($table, array('frameworkid'=>$record->id, 'deleted'=>0)); 
		$htmlrow->cells[] = new html_table_cell($count); 
		
		if($can_edit) {
			$edit_html = html_writer::start_tag('a', array('href'=>'node_framework/edit.php?prefix=' . $prefix . '&frameworkid=' . $record->id, 'title'=>'Edit Group')) .  html_writer::start_tag('img', array('src'=>'../images/edit.png', 'height'=>'20', 'width'=>'20')) . html_writer::end_tag('a');
		} else {
			$edit_html='';
		}

		if($can_delete) {
			$delete_html = html_writer::start_tag('a', array('href'=>'node_framework/delete.php?prefix=' . $prefix . '&frameworkid=' . $record->id, 'title'=>'Delete Group')) .html_writer::start_tag('img', array('src'=>'../images/delete.png', 'height'=>'20', 'width'=>'20')) . html_writer::end_tag('a');
		} else {
			$delete_html='';
		}

		// if($can_manage_powerusers) {
		// 	$poweruser_html = html_writer::start_tag('a', array('href'=>'../poweruser_assignment/branch/assignment.php?branchid=' . $record->id, 'title'=>'Manage power users')) .  html_writer::start_tag('img', array('src'=>'../images/poweruser_assignment.png', 'height'=>'23', 'width'=>'23')) . html_writer::end_tag('a');
		// } else {
		// 	$poweruser_html='';
		// }

		if($has_action) {
			$htmlrow->cells[] = new html_table_cell($edit_html . "&nbsp" . "&nbsp" . $delete_html . "&nbsp" . "&nbsp" . $poweruser_html);
		} 

		$htmltable->data[] = $htmlrow; //add the row to table

	}
			
	echo html_writer::table($htmltable);
	
	$url = new moodle_url('/local/hierarchy/nodes/node_framework.php', array('prefix'=>$prefix, 'page'=>$page, 'perpage'=>$perpage));
	
	echo $OUTPUT->paging_bar($total_count, $page, $perpage, $url);    

} else {

	if($can_view && $can_view_all_branches) {

		echo html_writer::start_tag('p') . get_string('no_' . $prefix . '_frameworks', 'local_hierarchy') . html_writer::end_tag('p');

	} else if( $can_view && !($can_view_all_branches) ) {
		echo "You are not assigned a branch";
	}

}


echo $OUTPUT->footer();
?>