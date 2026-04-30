<?php

defined('MOODLE_INTERNAL') || die;

	
function get_table_prefix($prefix) {
	//table prefixes added here 
	
	if($prefix=='locate') {
		return 'loc';
	}
	
}


function update_path_and_depthlevel_of_node_and_descendents($table, $frameworkid, $nodeid, $parent) {
//function to update the path and depthlevel of a node and its descendents. To be used when changing the parent of the node. 

	global $DB;
	
	$oldrecord = $DB->get_record($table, array('id'=>$nodeid, 'deleted'=>0)); //get the old node 

	if($oldrecord->depthlevel==1) {
		//if node was toplevel then create a fake parent for it
		$oldparent = new stdclass();
        $oldparent->id = 0;
        $oldparent->path = '';
        $oldparent->depthlevel = 0;
	} else {
		$oldparent = $DB->get_record($table, array('id'=>$oldrecord->parentid, 'deleted'=>0)); //get the old parent
	}
	  
	$depthchange = $parent->depthlevel + 1 - $oldrecord->depthlevel; //this will be the depth level change for the node and all its descendents (new depth - old depth)
			
	//moodle functions to get sql texts for calculations 
	$length_sql = $DB->sql_length("'$oldparent->path'"); //get length of the old parent's path
	$substr_sql = $DB->sql_substr('path', "{$length_sql} + 1"); //take the part of path after that length
	$updatepath = $DB->sql_concat("'{$parent->path}'", $substr_sql); //prepend new parent path to that substring, thus forming the new path. $parent is the new parent. 

	$params = array(
	'recordpath'   => $oldrecord->path,            //change path of the node itself
	'descendentpath'  => "$oldrecord->path/%",        //change path of the descendents
	'frameworkid' => $frameworkid);		 //verify frameworkid 

	$sql = "UPDATE {{$table}}
	SET path = $updatepath, depthlevel = depthlevel + $depthchange
	WHERE (path = :recordpath OR
	" . $DB->sql_like('path', ':descendentpath') . ")
	AND frameworkid = :frameworkid";

	$DB->execute($sql, $params);
}


function make_child_nodes_list($table, $frameworkid, $nodeid, &$list, $index=null) { 
//function to make a hierarchical list of a node's children 

//index is null by default 
//pass a reference to the list so that changes are made to the list that is passed 

	global $DB;

	if($index==null) { //function called for first time (not through recursion)
		
		$index = array(); //creat a 2-D array that will map parents to children. index[$record->parentid][]=$record 
	
		$result=$DB->get_records($table, array('frameworkid'=>$frameworkid, 'deleted'=>0)); 
		//get only those nodes that belong to the framework that is selected and are not deleted 

		if (count($result) > 0)
		{
			foreach ($result as $record) {
				$parentid = $record->parentid;  //create the index 
				$index[$parentid][] = $record;  //index contains the records 
			}
		}
	
	}  //all this happens only for the first call to the function 
	
	if(isset($index[$nodeid])) { //if any children exist for that node in index 
		foreach ($index[$nodeid] as $record){ //iterate through the node's children 
			
			$list[]=$record;  //append child to the list 
			make_child_nodes_list($table, $frameworkid, $record->id, $list, $index); //if that child has children, append them first (recursively)
			
			//when done with this child and its descendents, continue with the siblings of the child 
		
		}
	}
	
}


function node_and_descendents($table, $frameworkid, $nodeid) {
	//function to return a node and its descendents 
	
	global $DB;
	
	$record=$DB->get_record($table, array('frameworkid'=>$frameworkid, 'id'=>$nodeid, 'deleted'=>0)); //get the record 
	
	$params = array($record->path,        //path of the node itself
	"$record->path/%",        //path pattern of descendents
	$frameworkid,		//verify frameworkid 
	0);		 

	$sql = "SELECT * from {{$table}}
	WHERE (path = ? || path LIKE ?) AND frameworkid = ? AND deleted = ?";

	$result=$DB->get_records_sql($sql, $params);
	
	return $result;
}


