<?php


$errors = array(); //array to store error messages 

if(isset($_POST['add'])) { //if user has selected 'Add' 
	
	if(isset($_POST["potential_select"])) { //if something is selected 
		
		$roleid = $_POST['roleid'];
		$timestart = time();
		
		$timeend = 0;
		
		if($_POST['period']!=0) {
			$timeend = $timestart + $_POST['period'];
		}
		
		$users = $DB->get_records('user_dept_enrolments', array('deptid'=>$_POST['deptid'])); //get all users in that dept. These have to be enrolled. 
		
		// Retrieve each selected option 
		foreach($_POST['potential_select'] as $courseid) { //select a course 
		
			$check = $DB->record_exists('enrol', array('courseid'=>$courseid, 'enrol'=>'manual')); //check if manual enrolment is added for that course 
		
			if($check==true) { //if manual enrolment is added for the course 
				
				foreach($users as $user) { //iterate through users 
					
					enrol($courseid, $user->userid, $roleid, $timestart, $timeend); //enrol the users 
					
				}
				
				$enrol = $DB->get_record('enrol', array('courseid'=>$courseid, 'enrol'=>'manual'), 'id'); //get record for manual enrolment of the course 
				
				$record = new stdclass();
				
				$record->enrolid = $enrol->id;
				$record->deptid = $_POST['deptid'];
				$record->roleid = $roleid;
				$record->timestart = $timestart;
				$record->timeend = $timeend;
				$record->modifierid = $USER->id;
				$record->timecreated = time();
				$record->timemodified = time();
				
				$DB->insert_record('dept_enrolments', $record, $returnid=false, $bulk=false);
				//after all users of dept are enrolled in the course, make entry of that course in 'dept_enrolments' table 
				
			} else {
				
				$course = $DB->get_record('course', array('id'=>$courseid)); //get course record 
				
				$errors[] = "Manual enrolment is not added for course " . $course->fullname . "<br>";
				//add error message in errors array 
			}
			
		
		}	
	
	}
	
}


if(isset($_POST['remove'])) { //if user has selected 'Remove' 
	
	if(isset($_POST["existing_select"])) { //if something is selected 
		
		$users = $DB->get_records('user_dept_enrolments', array('deptid'=>$_POST['deptid'])); //get all users in that dept. These have to be unenrolled. 
		
		//Deleted users are automatically unenrolled from all courses by moodle. 
		
		// Retrieve each selected option 
		foreach($_POST['existing_select'] as $courseid) { //select a course 
		
			//No need to check if course has 'manual enrolment' added (if manual enrolment is deleted for a course, we delete its corresponding record in 'dept_enrolments' as well, so it won't show in list of enrolled courses) 
		
			foreach($users as $user) { //select a user 
				
				unenrol($courseid, $user->userid); //unenrol the users 
				
			}
			
			$enrol = $DB->get_record('enrol', array('courseid'=>$courseid, 'enrol'=>'manual'), 'id'); //get record for manual enrolment of the course 
			
			$DB->delete_records('dept_enrolments', array('enrolid'=>$enrol->id, 'deptid'=>$_POST['deptid'])); 
			//after all users of dept are unenrolled from the course, delete entry of that course from dept_enrolments table 
		
		}	
	
	}
	
}


?>
