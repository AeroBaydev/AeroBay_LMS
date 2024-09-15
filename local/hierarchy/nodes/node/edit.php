<?php

require('../../../../config.php');
require('../../lib.php');
global $CFG, $DB, $USER;

require_capability('local/hierarchy:manage', context_system::instance(), null, true, "Capability 'Manage hierarchies' required"); //check capability 
require_once ($CFG->libdir . '/externallib.php'); 
require_once "{$CFG->libdir}/externallib.php";
require_once($CFG->dirroot.'/cohort/lib.php');

$prefix=required_param('prefix', PARAM_TEXT);
$nodeid=required_param('nodeid', PARAM_INT);
$frameworkid=required_param('frameworkid', PARAM_INT);

require_once($CFG->libdir . '/adminlib.php'); 
admin_externalpage_setup($prefix); 

$PAGE->set_context(context_system::instance());
$url = new moodle_url('/local/hierarchy/nodes/node/edit.php', array('prefix'=>$prefix, 'nodeid'=>$nodeid, 'frameworkid'=>$frameworkid));
$PAGE->set_url($url);



if($nodeid==0) {
	$PAGE->set_title(get_string('add_' . $prefix, 'local_hierarchy'));
} else {
	$PAGE->set_title(get_string('edit_' . $prefix, 'local_hierarchy'));
}

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('site_administration', 'local_hierarchy'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('hierarchies', 'local_hierarchy'), new moodle_url('/admin/category.php?category=hierarchy'));
$PAGE->navbar->add(get_string($prefix . '_frameworks', 'local_hierarchy'), new moodle_url('/local/hierarchy/nodes/node_framework.php?prefix=locate'));
$table=get_table_prefix($prefix) . '_framework';
$nav_record=$DB->get_record($table, array('id'=>$frameworkid));
$PAGE->navbar->add($nav_record->fullname, new moodle_url('/local/hierarchy/nodes/node.php', array('prefix'=>$prefix, 'frameworkid'=>$frameworkid)));
if($nodeid==0) {
	$PAGE->navbar->add(get_string('add_' . $prefix, 'local_hierarchy'));
} else {
	$PAGE->navbar->add(get_string('edit_' . $prefix, 'local_hierarchy'));
}

require_once("$CFG->libdir/formslib.php");
 