function delete_node_and_descendents($table, $frameworkid, $nodeid) {
	//function to delete a node and its descendents 
	
	global $DB;
	
	$record=$DB->get_record($table, array('frameworkid'=>$frameworkid, 'id'=>$nodeid, 'deleted'=>0)); //get the record being deleted 
	
	$params = array($record->path,         //path of the node itself
	"$record->path/%",        //path pattern of descendents
	$frameworkid,		//verify frameworkid 
	0);		 

	$sql="SELECT id FROM {{$table}} WHERE (path = ? || path LIKE ?) AND frameworkid = ? AND deleted = ?";
	$result = $DB->get_records_sql($sql, $params); //get id of node and its descendents 
	
	foreach($result as $record) { //for node and its descendents
		
		///////////////////////////(1)
		/*
		$users=$DB->get_records('user_dept_enrolments', array('deptid'=>$record->id));
		
		foreach($users as $user) {
			remove_user_from_dept($user->userid, $record->id);
		}
		*/
		///////////////////////////(1)
		

		//////////////////////////(2)
		//remove poweruser status from the users of that dept (if any user has them)
		$result2=$DB->get_records('user', array('dept'=>$record->id)); //get users of dept
		foreach($result2 as $record2) { //loop through users of dept

			$poweruser_shortnames=array('dept_unit_admin', 'dept_enrollment_admin', 'dept_reporting_admin'); //shortnames of poweruser roles, take only those roles that are related to dept
			foreach($poweruser_shortnames as $shortname) {

				if( $DB->record_exists('role', array('shortname'=>$shortname)) ) {
					$role = $DB->get_record('role', array('shortname'=>$shortname)); 
					$context = context_system::instance();
					role_unassign($role->id, $record2->id, $context->id); //no error given if user doesn't actually have the role
				}

			}
			$sql = "UPDATE {user} SET deptpoweruser=0 WHERE id=$record2->id";
			$DB->execute($sql); //update the user table
			//remove any poweruser roles assigned and reset 'branchpoweruser' and 'deptpoweruser' fields

		}
		
		//when done with all users only then update branch and dept of users (sequence of execution matters, it's not possible to first update the dept of users and then find users of the dept)
		$sql="UPDATE {user} SET dept=0 WHERE dept= $record->id ";
		$DB->execute($sql);

		$DB->delete_records('user_dept_enrolments', array('deptid'=>$record->id)); //delete entries
		//////////////////////////////(2)


		$DB->delete_records('dept_enrolments', array('deptid'=>$record->id)); 
		$DB->delete_records('dept_category_enrolments', array('deptid'=>$record->id)); 
		//delete entries of dept from other tables
		
		
		//right now we are not unenrolling users from courses when a dept is deleted. To do that also, uncomment (1) and comment (2)
		
	}

	//after everything is done
	$sql="UPDATE {{$table}} SET deleted=1 WHERE (path = ? || path LIKE ?) AND frameworkid = ?";
	$DB->execute($sql, $params); //delete the node and descendents by setting 'deleted=1' (don't delete the records) 
	
}


