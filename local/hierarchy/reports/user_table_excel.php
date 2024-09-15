<?php
require('../../../config.php');
global $CFG, $DB, $USER; 

require_once('../../../lib/phpexcel/PHPExcel.php');
ini_set('mysql.connect_timeout', 14400000);
ini_set('default_socket_timeout', 1440000);
require_login();
global $CFG, $DB, $USER; 

$condition = required_param('condition_string', PARAM_RAW);
//$condition2 = required_param('condition2', PARAM_RAW);


/*
$sql_initial="SELECT * FROM {user_course_completion} $condition"; 
//get filtered records from backend table

$sql_main = "SELECT res.id, res.userid, res.courseid, res.course_completion_percent, usr.idnumber as useridnumber, usr.username, usr.firstname, usr.lastname, usr.email, usr.confirmed, usr.suspended, usr.branch, usr.dept, usr.address, usr.phone1, usr.city, usr.country, crs.idnumber as courseidnumber, crs.fullname as coursefullname, crs.shortname as courseshortname, crs.category as coursecategoryid, crs.startdate as coursestartdate, crs.enddate as courseenddate, crs.visible as coursevisible, crs.timecreated 

FROM ($sql_initial) AS res INNER JOIN {user} AS usr ON usr.id = res.userid INNER JOIN {course} AS crs ON crs.id = res.courseid $condition2 ORDER BY usr.username"; 
//join with user and course table to get details of user and course


$result=$DB->get_records_sql($sql_main);
*/

$CurrentUser=$DB->get_record('user',array('id'=>$USER->id));

		$departement = $CurrentUser->dept;
		$branch = $CurrentUser->branch;

		$departement_obj=$DB->get_record('loc',array('id'=>$departement));
		$depts= NULL;
		
		if($departement_obj){
			$dpath=$departement_obj->path;
			$selectrecord = ' path like ? '; //is put into the where clause
			$result = $DB->get_records_select_menu('loc', $selectrecord, array("$dpath/%"),'id','id,fullname');
			$result[$departement_obj->id]=$departement_obj->fullname;
			if(count($result))
			{
				$depts=implode(",",array_keys($result));
			}
		}
		
if((is_siteadmin()) || (user_has_role_assignment($USER->id, $managerRoleId->id)) || (user_has_role_assignment($USER->id, $teacherRoleId->id)))
{ 
     $power_condition = ''; 
}
else if($CurrentUser->branchpoweruser == 1)
{
      $power_condition = " AND u.branch = $branch";
}
else if($CurrentUser->deptpoweruser == 1)
{
      $power_condition = " AND u.dept in ($depts)";  
}


 $sql_main = "SELECT u.id AS userid, c.id AS courseid, u.idnumber AS useridnumber, u.username, u.firstname, u.lastname, u.email, u.confirmed, u.suspended, u.branch, u.dept, u.address, u.phone2, u.city, u.country, u.timemodified AS user_timemodified, u.designation AS designation, c.idnumber AS courseidnumber, c.fullname AS coursefullname, c.shortname AS courseshortname, c.category AS coursecategoryid, c.startdate AS coursestartdate, c.enddate AS courseenddate, c.duration AS duration, c.visible AS coursevisible, c.timecreated, cc.name AS categoryname, ue.timecreated AS user_enrolment_timecreated

FROM {user} AS u INNER JOIN {user_enrolments} AS ue ON u.id=ue.userid INNER JOIN {enrol} AS e ON ue.enrolid=e.id INNER JOIN {course} AS c on e.courseid=c.id INNER JOIN {course_categories} as cc on c.category=cc.id WHERE 1=1 $condition $power_condition GROUP BY u.id, c.id ORDER BY u.id ASC ";

$rs = $DB->get_recordset_sql($sql_main);


$objPHPExcel = new PHPExcel;
// set default font
$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
// set default font size
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
// create the writer
$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel2007");
// currency format, € with < 0 being in red color
$currencyFormat = '#,#0.## \€;[Red]-#,#0.## \€';
// number format, with thousands separator and two decimal points.
$numberFormat = '#,#0.##;[Red]-#,#0.##';


