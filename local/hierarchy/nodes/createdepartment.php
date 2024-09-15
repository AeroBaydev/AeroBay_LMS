<?php
require('../../../config.php');

global $DB;

$sql = "select mt.*,mlf.id frameworkid from {temp} mt left join {loc_framework} mlf 
on mlf.idnumber = mt.branch_id";

$temp_data = $DB->get_records_sql($sql, [], $limitfrom=0, $limitnum=0);
$i=1;
$table ="loc";
foreach ($temp_data as $key => $value) {

if($value->department_level_1){
	$department_level_1 = $value->department_level_1;
	$department_level_1_id = $value->department_level_1_id;
	$department_level_1_data = $DB->get_record($table,["fullname"=>$department_level_1,"idnumber"=>$department_level_1_id], $fields='*', $strictness=IGNORE_MISSING);

	if(!$department_level_1_data){
		
		 $object1 = new stdClass();
		 $object1->fullname = $value->department_level_1;
		 $object1->idnumber = $value->department_level_1_id;
		 $object1->frameworkid = $value->frameworkid;
		 $object1->parentid = 0;
		 $object1->path = "/$i";
		 $object1->depthlevel = 1;
		 $object1->deleted = 0;
		 $object1->timecreated = strtotime("now");
		 $object1->usercreated = 2;

		 $DB->insert_record($table, $object1, $returnid=true, $bulk=false);
		 //echo var_dump($object1)."<br>";
		$path1= $i;
		$i++;
		$path2= $i;
	}else{
		$path1= $department_level_1_data->id;
		$path2= $i;
	}

}else{ 
  continue;
}

 if($value->department_level_2){

	$department_level_2 = $value->department_level_2;
	$department_level_2_id = $value->department_level_2_id;
	$department_level_2_data = $DB->get_record($table,["fullname"=>$department_level_2,"idnumber"=>$department_level_2_id], $fields='*', $strictness=IGNORE_MISSING);
	if(!$department_level_2_data){
			
	 	
	 $object2 = new stdClass();
	 $object2->fullname = $value->department_level_2;
	 $object2->idnumber = $value->department_level_2_id;
	 $object2->frameworkid = $value->frameworkid;
	 $object2->parentid = $path1;
	 $object2->path = "/$path1/$path2"; 
	 $object2->depthlevel = 2;
	 $object2->deleted = 0;
	 $object2->timecreated = strtotime("now");
	 $object2->usercreated = 2;

	 
	 $DB->insert_record($table, $object2, $returnid=true, $bulk=false);
 //echo var_dump($object2)."<br>";
		$path2 = $i;
		$i ++;
		$path3 = $i;
	
	}else{
		
		$path2 = $department_level_2_data->id;
		$path3 = $i;
	}
 }else{ 
  continue;
 }

if($value->department_level_3){
$department_level_3 = str_replace("#",",",$value->department_level_3);
	$department_level_3_id = $value->department_level_3_id;
	$department_level_3_data = $DB->get_record($table,["fullname"=>$department_level_3,"idnumber"=>$department_level_3_id], $fields='*', $strictness=IGNORE_MISSING);

		if(!$department_level_3_data){

		 $object3 = new stdClass();
		 $object3->fullname = str_replace("#",",",$value->department_level_3);
		 $object3->idnumber = $value->department_level_3_id;
		 $object3->frameworkid = $value->frameworkid;
		 $object3->parentid = $path2;
		 $object3->path = "/$path1/$path2/$path3";
		 $object3->depthlevel = 3;
		 $object3->deleted = 0;
		 $object3->timecreated = strtotime("now");
		 $object3->usercreated = 2;

		$DB->insert_record($table, $object3, $returnid=true, $bulk=false);
 //echo var_dump($object3)."<br>";
		$path3 = $i;
		$i ++;
		$path4 = $i;
		}else{
		
		$path3 = $department_level_3_data->id;
		$path4 = $i;
	}
}
else{ 
  continue;
 }


if($value->department_level_4){

	$department_level_4 = $value->department_level_4;
	$department_level_4_id = $value->department_level_4_id;
	$department_level_4_data = $DB->get_record($table,["fullname"=>$department_level_4,"idnumber"=>$department_level_4_id], $fields='*', $strictness=IGNORE_MISSING);
      
		if(!$department_level_4_data){

			 $object4 = new stdClass();
			 $object4->fullname = $value->department_level_4;
			 $object4->idnumber = $value->department_level_4_id;
			 $object4->frameworkid = $value->frameworkid;
			 $object4->parentid = $path3;
		 	 $object4->path = "/$path1/$path2/$path3/$path4";
			 $object4->depthlevel = 4;
			 $object4->deleted = 0;
			 $object4->timecreated = strtotime("now");
			 $object4->usercreated = 2;

 			$DB->insert_record($table, $object4, $returnid=true, $bulk=false);
 			 //echo var_dump($object4)."<br>";
 	 	$path4 = $i;
		$i ++;
		$path5 = $i;
		}else{

			$path4 = $department_level_4_data->id;
			$path5 = $i;
		}
}else{ 
  continue;
 }

if($value->department_level_5){

	$department_level_5 = $value->department_level_5;
	$department_level_5_id = $value->department_level_5_id;
	$department_level_5_data = $DB->get_record($table,["fullname"=>$department_level_5,"idnumber"=>$department_level_5_id], $fields='*', $strictness=IGNORE_MISSING);

		if(!$department_level_5_data){

		 $object5 = new stdClass();
		 $object5->fullname = $value->department_level_5;
		 $object5->idnumber = $value->department_level_5_id;
		 $object5->frameworkid = $value->frameworkid;
		 $object5->parentid = $path4;
		 $object5->path = "/$path1/$path2/$path3/$path4/$path5";
		 $object5->depthlevel = 5;
		 $object5->deleted = 0;
		 $object5->timecreated = strtotime("now");
		 $object5->usercreated = 2;
		 $DB->insert_record($table, $object5, $returnid=true, $bulk=false);
		  //echo var_dump($object5)."<br>";

		 $i++;
		}
}else{ 
  continue;
 }



}
echo "done!";
?>