function delete_framework($table, $table2, $frameworkid) {
	//function to delete a framework 
	
	global $DB;
	
	$depts = $DB->get_records($table2, array('frameworkid'=>$frameworkid, 'deleted'=>0, 'depthlevel'=>1)); //get all toplevel depts in that branch

	foreach($depts as $dept) {
		delete_node_and_descendents($table2, $frameworkid, $dept->id);
		//call function for all top level nodes, this deletes all nodes
	}


	/////////////////// the above part will clear everything related to depts, but will not work for users that have only branch but no dept, also branchpoweruser roles are not removed by that

	//remove poweruser status from the users
	$result2=$DB->get_records('user', array('branch'=>$frameworkid)); 
	foreach($result2 as $record2) { 

		$poweruser_shortnames=array('dept_unit_admin', 'dept_enrollment_admin', 'dept_reporting_admin', 'branch_unit_admin', 'branch_enrollment_admin', 'branch_reporting_admin'); //shortnames of poweruser roles
		foreach($poweruser_shortnames as $shortname) {

			if( $DB->record_exists('role', array('shortname'=>$shortname)) ) {
				$role = $DB->get_record('role', array('shortname'=>$shortname)); 
				$context = context_system::instance();
				role_unassign($role->id, $record2->id, $context->id); //no error given if user doesn't actually have the role
			}

		}
		$sql = "UPDATE {user} SET branchpoweruser=0, deptpoweruser=0 WHERE id=$record2->id";
		$DB->execute($sql); //update the user table
		//remove any poweruser roles assigned and reset 'branchpoweruser' and 'deptpoweruser' fields

	}
	
	//when done with all users only then update branch and dept of users (sequence of execution matters, it's not possible to first update the branch of users and then find users of the branch)
	$sql="UPDATE {user} SET branch=0, dept=0 WHERE branch= $frameworkid";
	$DB->execute($sql);

	//after everything is done
	$sql="UPDATE {{$table}} SET deleted=1 WHERE id=$frameworkid";
	$DB->execute($sql); //delete the framework by setting 'deleted=1' (don't delete the record) 
	
}


function add_nodes_in_select_array($table, $nodeid, $frameworkid, &$node_array) {
//this function will append nodes in the array that will be displayed in the select element to choose parent 
//$node_array is passed through reference 
//function handles both the cases of node being added and edited 
	
	if($nodeid==0) { //if node is being added, provide all nodes for parent option 
		
		$list = array();  
		
		make_child_nodes_list($table, $frameworkid, 0, $list); //get hierarchy of all nodes 
		
		if(count($list) > 0) {
			foreach ($list as $record) { //take node from list
				
				$space="";
				for($i=1; $i<$record->depthlevel; $i++) {$space .= "&nbsp;&nbsp;&nbsp;";}
				//add spaces before name to show hierarchy within the select menu 
							
				$text = $space . $record->fullname;
				$node_array += array($record->id=>$text); //append the options in the array 
			}
		}
		
	} else { //else, if node is being edited, provide all nodes except the node itself and its descendents 
		
		$result=node_and_descendents($table, $frameworkid, $nodeid); //get the node and its descendents 
		//these are not to be displayed in the parent menu 
			
		$list = array();  
		make_child_nodes_list($table, $frameworkid, 0, $list); //get hierarchy of all nodes 
		
		if(count($list) > 0) {
			foreach ($list as $record) { //take node from list

				$flag=true; //set flag to true 
			
				foreach ($result as $result_record) { //go through each node that is not to be displayed 
				
					if($record->id == $result_record->id) { //set flag as false if node matches any rejected node 
						$flag=false;
					}		
				}
				
				if($flag==true) { //if node has not been rejected then append it to array 
					$space="";
					for($i=1; $i<$record->depthlevel; $i++) {$space .= "&nbsp;&nbsp;&nbsp;";}
					//add spaces before name to show hierarchy within the select menu 
							
					$text = $space . $record->fullname;
					$node_array += array($record->id=>$text); //append the nodes in the array 
				}		
			}
		}
		
	} //else
	
} //function


function enrol($courseid, $userid, $roleid, $timestart, $timeend=0)  { //default timeend=0 
	//function to enrol user 
	
	global $DB;

	$instance = $DB->get_record('enrol', array('courseid'=>$courseid, 'enrol'=>'manual'), '*');

	$enrol_manual = enrol_get_plugin('manual');
	
	$enrol_manual->enrol_user($instance, $userid, $roleid, $timestart, $timeend);
	//for unlimited enrolment, timeend is 0 
	//if user already exists then he is updated with these values  
}


