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
$url = new moodle_url('/local/hierarchy/nodes/node_framework/edit.php', array('prefix'=>$prefix, 'frameworkid'=>$frameworkid));
$PAGE->set_url($url);

if($frameworkid==0) {
	$PAGE->set_title(get_string('add_' . $prefix . '_framework', 'local_hierarchy'));
} else {
	$PAGE->set_title(get_string('edit_' . $prefix . '_framework', 'local_hierarchy'));
}

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('site_administration', 'local_hierarchy'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('hierarchies', 'local_hierarchy'), new moodle_url('/admin/category.php?category=hierarchy'));
$PAGE->navbar->add(get_string($prefix . '_frameworks', 'local_hierarchy'), new moodle_url('/local/hierarchy/nodes/node_framework.php?prefix=locate'));
if($frameworkid==0) {
	$PAGE->navbar->add(get_string('add_' . $prefix . '_framework', 'local_hierarchy'));
} else {
	$PAGE->navbar->add(get_string('edit_' . $prefix . '_framework', 'local_hierarchy'));
}


require_once("$CFG->libdir/formslib.php");
 
class simplehtml_form extends moodleform {
    //Add elements to form
    public function definition() {
       
	    global $frameworkid;
 
        $mform = $this->_form; // Don't forget the underscore! 
 
		//hidden element to store frameworkid
		$mform->addElement('hidden', 'frameworkid', 0);
		$mform->setType('frameworkid', PARAM_NOTAGS); 
 
        $mform->addElement('text', 'fullname', get_string('name', 'local_hierarchy'));
        $mform->setType('fullname', PARAM_NOTAGS); //Set type of element
		$mform->addRule('fullname', get_string('name_required', 'local_hierarchy'), 'required');
		
		$mform->addElement('text', 'idnumber', get_string('idnumber', 'local_hierarchy'));
        $mform->setType('idnumber', PARAM_NOTAGS); //Set type of element
		
		$mform->addElement('editor', 'description', get_string('description', 'local_hierarchy'));
		$mform->setType('description', PARAM_RAW); //may not work without this setType()
		
		if($frameworkid==0){
			$this->add_action_buttons($cancel = true, $submitlabel=get_string('save', 'local_hierarchy'));
		} else {
			$this->add_action_buttons($cancel = true, $submitlabel=get_string('update', 'local_hierarchy'));
		}
		
    }
    //Custom validation should be added here
    function validation($data, $files) {
		
		global $DB, $prefix;
		
		$errors= array();
		
		if($data['idnumber']!='') { //check idnumber only if it's not null
		
			if($data['frameworkid']==0) { //if framework is being added
				
				$table=get_table_prefix($prefix) . '_framework';
				$result=$DB->get_records($table, array('idnumber'=>$data['idnumber'], 'deleted'=>0));
				
				if(count($result) > 0) { //check that idnumber doesn't already exist 
					$errors['idnumber']=get_string('idnumber_already_exists', 'local_hierarchy');
				}
			
			} else { //if framework is being edited 
				
				$table=get_table_prefix($prefix) . '_framework';
				$record=$DB->get_record($table, array('id'=>$data['frameworkid'], 'deleted'=>0));
				
				if($record->idnumber != $data['idnumber']) { //if user is changing the idnumber
					
					$table=get_table_prefix($prefix) . '_framework';
					$result=$DB->get_records($table, array('idnumber'=>$data['idnumber'], 'deleted'=>0));
				
					if(count($result) > 0) { //check if the new idnumber already exists 
						$errors['idnumber']=get_string('idnumber_already_exists', 'local_hierarchy');
					}
					
				}
			}
		
		}
		
        return $errors;
		
	}
}

$mform = new simplehtml_form('edit.php?prefix=' . $prefix . '&frameworkid=' . $frameworkid);

//Form processing and displaying is done here
if ($mform->is_cancelled()) {

	$url = new moodle_url($CFG->wwwroot . '/local/hierarchy/nodes/node_framework.php', array('prefix'=>$prefix));
	redirect($url); //redirect to root if cancel is pressed 
	
} else if ($fromform = $mform->get_data()) {
  //In this case you process validated data. $mform->get_data() returns data posted in form.
   
	$record = new stdclass();
	$time=time();
	$record->fullname = $fromform->fullname;
	$record->idnumber = $fromform->idnumber;
	$record->description = $fromform->description['text'];
  
	if($fromform->frameworkid==0) {
		$record->timecreated = $time;
		$record->usercreated = $USER->id;
	} else {
		$record->timemodified = $time;
		$record->usermodified = $USER->id;
	}
  
	$table=get_table_prefix($prefix) . '_framework'; //table name 
  
	if($fromform->frameworkid==0) { //if framework is being created
		$DB->insert_record($table, $record); 
	} else { //if framework is being edited 
		$record->id=$fromform->frameworkid; //provide the id of the record in the fromform object 
		$DB->update_record($table, $record, $bulk=false); 
	}
  
  
	//redirect after all the data has been processed 
	$url = new moodle_url($CFG->wwwroot . '/local/hierarchy/nodes/node_framework.php', array('prefix'=>$prefix));
	redirect($url);
  
    
  
} else {
  
	if($frameworkid!=0) {
		//if frameworkid is received in get it means a framework is being edited 
		
		$toform = new stdclass();
		
		$toform->frameworkid=$frameworkid; //set the frameworkid value in form's hidden element 
		
		$table=get_table_prefix($prefix) . '_framework';
		$record=$DB->get_record($table, array('id'=>$frameworkid)); 
		
		$toform->frameworkid=$record->id;
		$toform->fullname=$record->fullname;
		$toform->idnumber=$record->idnumber;
		$toform->description['text']=$record->description;
		
		$mform->set_data($toform);
	} 
  
	echo $OUTPUT->header();
	
	if($frameworkid==0) {
		echo html_writer::start_tag('h2') . get_string('add_' . $prefix . '_framework', 'local_hierarchy') . html_writer::end_tag('h2');
		echo html_writer::start_tag('br');
	} else {
		echo html_writer::start_tag('h2') . get_string('edit_' . $prefix . '_framework', 'local_hierarchy') . html_writer::end_tag('h2');
		echo html_writer::start_tag('br');
	}
 
	//displays the form
	$mform->display();


	echo $OUTPUT->footer();  
  
}

?>