<?php
require_once($CFG->dirroot.'/cohort/lib.php');

$pdeptid=$_POST['deptid'];
$cohortid=$_POST['cohortid'];


if(isset($_POST['add'])) { //if user has selected 'Add' 
	
	if(isset($_POST["potential_select"])) { //if something is selected 
		
		// Retrieve each selected option 
		foreach($_POST['potential_select'] as $userid) { //select a user 
			
 
		 add_user_to_dept($userid, $pdeptid);
		 if($cohortid){
         cohort_add_member($cohortid, $userid);
			}

		}	
	
	}
	
}


if(isset($_POST['remove'])) { //if user has selected 'Remove'
	
	if(isset($_POST["existing_select"])) { //if something is selected
		
		// Retrieve each selected option 
		foreach($_POST['existing_select'] as $userid) { //select a user  
			
			remove_user_from_branch_and_dept($userid, $pdeptid);
				if($cohortid){
			cohort_remove_member($cohortid,$userid); 
		}
			

		}	
	
	}
	
}


?>
