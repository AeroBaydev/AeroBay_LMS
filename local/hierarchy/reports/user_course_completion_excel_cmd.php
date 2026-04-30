 <?php
define("CLI_SCRIPT",true) ; 
require_once('../../../lib/phpexcel/PHPExcel.php');
  require('../../../config.php');
   global $CFG, $DB,$USER;
   $condition =''; 

$USER->id=6;

$objPHPExcel = new PHPExcel;

	// set default font
	$objPHPExcel->getDefaultStyle()->getFont()->setName('Calibri');

	// set default font size
	$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);

	// create the writer
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel2007");

	// currency format, € with < 0 being in red color
	$currencyFormat = '#,#0.## \€;[Red]-#,#0.## \€';

	// number format, with thousands separator and two decimal points.
	$numberFormat = '#,#0.##;[Red]-#,#0.##';
        	
    $a="A1:Q1";
	$objPHPExcel->getActiveSheet()->getStyle($a)->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('000000');
				$objPHPExcel->getActiveSheet()->mergeCells($a);
				$objPHPExcel->getActiveSheet()->getCell('A1')->setValue('Course Report');
				$objPHPExcel->getActiveSheet()->getStyle($a)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				//$objPHPExcel->getActiveSheet()->getStyle($a)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID) ->getStartColor()->setARGB('f5e415');
		
	//Completion Report HEADING	
	/*$objPHPExcel->getActiveSheet()->getStyle('A4:V4')
						 ->getFill()->getFont()->setBold(true)->setSize(16)
						 ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
						 ->getStartColor()
						 ->setARGB('FFFFFF');*/
    $objPHPExcel->getActiveSheet()->getStyle('A2:Q2')->getFont()->setBold(true);        
						 
         $style = array(
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        )
    );

   $objPHPExcel->getActiveSheet()->getStyle("A2:Q2")->applyFromArray($style);
   $objPHPExcel->getActiveSheet()->setCellValue('A2','S.No');
   $objPHPExcel->getActiveSheet()->setCellValue('B2','Course id number');
   $objPHPExcel->getActiveSheet()->setCellValue('C2','Course name');
   $objPHPExcel->getActiveSheet()->setCellValue('D2','Category');
   $objPHPExcel->getActiveSheet()->setCellValue('E2','Category id number');
   $objPHPExcel->getActiveSheet()->setCellValue('F2','Status');
   $objPHPExcel->getActiveSheet()->setCellValue('G2','Course has expired');
   $objPHPExcel->getActiveSheet()->setCellValue('H2','Start Date');
   $objPHPExcel->getActiveSheet()->setCellValue('I2','End Date');
   $objPHPExcel->getActiveSheet()->setCellValue('J2','Course duration');
   $objPHPExcel->getActiveSheet()->setCellValue('K2','Subcribed users');
   $objPHPExcel->getActiveSheet()->setCellValue('L2','Not started');
   $objPHPExcel->getActiveSheet()->setCellValue('M2','Not started(%)');
   $objPHPExcel->getActiveSheet()->setCellValue('N2','In progres');
   $objPHPExcel->getActiveSheet()->setCellValue('O2','In progres(%)');
   $objPHPExcel->getActiveSheet()->setCellValue('P2','Completed');
   $objPHPExcel->getActiveSheet()->setCellValue('Q2','Completed(%)');
   
  
 	$count=0;
	$rowCount=3;

$i=0;
$course_info = $DB->get_records_sql("SELECT cu.id, cu.idnumber AS cuidnumber, cu.category, cu.fullname, cu.visible, cu.startdate, cu.enddate, cu.duration, cc.name, cc.idnumber AS ccidnumber FROM {course} AS cu INNER JOIN {course_categories} as cc ON cu.category = cc.id $condition ORDER BY cu.fullname");	  

$teacherRoleId = $DB->get_record('role', array('shortname' => 'editingteacher'));
$managerRoleId = $DB->get_record('role', array('shortname' => 'manager'));
$tenantRoleId = $DB->get_record('role', array('shortname' => 'tenantadmin'));

$CurrentUser=$DB->get_record('user',array('id'=>$USER->id));
		$departement = $CurrentUser->dept;
		$branch = $CurrentUser->branch;
		$departement_obj=$DB->get_record('loc',array('id'=>$departement));
		$depts= NULL;
		$deptes=array();
		if($departement_obj){
			$dpath=$departement_obj->path;
			$selectrecord = ' path like ? '; //is put into the where clause
			$result = $DB->get_records_select_menu('loc', $selectrecord, array("$dpath/%"),'id','id,fullname');
			$result[$departement_obj->id]=$departement_obj->fullname;
			if(count($result))
			{
				$depts=implode(",",array_keys($result));
				$deptes=explode(",",$depts);
			}
		}
		