class simplehtml_form extends moodleform {
    //Add elements to form 
    public function definition() {
       
	    global $DB, $prefix, $frameworkid, $nodeid;
 
        $mform = $this->_form; // Don't forget the underscore! 
		
		
		//hidden element to store nodeid
		$mform->addElement('hidden', 'hiddennodeid', 0);
		$mform->setType('hiddennodeid', PARAM_NOTAGS); 
		
		
		$table=get_table_prefix($prefix) . '_framework';
		$record=$DB->get_record($table, array('id'=>$frameworkid));
		$mform->addElement('static', 'framework', get_string($prefix . '_framework', 'local_hierarchy'), $record->fullname);
		//uneditable name of framework
		
		
		$node_array=array(0=>get_string('top', 'local_hierarchy')); //add top with id=0 as parent of root level nodes 
		
		$table=get_table_prefix($prefix);
		add_nodes_in_select_array($table, $nodeid, $frameworkid, $node_array);
		//this function will append nodes in the array that will be displayed in the select element to choose parent 
		//$node_array is passed through reference 
		//function handles both the cases of node being added and edited 
	
		$mform->addElement('select', 'parent_select', get_string('parent', 'local_hierarchy'), $node_array);
		
		
        $mform->addElement('text', 'fullname', get_string('name', 'local_hierarchy'));
        $mform->setType('fullname', PARAM_NOTAGS); //Set type of element
		$mform->addRule('fullname', get_string('name_required', 'local_hierarchy'), 'required');
		
		$mform->addElement('text', 'idnumber', get_string('idnumber', 'local_hierarchy'));
        $mform->setType('idnumber', PARAM_NOTAGS); //Set type of element
		
		$mform->addElement('editor', 'description', get_string('description', 'local_hierarchy'));
		$mform->setType('description', PARAM_RAW); //may not work without this setType()
		
		if($nodeid==0){
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
		
			if($data['hiddennodeid']==0){ //if node is being added
				
				$table=get_table_prefix($prefix);
				$result=$DB->get_records($table, array('idnumber'=>$data['idnumber'], 'deleted'=>0));
				
				if(count($result) > 0) { //check that idnumber doesn't already exist 
					$errors['idnumber']=get_string('idnumber_already_exists', 'local_hierarchy');
				}
			
			} else { //if node is being edited 
				
				$table=get_table_prefix($prefix);
				$record=$DB->get_record($table, array('id'=>$data['hiddennodeid'], 'deleted'=>0));
				
				if($record->idnumber != $data['idnumber']) { //if user is changing the idnumber
					
					$table=get_table_prefix($prefix);
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


$mform = new simplehtml_form('edit.php?prefix=' . $prefix . '&frameworkid=' . $frameworkid . '&nodeid=' . $nodeid);
//when form is submitted, send the prefix, frameworkid and nodeid in the url 


if ($mform->is_cancelled()) {
	//if form is cancelled 

	$fromform = $mform->get_data();

	$url = new moodle_url($CFG->wwwroot . '/local/hierarchy/nodes/node.php', array('prefix'=>$prefix, 'frameworkid'=>$frameworkid));
	redirect($url); //redirect to root if cancel is pressed 
	
} else if ($fromform = $mform->get_data()) {
  //In this case you process validated data. $mform->get_data() returns data posted in form.
	
	if($fromform->parent_select == 0) { //if top is selected as parent 
		//create a fake parent for the top level nodes 
		$parent = new stdclass();
        $parent->id = 0;
        $parent->path = '';
        $parent->depthlevel = 0;
	} else{	
		$table=get_table_prefix($prefix);
		$parent=$DB->get_record($table, array('id'=>$fromform->parent_select)); //find the node to be set as parent 
	}
	
	$record = new stdclass();
	$record->fullname = $fromform->fullname;   //set name
	$record->idnumber = $fromform->idnumber;   //set idnumber
	$record->description = $fromform->description['text'];   //set description
	$record->frameworkid = $frameworkid;    //set framework
	$record->parentid = $parent->id;     //set parent
	
	$time=time();
	if($fromform->hiddennodeid==0) {  //hiddennodeid element having default value means user is being added 
		$record->timecreated = $time;
		$record->usercreated = $USER->id;
	} else {  //if hiddennodeid element has value set, it means user is being edited 
		$record->timemodified = $time;
		$record->usermodified = $USER->id;
	}
	
	//we will set depthlevel and path while inserting/updating 
	
	if($fromform->hiddennodeid==0) { //insert record
	 
		$record->depthlevel = $parent->depthlevel + 1;    //change depthlevel 
		$table=get_table_prefix($prefix);
		$newid = $DB->insert_record($table, $record);  //insert the record and get the id 
		$DB->set_field($table, 'path', $parent->path . '/' . $newid, array('id' => $newid));   //can't set path until we know the id

		$cohort = new stdClass();
        $cohort->name = $fromform->fullname; 
         $cohort->contextid = \context_system::instance()->id;
         $cohort->idnumber = $fromform->idnumber;
         $cohort->description =  $fromform->description['text'];
         $cohort->descriptionformat = FORMAT_HTML;
         $newcohort = cohort_add_cohort($cohort);


				 


		
	} else { //update record 
		
		$table=get_table_prefix($prefix);
		$record_check=$DB->get_record($table, array('id'=>$fromform->hiddennodeid)); //get old parent 
		
		if($record_check->parentid != $record->parentid){ // if parent has been changed 
		
			//change paths and depthlevels of the node and all its descendents 
			
			update_path_and_depthlevel_of_node_and_descendents($table, $frameworkid, $fromform->hiddennodeid, $parent);
				
		}		
		
		$record->id=$fromform->hiddennodeid; //provide the id in the record 
		$DB->update_record($table, $record, $bulk=false); //update the record 
		
	}
	
	
	//redirect after all the data has been processed
	$url = new moodle_url($CFG->wwwroot . '/local/hierarchy/nodes/node.php', array('prefix'=>$prefix, 'frameworkid'=>$frameworkid));
	redirect($url);
  
  
} else {
  
	if($nodeid!=0) {
		//if nodeid is received in get it means a node is being edited 
		
		$toform = new stdclass(); 
		
		$table=get_table_prefix($prefix);
		$record=$DB->get_record($table, array('id'=>$nodeid)); 
		
		$toform->hiddennodeid=$record->id;  //set the nodeid value in form's hidden element
		$toform->parent_select=$record->parentid;  		
		$toform->fullname=$record->fullname;
		$toform->idnumber=$record->idnumber;
		$toform->description['text']=$record->description;
		
		$mform->set_data($toform);
	} 
  
	echo $OUTPUT->header();
	
	if($nodeid==0) {
		echo html_writer::start_tag('h2') . get_string('add_' . $prefix, 'local_hierarchy') . html_writer::end_tag('h2') . html_writer::start_tag('br');
	} else {
		echo html_writer::start_tag('h2') . get_string('edit_' . $prefix, 'local_hierarchy') . html_writer::end_tag('h2') . html_writer::start_tag('br');
	}


	$mform->display();

	echo $OUTPUT->footer();  
  
}

?>