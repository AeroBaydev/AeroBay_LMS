<?php


$pocId=$_POST['id'];  //get poc id id as user
global $DB, $OUTPUT, $PAGE,$USER;

if(isset($_POST['add'])) { //if user has selected 'Add' 
	
	if(isset($_POST["potential_select"])) { //if something is selected 

		// Retrieve each selected option 
		$role1 = $DB->get_record_sql("SELECT roleid FROM {role_assignments} ra WHERE ra.userid = $USER->id");
		$timecreated = time();
		$record = new stdClass();
		foreach($_POST['potential_select'] as $school) { //select a user 
			
			
			$record->schoolassignby = $USER->id;
			$record->schoolassignedto = $pocId;
			$record->assigneeroleid = $role1->roleid;
			$record->userid = $pocId;
			$record->schoolid = $school;
			$record->timecreated = $timecreated;
			$record->timemodified = $timecreated;
			$record->usertype= "poc";
			$success = $DB->insert_record('schoolassign', $record);
			
		}	
	
	}
	
}


if(isset($_POST['remove'])) { //if user has selected 'Remove'
	
	if(isset($_POST["existing_select"])) { //if something is selected
		
		// Retrieve each selected option 
		foreach($_POST['existing_select'] as $school) { //select a user  
			
			$DB->delete_records('schoolassign', array('schoolid'=>$school, 'userid'=>$pocId));
			

		}	
	
	}
	
}


?>