function unenrol($courseid, $userid) {
	//function to unenrol user 
	
	global $DB;
	
	$instance = $DB->get_record('enrol', array('courseid'=>$courseid, 'enrol'=>'manual'), '*');
	
	$enrol_manual = enrol_get_plugin('manual');
	
	$enrol_manual->unenrol_user($instance, $userid);
	//if user is already unenrolled (not found enrolled), then nothing happens (no error thrown) 
	
}


function add_user_to_dept($userid, $deptid) {
	//function to add user to dept (and hence branch)
	
	//function will make entry of user in 'user_dept_enrolments' table, update the 'user' table and enrol the user in all courses that the dept is enrolled in
	
	global $DB,$CFG,$USER;
	
	$entry = new stdClass();
	$entry->deptid = $deptid;
	$entry->userid = $userid;
	$entry->modifierid = $USER->id;
	$entry->timecreated	= time();
	$entry->timemodified = time();
	
	$DB->insert_record('user_dept_enrolments', $entry); //make entry in table 

	$deptrecord = $DB->get_record('loc', array('id'=>$deptid));
	$sql = "UPDATE {user} SET branch=$deptrecord->frameworkid, dept=$deptid WHERE id=$userid";
	$DB->execute($sql); //update the user table
	
	$sql1 = "SELECT e.courseid FROM {enrol} AS e INNER JOIN {dept_enrolments} AS d ON e.id = d.enrolid WHERE d.deptid=$deptid"; //query to get courseids of all courses that the dept is enrolled in 
	
	$courseid_records = $DB->get_records_sql($sql1);
	
	foreach($courseid_records as $courseid_record) {
		
		$courseid = $courseid_record->courseid;
		
		$enrol_instance = $DB->get_record('enrol', array('courseid'=>$courseid, 'enrol'=>'manual'));
		//manual enrol instance would surely exist so no need to check (if it is deleted for a course, we delete its corresponding record in 'dept_enrolments' as well using '\core\event\enrol_instance_deleted' event) 
		
		$record = $DB->get_record('dept_enrolments', array('deptid'=>$deptid, 'enrolid'=>$enrol_instance->id));
		
		enrol($courseid, $userid, $record->roleid, $record->timestart, $record->timeend);
		//enrol the user 
	}
	
}


function remove_user_from_dept($userid, $deptid) {
	//function to remove user from dept 
	
	//function will delete entry of user from 'user_dept_enrolments' table, update the 'user' table (dept field only), reset its poweruser functionality (of depts only) and unenrol the user from all courses that the dept is enrolled in 
	
	global $DB,$CFG,$USER;
	
	$DB->delete_records('user_dept_enrolments', array('userid'=>$userid, 'deptid'=>$deptid)); // delete entry from table 

	$sql = "UPDATE {user} SET dept=0 WHERE id=$userid";
	$DB->execute($sql); //update the user table
	
	$sql1 = "SELECT e.courseid FROM {enrol} AS e INNER JOIN {dept_enrolments} AS d ON e.id = d.enrolid WHERE d.deptid=$deptid"; //query to get courseids of all courses that the dept is enrolled in 
	
	$courseid_records = $DB->get_records_sql($sql1);
	
	foreach($courseid_records as $courseid_record) {
		
		$courseid = $courseid_record->courseid;
		
		unenrol($courseid, $userid); //unenrol user from course 
		
	}


	$poweruser_shortnames=array('dept_unit_admin', 'dept_enrollment_admin', 'dept_reporting_admin'); //shortnames of poweruser roles, take only those roles that are related to dept

	foreach($poweruser_shortnames as $shortname) {

		if( $DB->record_exists('role', array('shortname'=>$shortname)) ) {
			$role = $DB->get_record('role', array('shortname'=>$shortname)); 
			$context = context_system::instance();
			role_unassign($role->id, $userid, $context->id); //no error given if user doesn't actually have the role
		}

	}
	
	$sql = "UPDATE {user} SET deptpoweruser=0 WHERE id=$userid";
	$DB->execute($sql); //update the user table

	//remove any poweruser roles assigned and reset 'deptpoweruser' field
	
}