/////////// style
$objPHPExcel->getActiveSheet()->getStyle('A1:AH1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('d9d9d9');
// $objPHPExcel->getActiveSheet()->getStyle('A2:AR2')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('f5e415');


$objPHPExcel->getActiveSheet()->setCellValue('A1',"Report Generated On: " . date("d/m/Y h:i:sa"));	
$objPHPExcel->getActiveSheet()->mergeCells('A1:AH1');
$objPHPExcel->getActiveSheet()->getStyle('A1:P1')->getFont()->setSize(11);

$objPHPExcel->getActiveSheet()->setCellValue('A2','User Report');
$objPHPExcel->getActiveSheet()->mergeCells('A2:AH2');
$objPHPExcel->getActiveSheet()->getStyle('A2:AH2')->getFont()->setSize(16);



$objPHPExcel->getActiveSheet()->getStyle("A2:B2")->applyFromArray(array('alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER)));



$objPHPExcel->getActiveSheet()->getStyle('A2:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'2')->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle('A3:'.$objPHPExcel->getActiveSheet()->getHighestColumn().'3')->getFont()->setBold(true);


/////////// style

$objPHPExcel->getActiveSheet()->setCellValue('A3','S. No.');
$objPHPExcel->getActiveSheet()->setCellValue('B3','User ID Number');
$objPHPExcel->getActiveSheet()->setCellValue('C3','Username');
$objPHPExcel->getActiveSheet()->setCellValue('D3','First Name');
$objPHPExcel->getActiveSheet()->setCellValue('E3','Last Name');
$objPHPExcel->getActiveSheet()->setCellValue('F3','Email');
$objPHPExcel->getActiveSheet()->setCellValue('G3','Validation State');
$objPHPExcel->getActiveSheet()->setCellValue('H3','Suspended');
$objPHPExcel->getActiveSheet()->setCellValue('I3','Suspension date');
$objPHPExcel->getActiveSheet()->setCellValue('J3','Course ID Number');
$objPHPExcel->getActiveSheet()->setCellValue('K3','Course Fullname');
$objPHPExcel->getActiveSheet()->setCellValue('L3','Course Shortname');
$objPHPExcel->getActiveSheet()->setCellValue('M3','Course Category');
$objPHPExcel->getActiveSheet()->setCellValue('N3','Course Start Date');
$objPHPExcel->getActiveSheet()->setCellValue('O3','Course End Date');
$objPHPExcel->getActiveSheet()->setCellValue('P3','Course Has Expired');
$objPHPExcel->getActiveSheet()->setCellValue('Q3','Course Duration');
$objPHPExcel->getActiveSheet()->setCellValue('R3','User Enrolment Start date');
$objPHPExcel->getActiveSheet()->setCellValue('S3','User Enrolment End date');
$objPHPExcel->getActiveSheet()->setCellValue('T3','Course Status');
$objPHPExcel->getActiveSheet()->setCellValue('U3','First access');
$objPHPExcel->getActiveSheet()->setCellValue('V3','Last access');
$objPHPExcel->getActiveSheet()->setCellValue('W3','Score');
$objPHPExcel->getActiveSheet()->setCellValue('X3','Course Creation Date');
$objPHPExcel->getActiveSheet()->setCellValue('Y3','Status');
$objPHPExcel->getActiveSheet()->setCellValue('Z3','Completion Date');
$objPHPExcel->getActiveSheet()->setCellValue('AA3','Course Progression');
$objPHPExcel->getActiveSheet()->setCellValue('AB3','Designation');
$objPHPExcel->getActiveSheet()->setCellValue('AC3','Branch');
$objPHPExcel->getActiveSheet()->setCellValue('AD3','Dept	');
$objPHPExcel->getActiveSheet()->setCellValue('AE3','Mailing Address');
$objPHPExcel->getActiveSheet()->setCellValue('AF3','Mobile No.');
$objPHPExcel->getActiveSheet()->setCellValue('AG3','City');
$objPHPExcel->getActiveSheet()->setCellValue('AH3','Country');



	$count = 1;
	$rowCount = 4;

	// $DB->set_debug(true);
foreach ($rs as $record) {

	$counts++;

	//set values

	//
	$courseduration = '-';
	if($record->duration != 0) {
		$courseduration = $record->duration . " min";
	}

	//
	$designation = $record->designation;
	if($designation==NULL) {
		$designation="-";
	}


	//
	$status='Subscribed';
	
	$sql="SELECT * FROM {course_completions} WHERE userid=$record->userid AND course=$record->courseid";
	if($DB->record_exists_sql($sql)) {
	
		$rec = $DB->get_record('course_completions', array('userid'=>$record->userid, 'course'=>$record->courseid));

		if( ($rec->timecompleted!=0) && ($rec->timecompleted!=NULL) && ($rec->timecompleted!='') ) {
			$status='Completed';
		}

	}

	if($status!='Completed') { //if course is not completed

		$sql="SELECT * FROM {user_lastaccess} WHERE userid=$record->userid AND courseid=$record->courseid";

		if($DB->record_exists_sql($sql)) { //if user has accessed the course at least once
			$status='In progress';
		}

	}


	$total_module = $DB->get_record_sql("SELECT count(module) as c FROM {course_modules} WHERE deletioninprogress = 0 AND course = $record->courseid");
               
               $notcompleted_module = $DB->get_record_sql("SELECT count(module) as uc FROM {course_modules} WHERE completion = 0 AND deletioninprogress = 0  AND course = $record->courseid");

               $total_completion = $DB->get_record_sql("SELECT count(modules.module) as f FROM {course_modules} AS modules INNER JOIN {course_modules_completion} as course_module_result ON modules.id=course_module_result.coursemoduleid WHERE modules.course=$record->courseid AND modules.deletioninprogress=0 AND modules.completion>0 AND course_module_result.userid = $record->userid and course_module_result.completionstate>0");

               $total_completemodule = ($total_module->c)-($notcompleted_module->uc);
               $totaluser_completemodule = $total_completion->f;

               if($status!='Completed')
               {
	               if($total_completemodule && $totaluser_completemodule)
	                {   
	                	$record->course_completion_percent = round(($totaluser_completemodule/$total_completemodule)*100);
	                }
	                else
	                {
	                	$record->course_completion_percent = 0;
	                }
                }
                else
                {
                	$record->course_completion_percent = 100;
                }
	// 
	//$record->course_completion_percent=0;

	//
	$validated='Validated';
	if($record->confirmed==0){$validated='Not validated';}

	//
	$suspended='No';
	if($record->suspended==1) {$suspended='Yes';}

	//
	$suspension_time='-';
	if($suspended=='Yes') {
		$suspension_time = $firstaccess = date('d-m-Y h:i A', $record->user_timemodified);
	}

	//
	$coursecategory='-';
	$coursecategoryidnumber='-';


	$coursecategory=$record->categoryname;
	$catid=$DB->get_record_sql("SELECT * FROM {course_categories} WHERE id=$record->coursecategoryid");
    if($catid->idnumber != null)
    {
 		$coursecategoryidnumber=$catid->idnumber;
    }

	//
	$coursestartdate='-';
	$courseenddate='-';
	if($record->coursestartdate!=0) {$coursestartdate=date('d/m/Y', $record->coursestartdate);}
	if($record->courseenddate!=0) {$courseenddate=date('d/m/Y', $record->courseenddate);}

	//
	$coursehasexpired='No';
	if($record->courseenddate < time() && $record->courseenddate != 0) {$coursehasexpired='Yes';}

	//
	$userenrolstartdate='-';
	$userenrolenddate='-';

	$sql0="SELECT * FROM {enrol} WHERE courseid=$record->courseid";

	$sql="SELECT * FROM ($sql0) AS e INNER JOIN {user_enrolments} AS u ON e.id=u.enrolid WHERE u.userid=$record->userid LIMIT 1";

	$enrolment=$DB->get_record_sql($sql);

	if($enrolment->timestart!=0) {$userenrolstartdate = date('d/m/Y', $enrolment->timestart);}
	if($enrolment->timeend!=0) {$userenrolenddate = date('d/m/Y', $enrolment->timeend);}

	//
	$coursestatus='Not published';
	if($record->coursevisible==1) {$coursestatus='Published';}

	//
	$coursecreationdate='-';
	if($record->timecreated!=0) {$coursecreationdate=date('d/m/Y', $record->timecreated);}

	//
	

	//
	$coursecompletiondate='-';
	if($status=='Completed') {
		$a=$DB->get_record('course_completions', array('userid'=>$record->userid, 'course'=>$record->courseid));
		$coursecompletiondate=date('d/m/Y', $a->timecompleted);
	}

	//
	$branch='-';
	$dept='-';
	if($record->branch!=0) {
		$branchrecord=$DB->get_record('loc_framework', array('id'=>$record->branch));
		$branch=$branchrecord->fullname;
	}
	if($record->dept!=0) {
		$deptrecord=$DB->get_record('loc', array('id'=>$record->dept));
		$dept=$deptrecord->fullname;
	}

    if($record->country)
    {
        $country= get_string($record->country,'countries'); 
    }
    else
    {
    	$country='';
    }
    if($record->courseidnumber)
    {
        $courseidnumber = $record->courseidnumber; 
    }
    else
    {
        $courseidnumber = '-';  
    }
    if($record->useridnumber)
    {
    	$useridnumber =  $record->useridnumber;
    }
    else
    {
        $useridnumber = '-';	
    }

    //
    $firstaccess='-';
    $lastaccess='-';

    if( $DB->record_exists_sql("SELECT * FROM {user_lastaccess} WHERE userid=$record->userid AND courseid=$record->courseid") ) { //if user has opened the course

    	$firstaccess = date('d-m-Y h:i A', $record->user_enrolment_timecreated);

    	$lastaccess_record = $DB->get_record('user_lastaccess', array('userid'=>$record->userid, 'courseid'=>$record->courseid));

    	$lastaccess = date('d-m-Y h:i A', $lastaccess_record->timeaccess);

    }

    //
    $score = '-';

    $sql0="SELECT * FROM {grade_items} WHERE courseid=$record->courseid AND itemtype!='course' ";

   	$sql1="SELECT gg.id, gi.grademax, gg.finalgrade FROM ($sql0) AS gi INNER JOIN {grade_grades} AS gg ON gi.id=gg.itemid WHERE gg.userid=$record->userid AND gg.finalgrade IS NOT NULL";

	$items=$DB->get_records_sql($sql1);
	//don't take course's grade directly. When an activity is deleted, its grade is not deducted from the course's grade of the user.

	$total_grade=0;
	$total_maxgrade=0;

    foreach($items as $item) {

    	$total_grade += $item->finalgrade;
    	$total_maxgrade += $item->grademax;

    }

    if($total_maxgrade!=0) {
    	$score = $total_grade . "/" . $total_maxgrade;
    }


		$column='A';
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$counts);
		$column++;	
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$useridnumber);
		$column++;	
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$record->username);
		$column++;	
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$record->firstname);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$record->lastname);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$record->email);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$validated);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$suspended);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$suspension_time);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$courseidnumber);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$record->coursefullname);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$record->courseshortname);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$coursecategory);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$coursestartdate);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$courseenddate);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$coursehasexpired);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$courseduration);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$userenrolstartdate);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$userenrolenddate);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$coursestatus);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$firstaccess);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$lastaccess);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$score);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$coursecreationdate);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$status);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$coursecompletiondate);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$record->course_completion_percent);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$designation);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$branch);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$dept);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$record->address);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$record->phone2);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$record->city);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$country);
		$column++;

		$rowCount++;
		$count++;
	}
	






$BStyle = array(
  'borders' => array(
    'outline' => array(
      'style' => PHPExcel_Style_Border::BORDER_THIN
    )
  )
);

$objPHPExcel->getActiveSheet()->getStyle('A1:AH' . ($rowCount-1))->applyFromArray($BStyle);
unset($BStyle);

$char='A';
for($i=0; $i<100; $i++)
{
	$objPHPExcel->getActiveSheet()->getColumnDimension($char)->setAutoSize(true);
	$char++;
}	

/*$currenturl=array();
$currenturl=explode('.', @$_SERVER['HTTP_HOST']);
if(count($currenturl)){
$tenantdomain=$currenturl[0];
}*/
$save_name='UserReport.xlsx';
//$file="user_".$tenantdomain.".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename='.$save_name);
header('Cache-Control: max-age=0');
ob_end_clean();


 
$objWriter->save('php://output');
			
?>
