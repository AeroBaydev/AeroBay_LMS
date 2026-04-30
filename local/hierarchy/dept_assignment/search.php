<?php

require('../../../config.php');

global $CFG, $DB;

$type = required_param('type', PARAM_TEXT);
$deptid = required_param('deptid', PARAM_INT);
$searchtext = required_param('searchtext', PARAM_TEXT);


$sql1="SELECT e.courseid FROM {enrol} AS e INNER JOIN {dept_enrolments} AS d ON e.id = d.enrolid WHERE d.deptid=$deptid"; //query to get courseids of all courses that this dept is enrolled in 


if($type=='potential') { //if search is for potential users 
	
	$result=$DB->get_records_sql("SELECT c.* FROM {course} AS c LEFT JOIN ($sql1) AS b ON c.id=b.courseid WHERE b.courseid IS NULL AND c.id!=1 AND c.fullname LIKE '%$searchtext%'"); //get records that match the search 
	//first record in course table is for site name 
	//when $searchtext is empty, pattern becomes %% and all records are fetched 
	
	$potential_select=array();
	
	foreach($result as $record) {
		$potential_select [] = array('id'=>$record->id, 'fullname'=>$record->fullname, 'shortname'=>$record->shortname);
	}
	
	$data = json_encode($potential_select); //encode the array as json string 
	
	if($data=='[]') { //if json array is empty (no record returned) 
		echo 0;
	} else {
		echo $data;
	}
	
}


if($type=='existing') { //if search is for existing users 
	
	$result=$DB->get_records_sql("SELECT c.* FROM {course} AS c INNER JOIN ($sql1) AS b ON c.id=b.courseid WHERE c.fullname LIKE '%$searchtext%'"); //get records that match the search 
	
	//when $searchtext is empty, pattern becomes %% and all records are fetched 
	
	$existing_select=array();
	
	foreach($result as $record) {
		$existing_select [] = array('id'=>$record->id, 'fullname'=>$record->fullname, 'shortname'=>$record->shortname);
	}
	
	$data = json_encode($existing_select); //encode the array as json string 
	
	if($data=='[]') { //if json array is empty (no record returned) 
		echo 0;
	} else {
		echo $data;
	}
	
}

?>