foreach ($course_info as $course) 
{
    $i++;
  $Subcribed_user    = 0;
  $course_notstart   = 0;
  $course_inprogress = 0;
  $course_completed  = 0;
  $course_notstart_percent   = 0;
  $course_inprogress_percent = 0;
  $course_completed_percent  = 0;

    $context = context_course::instance($course->id);
    $enrolled = get_enrolled_users($context);

 
 if((is_siteadmin()) || (user_has_role_assignment($USER->id, $managerRoleId->id)) || (user_has_role_assignment($USER->id, $tenantRoleId->id)) || (user_has_role_assignment($USER->id, $teacherRoleId->id)))
  { 
      if(count($enrolled))
      { 
        $Subcribed_user   = count($enrolled);

        foreach ($enrolled as $enobj) 
        { 
               $userid = $enobj->id;

               $st =$DB->get_record('course_completions',array('userid'=>$userid,'course'=>$course->id));

                if(($st->timecompleted!=0) && ($st->timecompleted!=null) && ($st->timecompleted!='')) 
                {
                    $course_completed++;
                    $status = 1;
                }
                else
                {
                    $status = 0;
                }

                if($status == 0)
                {
                  $sql="SELECT * FROM {user_lastaccess} WHERE userid=$userid AND courseid=$course->id";

                    if($DB->record_exists_sql($sql)) { //if user has accessed the course at least once
                     $course_inprogress++;
                    }
                    else
                    {
                        $course_notstart++;
                    }

                  /* $total_module = $DB->get_record_sql("SELECT count(module) as c FROM {course_modules} WHERE deletioninprogress = 0 AND course = $course->id");
                   
                   $notcompleted_module = $DB->get_record_sql("SELECT count(module) as uc FROM {course_modules} WHERE completion = 0 AND deletioninprogress = 0  AND course = $course->id");

                   $total_completion = $DB->get_record_sql("SELECT count(modules.module) as f FROM {course_modules} AS modules INNER JOIN {course_modules_completion} as course_module_result ON modules.id=course_module_result.coursemoduleid WHERE modules.course=$course->id AND modules.deletioninprogress=0 AND modules.completion>0 AND course_module_result.userid = $userid and course_module_result.completionstate>0");

                   $total_completemodule = ($total_module->c)-($notcompleted_module->uc);
                   $totaluser_completemodule = $total_completion->f;



                   if($total_completemodule && $totaluser_completemodule)
                    {   
                      $occp = round(($totaluser_completemodule/$total_completemodule)*100);
                    }
                    else
                    {
                      $occp = 0;
                    }

                    if($occp == 0)
                    {
                        $course_notstart++;
                }
                if($occp > 0 && $occp < 100)
                {
                        $course_inprogress++;  
                }*/
                }
                
          }
             
            if($course_notstart)
            {
              $course_notstart_percent = round(($course_notstart/$Subcribed_user)*100);
            }

            if($course_inprogress)
            {
              $course_inprogress_percent = round(($course_inprogress/$Subcribed_user)*100);
            }
       
            if($course_completed)
            {
              $course_completed_percent = round(($course_completed/$Subcribed_user)*100);
            }
      }

        if($course->visible == 1)
        {
          $visible = "Published";
        }
        else
        {
          $visible = "Not Published";
        }

        $startdate = date('d/m/Y', $course->startdate);

        if($course->enddate > 0)
        {
          $t =time();
          $enddate   = date('d/m/Y', $course->enddate);

            $h =abs(($course->startdate) - ($course->enddate));
            
            
            if($course->enddate > $t)
            {
               $has_exp = "No";  
            }
            else
            {
              $has_exp = "Yes";
            }
        }
        else
        {
          $enddate = "-";
          $has_exp = "No";
          
        }

        if($course->ccidnumber != 0)
        {
          $category_id = $course->ccidnumber;
        }
        else
        {
          $category_id = "-";
        }
        if($course->cuidnumber != 0)
        {
          $course_idnumber = $course->cuidnumber;
        }
        else
        {
          $course_idnumber = "-";
        }

        $course_fullname     = $course->fullname;
        $course_category   = $course->name;

  }

    //branchpoweruser loop

else if($CurrentUser->branchpoweruser == 1)
    {
        $enrollbranchpoweruser = array();
        if(count($enrolled) != 0)
        {
            foreach ($enrolled as $obj) 
            {
               if($obj->branch == $USER->branch)
               {
                  $enrollbranchpoweruser[] = $obj;
               }
            }          
        }   
        if(count($enrollbranchpoweruser))
        { 
            $Subcribed_user  = count($enrollbranchpoweruser);

           foreach ($enrollbranchpoweruser as $enobj) 
            {   
               $userid = $enobj->id;

              $st =$DB->get_record('course_completions',array('userid'=>$userid,'course'=>$course->id));

                if(($st->timecompleted!=0) && ($st->timecompleted!=null) && ($st->timecompleted!=''))
                {
                    $course_completed++;
                    $status = 1;
                }
                else
                {
                    $status = 0;
                }
                
                if($status == 0)
                {

                  $sql="SELECT * FROM {user_lastaccess} WHERE userid=$userid AND courseid=$course->id";

                    if($DB->record_exists_sql($sql)) { //if user has accessed the course at least once
                     $course_inprogress++;
                    }
                    else
                    {
                        $course_notstart++;
                    }

                   /*$total_module = $DB->get_record_sql("SELECT count(module) as c FROM {course_modules} WHERE deletioninprogress = 0 AND course = $course->id");
                   
                   $notcompleted_module = $DB->get_record_sql("SELECT count(module) as uc FROM {course_modules} WHERE completion = 0 AND deletioninprogress = 0  AND course = $course->id");

                   $total_completion = $DB->get_record_sql("SELECT count(modules.module) as f FROM {course_modules} AS modules INNER JOIN {course_modules_completion} as course_module_result ON modules.id=course_module_result.coursemoduleid WHERE modules.course=$course->id AND modules.deletioninprogress=0 AND modules.completion>0 AND course_module_result.userid = $userid and course_module_result.completionstate>0");

                   $total_completemodule = ($total_module->c)-($notcompleted_module->uc);
                   $totaluser_completemodule = $total_completion->f;



                   if($total_completemodule && $totaluser_completemodule)
                    {   
                        $occp = round(($totaluser_completemodule/$total_completemodule)*100);
                    }
                    else
                    {
                        $occp = 0;
                    }

                    if($occp == 0)
                    {
                        $course_notstart++;
                    }
                    if($occp > 0 && $occp < 100)
                    {
                        $course_inprogress++;  
                    }*/
                }
                
            }
             
            if($course_notstart)
            {
                $course_notstart_percent = round(($course_notstart/$Subcribed_user)*100);
            }

            if($course_inprogress)
            {
                $course_inprogress_percent = round(($course_inprogress/$Subcribed_user)*100);
            }
       
            if($course_completed)
            {
                $course_completed_percent = round(($course_completed/$Subcribed_user)*100);
            }
        }

        if($course->visible == 1)
        {
            $visible = "Published";
        }
        else
        {
            $visible = "Not Published";
        }

        $startdate = date('d/m/Y', $course->startdate);

        if($course->enddate > 0)
        {
            $t =time();
            $enddate   = date('d/m/Y', $course->enddate);

            $h =abs(($course->startdate) - ($course->enddate));
           
            
            if($course->enddate > $t)
            {
               $has_exp = "No";  
            }
            else
            {
                $has_exp = "Yes";
            }
        }
        else
        {
            $enddate = "-";
            $has_exp = "No";
         
        }

        if($course->ccidnumber != 0)
        {
            $category_id = $course->ccidnumber;
        }
        else
        {
            $category_id = "-";
        }
        if($course->cuidnumber != 0)
        {
            $course_idnumber = $course->cuidnumber;
        }
        else
        {
            $course_idnumber = "-";
        }

        $course_fullname     = $course->fullname;
        $course_category     = $course->name;

    } 
    
    // departmentpoweruser loop

       else if($CurrentUser->deptpoweruser == 1)
    {
        $enrolleddeptuser = array();
        if(count($enrolled) != 0)
        {
            foreach ($enrolled as $obj) 
            {
               if(in_array($obj->dept, $deptes))
               {
                  $enrolleddeptuser[] = $obj;
               }
            }
        }   
        if(count($enrolleddeptuser))
        { 
            $Subcribed_user  = count($enrolleddeptuser);

          foreach ($enrolleddeptuser as $enobj) 
            {   
               $userid = $enobj->id;

               $st =$DB->get_record('course_completions',array('userid'=>$userid,'course'=>$course->id));

                if(($st->timecompleted!=0) && ($st->timecompleted!=null) && ($st->timecompleted!=''))
                {
                    $course_completed++;
                    $status = 1;
                }
                else
                {
                    $status = 0;
                }
                
                if($status == 0)
                {

                  $sql="SELECT * FROM {user_lastaccess} WHERE userid=$userid AND courseid=$course->id";

                    if($DB->record_exists_sql($sql)) { //if user has accessed the course at least once
                     $course_inprogress++;
                    }
                    else
                    {
                        $course_notstart++;
                    }

                  /* $total_module = $DB->get_record_sql("SELECT count(module) as c FROM {course_modules} WHERE deletioninprogress = 0 AND course = $course->id");
                   
                   $notcompleted_module = $DB->get_record_sql("SELECT count(module) as uc FROM {course_modules} WHERE completion = 0 AND deletioninprogress = 0  AND course = $course->id");

                   $total_completion = $DB->get_record_sql("SELECT count(modules.module) as f FROM {course_modules} AS modules INNER JOIN {course_modules_completion} as course_module_result ON modules.id=course_module_result.coursemoduleid WHERE modules.course=$course->id AND modules.deletioninprogress=0 AND modules.completion>0 AND course_module_result.userid = $userid and course_module_result.completionstate>0");

                   $total_completemodule = ($total_module->c)-($notcompleted_module->uc);
                   $totaluser_completemodule = $total_completion->f;



                   if($total_completemodule && $totaluser_completemodule)
                    {   
                        $occp = round(($totaluser_completemodule/$total_completemodule)*100);
                    }
                    else
                    {
                        $occp = 0;
                    }

                    if($occp == 0)
                    {
                        $course_notstart++;
                    }
                    if($occp > 0 && $occp < 100)
                    {
                        $course_inprogress++;  
                    }*/
                }
                
            }
             
            if($course_notstart)
            {
                $course_notstart_percent = round(($course_notstart/$Subcribed_user)*100);
            }

            if($course_inprogress)
            {
                $course_inprogress_percent = round(($course_inprogress/$Subcribed_user)*100);
            }
       
            if($course_completed)
            {
                $course_completed_percent = round(($course_completed/$Subcribed_user)*100);
            }
        }

        if($course->visible == 1)
        {
            $visible = "Published";
        }
        else
        {
            $visible = "Not Published";
        }

        $startdate = date('d/m/Y', $course->startdate);

        if($course->enddate > 0)
        {
            $t =time();
            $enddate   = date('d/m/Y', $course->enddate);

            $h =abs(($course->startdate) - ($course->enddate));
            
            
            if($course->enddate > $t)
            {
               $has_exp = "No";  
            }
            else
            {
                $has_exp = "Yes";
            }
        }
        else
        {
            $enddate = "-";
            $has_exp = "No";
           
        }

        if($course->ccidnumber != 0)
        {
            $category_id = $course->ccidnumber;
        }
        else
        {
            $category_id = "-";
        }
        if($course->cuidnumber != 0)
        {
            $course_idnumber = $course->cuidnumber;
        }
        else
        {
            $course_idnumber = "-";
        }

        $course_fullname     = $course->fullname;
        $course_category     = $course->name;

    } 

    //
    $duration = '-';
    if($course->duration != 0) {
        $duration = $course->duration . " min";
    }


    $value1="A".$rowCount.":".'E'.$rowCount;
		$objPHPExcel->getActiveSheet()->getStyle($value1)->applyFromArray($style);
		   
			/*if($rowCount%2!=0)
	   		 { 
                $value="A".$rowCount.":".'V'.$rowCount;
	            $objPHPExcel->getActiveSheet()->getStyle($value)
						 ->getFill()
						 ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
						 ->getStartColor()
						 ->setARGB('FFFFFF');
	         }*/
		$count++;
		$column='A';
		
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$i);
		$column++;	
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$course_idnumber);
		$column++;	
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$course_fullname);	
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$course_category);	
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$category_id);
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$visible);	
		$column++;	
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$has_exp);	
		$column++;
        $objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$startdate);	
		$column++;
        $objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$enddate);	
		$column++;
        $objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$duration);	
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$Subcribed_user);	
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$course_notstart);	
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$course_notstart_percent);	
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$course_inprogress);	
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$course_inprogress_percent);	
		$column++;
		$objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$course_completed);	
		$column++;
        $objPHPExcel->getActiveSheet()->setCellValue($column.$rowCount,$course_completed_percent);	
		
		


		$rowCount++;
}
	
// auto resizing of column
$ch='A';
for($i=0;$i<26;$i++)
{
	$objPHPExcel->getActiveSheet()->getColumnDimension($ch)->setAutoSize(true);
	$ch++;
}

$date=date('d_M_Y');
$filename="course_completion_".$date.".xlsx";
//header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
//header('Content-Disposition: attachment;filename='.$filename);
//header('Cache-Control: max-age=0');
//ob_end_clean();
$objWriter->save("test.xls");
?>
