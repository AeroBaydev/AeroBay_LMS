<?php

require('../../../config.php');
require_once(__DIR__ . '/../lib.php');

global $CFG, $DB;

$type = required_param('type', PARAM_TEXT);
$deptid = required_param('deptid', PARAM_INT);
$searchtext = required_param('searchtext', PARAM_TEXT);


$sql1="SELECT d.categoryid FROM {dept_category_enrolments} AS d WHERE d.deptid=$deptid"; //query to get ids of all categories that this dept is enrolled in 


if($type=='potential') { //if search is for potential users 
	
	$potential_result=$DB->get_records_sql("SELECT c.* FROM {course_categories} AS c LEFT JOIN ($sql1) AS b ON c.id=b.categoryid WHERE b.categoryid IS NULL AND c.name LIKE '%$searchtext%'");

	$potential_list=array();
	set_child_nodes_list($potential_result, 0, 0, $potential_list, null); //make hierarchical list

	//when $searchtext is empty, pattern becomes %% and all records are fetched 
	
	$potential_select=array();
	
	foreach($potential_list as $item) {

		$record = $DB->get_record('course_categories', array('id'=>$item->id));

		$space='';
		for($i=0; $i<$item->depth; $i++) {$space .= '&nbsp;&nbsp;&nbsp;';}

		$potential_select [] = array('id'=>$record->id, 'name'=>$record->name, 'space'=>$space);
	
	}
	
	$data = json_encode($potential_select); //encode the array as json string 
	
	if($data=='[]') { //if json array is empty (no record returned) 
		echo 0;
	} else {
		echo $data;
	}
	
}


if($type=='existing') { //if search is for existing users 
	
	$existing_result=$DB->get_records_sql("SELECT c.* FROM {course_categories} AS c INNER JOIN ($sql1) AS b ON c.id=b.categoryid AND c.name LIKE '%$searchtext%'");
	
	//when $searchtext is empty, pattern becomes %% and all records are fetched 

	$existing_list=array(); 
	set_child_nodes_list($existing_result, 0, 0, $existing_list, null); //make hierarchical list
	
	$existing_select=array();
	
	foreach($existing_list as $item) {

		$record = $DB->get_record('course_categories', array('id'=>$item->id));

		$space='';
		for($i=0; $i<$item->depth; $i++) {$space .= '&nbsp;&nbsp;&nbsp;';}

		$existing_select [] = array('id'=>$record->id, 'name'=>$record->name, 'space'=>$space);

	}
	
	$data = json_encode($existing_select); //encode the array as json string 
	
	if($data=='[]') { //if json array is empty (no record returned) 
		echo 0;
	} else {
		echo $data;
	}
	
}

?>