function remove_user_from_branch_and_dept($userid, $deptid) {
	//function to remove user from branch and dept
	
	//function will delete entry of user from 'user_dept_enrolments' table, update the 'user' table, reset its poweruser functionality and unenrol the user from all courses that the dept is enrolled in 
	
	//function named 'add_user_to_branch_and_dept()' doesn't exist because it is already done by add_user_to_dept(). User added to dept will be obviously added to the branch also.

	//Since we are not doing anything (yet) when a user is added just to the branch, function add_user_to_branch() is not needed
	
	global $DB,$CFG,$USER;
	
	$DB->delete_records('user_dept_enrolments', array('userid'=>$userid, 'deptid'=>$deptid)); // delete entry from table 

	$sql = "UPDATE {user} SET branch=0, dept=0 WHERE id=$userid";
	$DB->execute($sql); //update the user table
	
	$sql1 = "SELECT e.courseid FROM {enrol} AS e INNER JOIN {dept_enrolments} AS d ON e.id = d.enrolid WHERE d.deptid=$deptid"; //query to get courseids of all courses that the dept is enrolled in 
	
	$courseid_records = $DB->get_records_sql($sql1);
	
	foreach($courseid_records as $courseid_record) {
		
		$courseid = $courseid_record->courseid;
		
		unenrol($courseid, $userid); //unenrol user from course 
		
	}


	$poweruser_shortnames=array('dept_unit_admin', 'dept_enrollment_admin', 'dept_reporting_admin', 'branch_unit_admin', 'branch_enrollment_admin', 'branch_reporting_admin'); //shortnames of poweruser roles

	foreach($poweruser_shortnames as $shortname) {

		if( $DB->record_exists('role', array('shortname'=>$shortname)) ) {
			$role = $DB->get_record('role', array('shortname'=>$shortname)); 
			$context = context_system::instance();
			role_unassign($role->id, $userid, $context->id); //no error given if user doesn't actually have the role
		}

	}
	
	$sql = "UPDATE {user} SET branchpoweruser=0, deptpoweruser=0 WHERE id=$userid";
	$DB->execute($sql); //update the user table

	//remove any poweruser roles assigned and reset 'branchpoweruser' and 'deptpoweruser' fields
	
}


function set_child_nodes_list($result_set, $nodeid, $depth, &$list, $index=null) {
	//function to make hierarchy in select menu in dept_category_assignment

	//records in $result_set should have a field 'parent' containing the parentid

	//index is null by default 
	//pass a reference to the list so that changes are made to the list that is passed

	global $DB;


	if($index==null) { //function called for first time (not through recursion)


		$index = array(); //create a 2-D array that will map parents to children
	
		if(count($result_set) > 0) {

			foreach($result_set as $record) {  //iterate through all records to be shown

				$parentid=$record->parent;

				$flag=0;

				foreach($result_set as $check_record) {  //check if parent of the record exists in the list

					if($parentid==$check_record->id) {
						$flag=1;
					}

				}

				if($flag==1) { //if parent is present in list

					$node = new stdClass(); //create object to store node details
					//don't create object globally, in php5 object is always passed by reference
					//hence create a new object for each node

					$node->id=$record->id; //set id right now, we will set depth later
					$node->depth=0;

					$index[$parentid][] = $node; //append node under parentid

				} else {

					$node = new stdClass();  

					$node->id=$record->id;
					$node->depth=0;

					$index[0][] = $node; //append node under 'top'

				}

			}

		}

	
	}  //all this happens only for the first call to the function 
	

	if(isset($index[$nodeid])) { //if any children exist for that node in index 
		foreach ($index[$nodeid] as $node){ //iterate through the node's children 

			$node->depth=$depth; //set depth of node

			$node1 = clone $node; //clone a new object (just to be safe)
			
			$list[]=$node1;  //append child to the list 
			set_child_nodes_list($result_set, $node->id, $depth+1, $list, $index); //if that child has children, append them first (recursively)
			//increment depth with each recursion
			
			//when done with this child and its descendents, continue with the siblings of the child 
		
		}
	}
	

}


