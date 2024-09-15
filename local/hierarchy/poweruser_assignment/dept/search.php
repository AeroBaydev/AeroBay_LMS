<?php

require('../../../../config.php');

global $CFG, $DB;

$type = required_param('type', PARAM_TEXT);
$deptid = required_param('deptid', PARAM_INT);
$searchtext = required_param('searchtext', PARAM_TEXT);


if($type=='potential') { //if search is for potential users 
	
	$result=$DB->get_records_sql("SELECT * FROM {user} WHERE deptpoweruser=0 AND dept=$deptid AND deleted=0 AND (firstname LIKE '%$searchtext%' OR lastname LIKE '%$searchtext%' OR email LIKE '%$searchtext%')"); 

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

	$result=$DB->get_records_sql("SELECT * FROM {user} WHERE deptpoweruser=1 AND dept=$deptid AND deleted=0 AND (firstname LIKE '%$searchtext%' OR lastname LIKE '%$searchtext%' OR email LIKE '%$searchtext%')"); 
	
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