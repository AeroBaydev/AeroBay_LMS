<?php


$armID=$_POST['id']; 
$usertype=$_POST['usertype']; //get poc id id as user
global $DB, $OUTPUT, $PAGE,$USER;

if(isset($_POST['add'])) { //if user has selected 'Add' 
	
	if(isset($_POST["potential_select"])) { //if something is selected 

		// Retrieve each selected option 
		// $role1 = $DB->get_record_sql("SELECT roleid FROM {role_assignments} ra WHERE ra.userid = $USER->id");
		$timecreated = time();
		$record = new stdClass();
		foreach($_POST['potential_select'] as $school) { //select a user 
			
			
			$record->schoolassignby = $USER->id;
			$record->schoolassignedto = $armID;
			// $record->assigneeroleid = $role1->roleid;
			$record->userid = $armID;
			$record->schoolid = $school;
			$record->usertype =$usertype;
			$record->timecreated = $timecreated;
			$record->timemodified = $timecreated;
	
			$success = $DB->insert_record('schoolassign', $record);
			
		}	
	
	}
	
}


if(isset($_POST['remove'])) { //if user has selected 'Remove'
	
	if(isset($_POST["existing_select"])) { //if something is selected
		
		// Retrieve each selected option 
		foreach($_POST['existing_select'] as $school) { //select a user  
			
			$schoolassignid=$DB->get_record('schoolassign', array('schoolid'=>$school, 'userid'=>$armID,'usertype'=>$usertype));
				if($schoolassignid){
			$DB->delete_records('schoolassign', array('id' => $schoolassignid->id));
		}
			

		}	
	
	}
	
}


?>