function local_hierarchy_extend_navigation(global_navigation $navigation)
{
       global $CFG,$DB,$USER;
    
	$url = new moodle_url('/course/');
	$cnode = $navigation->add('Course Catalogue', $url,
		navigation_node::NODETYPE_LEAF, 'hierarchy', 'coursecatalogue', new pix_icon('i/course', ''));
	//$cnode->display = false;
	$cnode->showinflatnavigation = true;
	
	 if (is_siteadmin() || has_capability('local/hierarchy:user_report_access', context_system::instance())) {
            $url = new moodle_url('/local/hierarchy/reports/user_table.php');
            $cnode = $navigation->add('Custom Reports', $url,
                navigation_node::NODETYPE_LEAF,
				'hierarchy',
				'customreport',
				new pix_icon('i/privatefiles', ''));
            $cnode->display = false;
            $cnode->showinflatnavigation = true;
        }
    
	if (isloggedin()) {
        
            $url = new moodle_url('/local/hierarchy/nodes/pdf/myreport.php');
            $cnode = $navigation->add('My Activity Report', $url,
						navigation_node::NODETYPE_LEAF,
						'hierarchy',
						'myreport',
						new pix_icon('i/privatefiles', ''));
            $cnode->display = false;
            $cnode->showinflatnavigation = true;
	}
	
	
		$managerRoleId = $DB->get_record('role', array('shortname' => 'manager'));
		$deptunitadmin = $DB->get_record('role', array('shortname' => 'dept_unit_admin'));
		$branchunitadmin = $DB->get_record('role', array('shortname' => 'branch_unit_admin'));

	if((is_siteadmin()) || (user_has_role_assignment($USER->id, $managerRoleId->id)))
	{
		
	}
	else if(($USER->branchpoweruser == 1 && user_has_role_assignment($USER->id, $branchunitadmin->id)) || ($USER->deptpoweruser == 1 && user_has_role_assignment($USER->id, $deptunitadmin->id)))
	{
		$returnlisturl = new moodle_url('/admin/user.php');
		$usernode = $navigation->add('Manage Users', $returnlisturl,
						navigation_node::NODETYPE_LEAF,
						'hierarchy',
						'manageusers',
						new pix_icon('i/user', ''));
            $usernode->display = false;
            $usernode->showinflatnavigation = true;
		
	}
	
		// if(($CFG->prefix=='mdl_')&&(is_siteadmin())){
		// 	 $kng=$navigation->add('Push Courses', new moodle_url($CFG->wwwroot . '/centralSystem/courses/tenant_courses.php'),navigation_node::NODETYPE_LEAF,
		// 				'hierarchy',
		// 				'myreport',
		// 				new pix_icon('i/privatefiles', ''));
		// 	 $kng->display = true;
  //            $kng->showinflatnavigation = true;
  //       }
        
        if ( user_has_role_assignment($USER->id,9)) {
            $kng=$navigation->add('Centralized courses', new moodle_url($CFG->wwwroot . '/centralSystem/courses/index.php'),navigation_node::NODETYPE_LEAF,
						'hierarchy',
						'myreport',
						new pix_icon('i/privatefiles', ''));
			$kng->display = true;
            $kng->showinflatnavigation = true;
        }
        
        if(($CFG->prefix!='mdl_')&&(is_siteadmin())){
			$kng=$navigation->add('Centralized courses', new moodle_url($CFG->wwwroot . '/centralSystem/courses/index.php'),navigation_node::NODETYPE_LEAF,
						'hierarchy',
						'myreport',
						new pix_icon('i/privatefiles', ''));
			$kng->display = true;
            $kng->showinflatnavigation = true;
        }
    
}


function local_hierarchy_hide_before_footer() {
    global $PAGE;
   $PAGE->requires->js_init_code('alert("erere")');
}