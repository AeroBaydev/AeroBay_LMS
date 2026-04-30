<?php

require('../../../../config.php');
require('../../lib.php');
global $CFG, $DB, $USER;

require_capability('local/hierarchy:manage', context_system::instance(), null, true, "Capability 'Manage hierarchies' required"); //check capability 

$prefix=required_param('prefix', PARAM_TEXT);
$nodeid=required_param('nodeid', PARAM_INT);
$frameworkid=required_param('frameworkid', PARAM_INT);

require_once($CFG->libdir . '/adminlib.php'); 
admin_externalpage_setup($prefix); 

$PAGE->set_context(context_system::instance());
$url = new moodle_url('/local/hierarchy/nodes/node/display.php', array('prefix'=>$prefix, 'nodeid'=>$nodeid, 'frameworkid'=>$frameworkid));
$PAGE->set_url($url);

$table=get_table_prefix($prefix);
$record=$DB->get_record($table, array('id'=>$nodeid));
$PAGE->set_title($record->fullname);

$PAGE->requires->css(new moodle_url($CFG->wwwroot.'/local/hierarchy/style/node_display.css'));

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('site_administration', 'local_hierarchy'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('hierarchies', 'local_hierarchy'), new moodle_url('/admin/category.php?category=hierarchy'));
$PAGE->navbar->add(get_string($prefix . '_frameworks', 'local_hierarchy'), new moodle_url('/local/hierarchy/nodes/node_framework.php?prefix=locate'));
$table=get_table_prefix($prefix) . '_framework';
$nav_record=$DB->get_record($table, array('id'=>$frameworkid));
$PAGE->navbar->add($nav_record->fullname, new moodle_url('/local/hierarchy/nodes/node.php', array('prefix'=>$prefix, 'frameworkid'=>$frameworkid)));
$table=get_table_prefix($prefix);
$nav_record=$DB->get_record($table, array('id'=>$nodeid));
$PAGE->navbar->add($nav_record->fullname);

$table=get_table_prefix($prefix) . '_framework';
$record2=$DB->get_record($table, array('id'=>$frameworkid));

if($record->parentid==0) { //if node is at root level
	//create a fake parent with name as top 
	$parent = new stdclass();
	$parent->fullname = get_string('top', 'local_hierarchy');
} else {
	//get the parent node 
	$table=get_table_prefix($prefix);
	$parent=$DB->get_record($table, array('id'=>$record->parentid));
} 


echo $OUTPUT->header();


if($record->idnumber!='') { 
	$get_idnumber=get_string('idnumber', 'local_hierarchy');
	$idnumber=$record->idnumber;
} else { //don't show idnumber if it's null 
	$get_idnumber='';
	$idnumber='';
}

if($record->description!='') { 
	$get_description=get_string('description', 'local_hierarchy');
	$description=$record->description;
} else { //don't show description if it's null 
	$get_description='';
	$description='';
}


echo html_writer::start_tag('a', array('href'=>new moodle_url('../node.php',array('prefix'=>$prefix, 'frameworkid'=>$frameworkid)))) . 
	html_writer::start_tag('font', array('size'=>'2')) .
		"&lt&lt&nbsp" .		get_string('back_to', 'local_hierarchy') . "&nbsp" . $record2->fullname .
	html_writer::end_tag('font') .
html_writer::end_tag('a') .


html_writer::start_tag('div', array('class'=>'div-heading')) .

	html_writer::start_tag('h2') .
		$record2->fullname . " - " . $record->fullname .
		html_writer::start_tag('a', array('class'=>'a-heading', 'href'=>'edit.php?prefix=' . $prefix . '&nodeid=' . $record->id . '&frameworkid=' . $record->frameworkid)) .
			html_writer::start_tag('img', array('src'=>'../../images/edit.png', 'height'=>'25', 'width'=>'25')) .
		html_writer::end_tag('a') .	
	html_writer::end_tag('h2') .
	//don't use absolute path in src in img tag 
	
	html_writer::start_tag('dl') .
	
		html_writer::start_tag('dt') .
			get_string('name', 'local_hierarchy') .
		html_writer::end_tag('dt') .
		
		html_writer::start_tag('dd') .
			$record->fullname .
		html_writer::end_tag('dd') .
		
		html_writer::start_tag('dt') .
			$get_idnumber .
		html_writer::end_tag('dt') .
		
		html_writer::start_tag('dd') .
			$idnumber .
		html_writer::end_tag('dd') .
		
		html_writer::start_tag('dt') .
			$get_description .
		html_writer::end_tag('dt') .
		
		html_writer::start_tag('dd') .
			$description .
		html_writer::end_tag('dd') .
		
		html_writer::start_tag('dt') .
			get_string('parent', 'local_hierarchy') .
		html_writer::end_tag('dt') .
		
		html_writer::start_tag('dd') .
			$parent->fullname .
		html_writer::end_tag('dd') .
	
	html_writer::end_tag('dl') .
			
html_writer::end_tag('div');

echo $OUTPUT->footer();

?>