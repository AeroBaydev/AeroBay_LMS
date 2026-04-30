<?php

require('../../../../config.php');
require('../../lib.php');
global $CFG, $DB, $USER;

require_capability('local/hierarchy:manage', context_system::instance(), null, true, "Capability 'Manage hierarchies' required"); //check capability 

$prefix=required_param('prefix', PARAM_TEXT);
$frameworkid=required_param('frameworkid', PARAM_INT);

require_once($CFG->libdir . '/adminlib.php'); 
admin_externalpage_setup($prefix); 

$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('confirmation', 'local_hierarchy'));
$url = new moodle_url('/local/hierarchy/nodes/node_framework/delete.php', array('prefix'=>$prefix, 'frameworkid'=>$frameworkid));
$PAGE->set_url($url);

$PAGE->requires->css(new moodle_url($CFG->wwwroot.'/local/hierarchy/style/confirm.css'));

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('site_administration', 'local_hierarchy'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('hierarchies', 'local_hierarchy'), new moodle_url('/admin/category.php?category=hierarchy'));
$PAGE->navbar->add(get_string($prefix . '_frameworks', 'local_hierarchy'), new moodle_url('/local/hierarchy/nodes/node_framework.php?prefix=locate'));
$PAGE->navbar->add(get_string('confirmation', 'local_hierarchy'));


require_once("$CFG->libdir/formslib.php");

class simplehtml_form extends moodleform {
    //Add elements to form
    public function definition() {
 
        $mform = $this->_form; 
		
		//hidden element to store frameworkid
		$mform->addElement('hidden', 'hiddenframeworkid', 0);
		$mform->setType('hiddenframeworkid', PARAM_NOTAGS); 
		
		$this->add_action_buttons($cancel = true, $submitlabel=get_string('delete', 'local_hierarchy'));
		
    }
    //Custom validation should be added here
    function validation($data, $files) {
		//tags are already removed so no need of validation  
	}
}

$mform = new simplehtml_form('delete.php?prefix=' . $prefix . '&frameworkid=' . $frameworkid);

//Form processing and displaying is done here
if ($mform->is_cancelled()) {

	$url = new moodle_url($CFG->wwwroot . '/local/hierarchy/nodes/node_framework.php', array('prefix'=>$prefix));
	redirect($url); //redirect to root if cancel is pressed 
	
} else if ($fromform = $mform->get_data()) {
  //if user submits (clicks on delete), delete the framework and its nodes 
	
	$table=get_table_prefix($prefix) . '_framework';
	$table2=get_table_prefix($prefix);
	$frameworkid = $fromform->hiddenframeworkid;
	
	delete_framework($table, $table2, $frameworkid); 

	//redirect after data has been processed
	$url = new moodle_url($CFG->wwwroot . '/local/hierarchy/nodes/node_framework.php', array('prefix'=>$prefix)); 
	redirect($url);
  
} else {
	
	$table=get_table_prefix($prefix) . '_framework';
	$record=$DB->get_record($table, array('id'=>$frameworkid)); //get the record to be deleted 
		
	$toform = new stdclass();
	$toform->hiddenframeworkid=$record->id;  //set the frameworkid in form's hidden element
		
	$mform->set_data($toform);
	
	echo $OUTPUT->header();
		
	echo html_writer::start_tag('div', array('class'=>'confirm-div')) .
		
		html_writer::start_tag('h4', array('class'=>'confirm-h4')) . 
			get_string('confirm_heading', 'local_hierarchy') .
		html_writer::end_tag('h4') .
		html_writer::start_tag('p', array('class'=>'confirm-p')) . 
			get_string($prefix . '_confirmation_framework_part_1', 'local_hierarchy') . $record->fullname . get_string($prefix . '_confirmation_framework_part_2', 'local_hierarchy') .
		html_writer::end_tag('p') .
		
		html_writer::end_tag('div') .
		
		html_writer::start_tag('br');
	
		//display the form 
		$mform->display();

	echo $OUTPUT->footer();  
	  
}

?>


