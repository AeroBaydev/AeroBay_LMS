<?php

require_once(__DIR__ . '/../../../../config.php');

global $CFG, $DB;

$type = required_param('type', PARAM_TEXT);
$deptid = required_param('deptid', PARAM_INT);
$searchtext = required_param('searchtext', PARAM_TEXT);


$sql1="SELECT userid FROM {user_dept_enrolments} WHERE deptid=$deptid"; //query to get userid of all users enrolled in the dept

$sql2="SELECT userid FROM {user_dept_enrolments}"; //query to get userid of all users enrolled in ANY dept


if($type=='potential') { //if search is for potential users 
	
	$result=$DB->get_records_sql("SELECT u.* FROM {user} AS u LEFT JOIN ($sql2) AS b ON u.id=b.userid WHERE b.userid IS NULL AND u.id!=1 AND u.deleted=0 AND (u.firstname LIKE '%$searchtext%' || u.lastname LIKE '%$searchtext%' || u.email LIKE '%$searchtext%')"); //get records that match the search
	//when $searchtext is empty, pattern becomes %% and all records are fetched
	
	$potential_select=array();
	
	foreach($result as $record) {
		$potential_select [] = array('id'=>$record->id, 'firstname'=>$record->firstname, 'lastname'=>$record->lastname, 'email'=>$record->email);
	}
	
	$data = json_encode($potential_select); //encode the array as json string 
	
	if($data=='[]') { //if json array is empty (no record returned) 
		echo 0;
	} else {
		echo $data;
	}
	
}


if($type=='existing') { //if search is for existing users 
	
	$result=$DB->get_records_sql("SELECT u.* FROM {user} AS u INNER JOIN ($sql1) AS b ON u.id=b.userid WHERE u.deleted=0 AND (u.firstname LIKE '%$searchtext%' || u.lastname LIKE '%$searchtext%' || u.email LIKE '%$searchtext%')");
	
	//when $searchtext is empty, pattern becomes %% and all records are fetched 
	
	$existing_select=array();
	
	foreach($result as $record) {
		$existing_select [] = array('id'=>$record->id, 'firstname'=>$record->firstname, 'lastname'=>$record->lastname, 'email'=>$record->email);
	}
	
	$data = json_encode($existing_select); //encode the array as json string 
	
	if($data=='[]') { //if json array is empty (no record returned) 
		echo 0;
	} else {
		echo $data;
	}
	
}

?>