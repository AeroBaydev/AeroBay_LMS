<?php

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');
global $CFG, $DB,$USER;
require_once("$CFG->libdir/formslib.php");
require_login();

if(!(has_capability('local/hierarchy:user_report_access', context_system::instance()))) { //check capability 
    redirect(new moodle_url('/my'));
}


$page=optional_param('page', 0, PARAM_INT);
$perpage=optional_param('perpage', 10, PARAM_INT);
$paging=$page*$perpage;  


$startdate_filter = optional_param('startdate_filter', 0, PARAM_INT);
$enddate_filter = optional_param('enddate_filter', 0, PARAM_INT);
$username_filter = optional_param('username_filter', '', PARAM_TEXT);
$firstname_filter = optional_param('firstname_filter', '', PARAM_TEXT);
$lastname_filter = optional_param('lastname_filter', '', PARAM_TEXT);
$email_filter = optional_param('email_filter', '', PARAM_TEXT);
$course_fullname_filter = optional_param('course_fullname_filter', '', PARAM_TEXT);
$course_shortname_filter = optional_param('course_shortname_filter', '', PARAM_TEXT);
$course_category_filter = optional_param('course_category_filter', '', PARAM_TEXT);
$branch_filter = optional_param('branch_filter', 0, PARAM_INT);
$dept_filter = optional_param('dept_filter', 0, PARAM_INT);


$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title('User report');
$PAGE->set_heading('User report');

$PAGE->set_url(new moodle_url('/local/hierarchy/reports/user_table.php', array('page'=> $page, 'perpage'=>$perpage, 'username_filter'=>$username_filter, 'firstname_filter'=>$firstname_filter, 'lastname_filter'=>$lastname_filter, 'email_filter'=>$email_filter, 'course_fullname_filter'=>$course_fullname_filter, 'course_shortname_filter'=>$course_shortname_filter, 'course_category_filter'=>$course_category_filter, 'startdate_filter'=>$startdate_filter, 'enddate_filter'=>$enddate_filter, 'branch_filter'=>$branch_filter, 'dept_filter'=>$dept_filter)));

$PAGE->requires->css(new moodle_url('/local/hierarchy/reports/style/table.css'));


///////////////////////////////////////////////////////////////////////

$teacherRoleId = $DB->get_record('role', array('shortname' => 'editingteacher'));
$managerRoleId = $DB->get_record('role', array('shortname' => 'manager'));
$tenant_admin_id = $DB->get_record('role', array('shortname' => 'tenantadmin'));


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

//////////////////////////////////////////////////

class simplehtml_form extends moodleform{

   		 public function definition() 
		{
			global $CurrentUser, $CFG,$DB,$PAGE,$course_fullname_filter,$course_shortname_filter,$course_category_filter,$startdate_filter,$enddate_filter,$username_filter,$firstname_filter,$lastname_filter,$email, $branch_filter, $dept_filter;

			$mform = $this->_form;
			//$buttonarray=array();
		    $mform->addElement('header','filterhead', 'Filter');
          	$mform->setExpanded('filterhead',0);
			$mform->addElement('html','<div class="amit-form">');
				$mform->addElement('html','<div class="row">');
					$mform->addElement('html','<div class="col-md-6">');
						$mform->addElement('text','username','User Name',array('size'=>'40'));
					$mform->addElement('html','</div>');
					
					$mform->addElement('html','<div class="col-md-6">');
						$mform->addElement('text','firstname','First Name',array('size'=>'40'));
					$mform->addElement('html','</div>');
					
					$mform->addElement('html','<div class="col-md-6">');
						$mform->addElement('text','lastname','Last Name',array('size'=>'40'));
					$mform->addElement('html','</div>');
					
					$mform->addElement('html','<div class="col-md-6">');
						$mform->addElement('text','email','Email',array('size'=>'40'));
					$mform->addElement('html','</div>');
					
					$mform->addElement('html','<div class="col-md-6">');
						$mform->addElement('text','coursefullname','Course Fullname',array('size'=>'40'));
					$mform->addElement('html','</div>');
					
					$mform->addElement('html','<div class="col-md-6">');
						$mform->addElement('text','courseshortname','Course Shortname',array('size'=>'40'));
					$mform->addElement('html','</div>');
					
					$mform->addElement('html','<div class="col-md-6">');
						$mform->addElement('text','coursecategory','Course Category',array('size'=>'40'));
					$mform->addElement('html','</div>');
					
					$mform->addElement('html','<div class="col-md-6">');
						$mform->addElement('date_selector','course_start_filter','Course Start Date',array('size'=>'40'));
					$mform->addElement('html','</div>');
				
				
						$year=2000;
						$month=1; 
						$day=1;
						$defaulttime = make_timestamp($year, $month, $day);
						$mform->setDefault('course_start_filter',  $defaulttime);
				
					$mform->addElement('html','<div class="col-md-6">');
						$mform->addElement('date_selector','course_end_filter','Course End Date',array('size'=>'40'));
					$mform->addElement('html','</div>');

////////////////////////////////////////////////////////

                    $modulejs=array(
        'name'=>'apply',
        'fullpath'=>'/local/hierarchy/reports/js/selection.js',
        'string'=>array(),
        'requires' => array(),
        );
        
        $userid=$CurrentUser->id;
        $deptid=$CurrentUser->dept;

        $table='loc_framework';
        $result = $DB->get_records_menu($table, array('deleted'=>0), null, $fields='id,fullname'); //get associative array
        
        $options = array('0'=>'Select branch') + $result;
        
        $table2='loc';
        $result2 = $DB->get_records_menu($table2, array('deleted'=>0), null, $fields='id,fullname'); //get associative array
        
        $options2 = array(''=>'Select department') + $result2;
    
        if((is_siteadmin()) || (user_has_role_assignment($USER->id, $managerRoleId->id)) || (user_has_role_assignment($USER->id, $teacherRoleId->id)))
        {
            
        	$mform->addElement('html','<div class="col-md-6">');
        	$mform->addElement('select', 'branch_filter', 'Branch', $options, array('id'=>'id_branch'));
        	$mform->addElement('html','</div>');

             $radioarray=array();
            $radioarray[] = $mform->createElement('select', 'dept_filter', 'Department', $options2, array('id'=>'id_dept', 'disabled'=>'disabled'));
            $radioarray[] = $mform->createElement('html', "<div class='loader2' style='background-image: url(\"images/loader.gif\"); background-size: contain; display: none; width: 20px; height: 20px;'></div>");

            $mform->addElement('html','<div class="col-md-6">');
            $mform->addGroup($radioarray, 'radioar', 'Dept', array(' '), false);  
            $mform->addElement('html','</div>');

            $PAGE->requires->js_init_call('apply',array($userid, $deptid, "id_branch", "id_dept"),true,$modulejs);
            //js will be required only for this condition

            
        }
        else if($CurrentUser->branchpoweruser == 1) //check branchpoweruser before deptpoweruser (it's subset)
        {
            /*$result2 = $DB->get_records_menu($table2, array('deleted'=>0,'frameworkid'=>$USER->branch), null, $fields='id,fullname'); //get associative array*/

            $list=array();
            make_child_nodes_list_select('loc', $CurrentUser->branch, 0, $list, $index=null);

            $result2=array("0"=>"Select department");
            foreach($list as $record) {
                $space="";
                for($i=1; $i<$record->depthlevel; $i++) {$space .= "&nbsp;&nbsp;&nbsp;";}
                //add spaces before name to show hierarchy within the select menu 
                        
                $text = $space . $record->fullname;
                
                $result2 = $result2 + array($record->id => $text);
            }


            
            $mform->addElement('hidden', 'branch_filter', $CurrentUser->branch, array('id'=>'id_branch'));
            
            $mform->addElement('html','<div class="col-md-6">');
            $mform->addElement('select', 'dept_filter', 'Department', $result2, array('id'=>'id_dept'));
            $mform->addElement('html','</div>');

        }
        else if($CurrentUser->deptpoweruser == 1)
        {

            $list=array();
            make_child_nodes_list_select('loc', $CurrentUser->branch, $CurrentUser->dept, $list, $index=null);

            $result2=array();
            foreach($list as $record) {
                $space="&nbsp;&nbsp;&nbsp;";
                for($i=1; $i<$record->depthlevel; $i++) {$space .= "&nbsp;&nbsp;&nbsp;";}
                //add spaces before name to show hierarchy within the select menu 
                        
                $text = $space . $record->fullname;
                
                $result2 = $result2 + array($record->id => $text);
            }

            $dept_record = $DB->get_record('loc', array('id'=>$CurrentUser->dept));

            $options2 = array("0"=>"Select Department", $CurrentUser->dept => $dept_record->fullname);
            $options2 += $result2;

            $mform->addElement('hidden', 'branch_filter', $CurrentUser->branch,array('id'=>'id_branch'));
            
            $mform->addElement('html','<div class="col-md-6">');
            $mform->addElement('select', 'dept_filter', 'Department', $options2, array('id'=>'id_dept'));
        	$mform->addElement('html','</div>');

        }
         else 
        {
            $mform->addElement('select', 'branch_filter', 'Branch', $options, array('id'=>'id_branch'));
            $mform->addElement('select', 'dept_filter', 'Department', $options2, array('id'=>'id_dept'));
            // $mform->disabledif('dept', 'branch', 'eq', '');
            $PAGE->requires->js_init_call('apply',array($userid, $deptid, "id_branch", "id_dept"),true,$modulejs);

        }
        
/////////////////////////////////////////////////////////////////


				$mform->addElement('html','</div>');
				
				$year=2050;
				$month=1; 
				$day=1;
				$defaulttime = make_timestamp($year, $month, $day);
				$mform->setDefault('course_end_filter',  $defaulttime);
				$mform->addElement('html','<div class="row">');
					$mform->addElement('html','<div class="col-md-12 cus-ami-right-form">');
						
						$mform->addElement('cancel','clear','Clear');
						$mform->addElement('submit','filter','Filter');
					$mform->addElement('html','</div>');					
				$mform->addElement('html','</div>');
		$mform->addElement('html','</div>');
		}
	}

	$mform = new simplehtml_form();
	
    if($mform->is_cancelled())
    {

    	$url= new moodle_url('/local/hierarchy/reports/user_table.php'); 
        
    	redirect($url);

    }  

    if ($formdata = $mform->get_data()) 
	{
           $course_fullname_filter   = $formdata->coursefullname;
           $course_shortname_filter  = $formdata->courseshortname;
           $course_category_filter   = $formdata->coursecategory;
           $startdate_filter         = $formdata->course_start_filter;
           $enddate_filter           = $formdata->course_end_filter;
           $username_filter          = $formdata->username;
           $firstname_filter         = $formdata->firstname;
           $lastname_filter          = $formdata->lastname;
           $email_filter             = $formdata->email;
           $branch_filter             = $formdata->branch_filter;
           $dept_filter             = $formdata->dept_filter;

	}


//set conditions according to filters
$condition = "";

if($startdate_filter!=0) {
	$condition .= " AND c.startdate > $startdate_filter";
}

if($enddate_filter!=0) {
	$condition .= " AND c.enddate < $enddate_filter"; 
}

if($username_filter!='') {
	$condition .= " AND u.username LIKE '%$username_filter%'";
}

if($firstname_filter!='') {
	$condition .= " AND u.firstname LIKE '%$firstname_filter%'";
}

if($lastname_filter!='') {
	$condition .= " AND u.lastname LIKE '%$lastname_filter%'";
}

if($email_filter!='') {
	$condition .= " AND u.email LIKE '%$email_filter%'";
}

if($course_fullname_filter!='') {
	$condition .= " AND c.fullname LIKE '%$course_fullname_filter%'";
}

if($course_shortname_filter!='') {
	$condition .= " AND c.shortname LIKE '%$course_shortname_filter%'";
}

if($course_category_filter!='') {
	$condition .= " AND cc.name LIKE '%$course_category_filter%'";
}

if($branch_filter!=0) {
	$condition .= " AND u.branch = $branch_filter";
}

if($dept_filter!=0) {
	$condition .= " AND u.dept = $dept_filter";
}



$sql_main = "SELECT u.id AS userid, c.id AS courseid, u.idnumber AS useridnumber, u.username, u.firstname, u.lastname, u.email, u.confirmed, u.suspended, u.branch, u.dept, u.address, u.phone2, u.city, u.country, u.timemodified AS user_timemodified, u.designation AS designation, c.idnumber AS courseidnumber, c.fullname AS coursefullname, c.shortname AS courseshortname, c.category AS coursecategoryid, c.startdate AS coursestartdate, c.enddate AS courseenddate, c.duration AS duration, c.visible AS coursevisible, c.timecreated, cc.name AS categoryname, ue.timecreated AS user_enrolment_timecreated

FROM {user} AS u INNER JOIN {user_enrolments} AS ue ON u.id=ue.userid INNER JOIN {enrol} AS e ON ue.enrolid=e.id INNER JOIN {course} AS c on e.courseid=c.id INNER JOIN {course_categories} as cc on c.category=cc.id WHERE 1=1 $condition $power_condition GROUP BY u.id, c.id ORDER BY u.id ASC LIMIT $perpage OFFSET $paging";


$count = "SELECT count(*) as c
FROM {user} AS u INNER JOIN {user_enrolments} AS ue ON u.id=ue.userid INNER JOIN {enrol} AS e ON ue.enrolid=e.id INNER JOIN {course} AS c on e.courseid=c.id INNER JOIN {course_categories} as cc on c.category=cc.id WHERE 1=1 $condition $power_condition";

$total_value = $DB->get_record_sql($count);
$total_value = $total_value->c;


$rs = $DB->get_recordset_sql($sql_main);



$htmltable = new html_table();

$htmltable->attributes['class'] = 'table table-striped table-hover table-bordered custom-table';

$htmltable->head = array('S.No.', 'Username', 'First Name', 'Last Name', 'Email',  'Suspended', 'Suspension date', 'Course ID Number', 'Course Fullname', 'Course Shortname', 'Course Category', 'Course Start Date', 'Course End Date', 'Course Has Expired', 'Course Duration', 'User Enrolment Start date', 'User Enrolment End date', 'Course Status', 'First access', 'Last access', 'Score', 'Course Creation Date', 'Status', 'Completion Date', 'Course Progression',  'Designation',  'Branch', 'Dept', 'Mailing Address', 'Mobile No.', 'City', 'Country', 'Report');


$count=$paging;

foreach ($rs as $record) {

	$count++;

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


    //
    $report="<form method='post' action='../nodes/pdf/myreport_for_user_report.php'>
    	<input type='hidden' name='userid' value=$record->userid />
    	<input type='submit' name='submit' value='View report' class='btn btn-secondary' />
    </form>";

	$htmltable->data[] = array($count, $record->username, $record->firstname, $record->lastname, $record->email,  $suspended, $suspension_time, $courseidnumber, $record->coursefullname, $record->courseshortname, $coursecategory,  $coursestartdate, $courseenddate, $coursehasexpired, $courseduration, $userenrolstartdate, $userenrolenddate, $coursestatus, $firstaccess, $lastaccess, $score, $coursecreationdate, $status, $coursecompletiondate, $record->course_completion_percent, $designation,  $branch, $dept, $record->address, $record->phone2, $record->city, $country, $report);

} 

$rs->close(); // Don't forget to close the recordset!



echo $OUTPUT->header();

/////////
	$tabs = array();
	$row = array();
	$activated = array();
	$inactive=array();
	$currenttab='User';
	 
	 
	 
	$pending1url = new moodle_url('/local/hierarchy/reports/user_table.php');
	$row[] = new tabobject('User', $pending1url->out(), 'User Report');

	$pending2url = new moodle_url('/local/hierarchy/reports/user_course_completion.php');
	$row[] = new tabobject('Course', $pending2url->out(),  'Course Report');


	$tabs[] = $row;
	$activated[] = $currenttab;
	print_tabs($tabs, $currenttab, $inactive, $activated);
	
////////






$systemcontext = context_system::instance();
    if(has_capability('local/hierarchy:course_report_filter',$systemcontext))
    {
    	$mform->display();
	}


$admins = get_admins();
$isadmin = false;
foreach($admins as $admin) {
    if ($USER->id == $admin->id) {
        $isadmin = true;
        break;
    }
}
 $systemcontext = context_system::instance();
    if(has_capability('local/hierarchy:course_report_download',$systemcontext))
    {
			
			
			echo html_writer::start_tag('div',array('class'=>'')).
			html_writer::start_tag('a',array('href'=>'#','class'=>'download')).html_writer::start_tag('img',array('class'=>'iconimg','src'=>'style/img/dowload.svg')).html_writer::end_tag('a').
		html_writer::end_tag('div');
			if( ($isadmin) || (is_siteadmin()) || (user_has_role_assignment($USER->id, $tenant_admin_id->id)) ) {
			echo html_writer::start_tag('div',array('class'=>'text-right', 'style'=>'font-size:24px; margin-top: -4px;')).
			html_writer::start_tag('a',array('href'=>'#','class'=>'download', 'style'=>'padding:5px;')).html_writer::start_tag('span',array('class'=>'fa fa-cloud-download')).html_writer::end_tag('a').
		html_writer::end_tag('div');
			}
    }

if(!$total_value)
{
	$a = new html_table_cell("No records found");
	$a->colspan=45;
    $htmltable->data[] = new html_table_row(array($a));
}

echo html_writer::start_tag('div',array('style'=>'clear:both;'));
echo html_writer::end_tag('div');


echo html_writer::start_tag('div',array('style'=>'overflow:auto; margin-top:15px;'));
echo html_writer::table($htmltable);
echo html_writer::end_tag('div');


$paging_url = new moodle_url('/local/hierarchy/reports/user_table.php', array('page'=> $page, 'perpage'=>$perpage, 'username_filter'=>$username_filter, 'firstname_filter'=>$firstname_filter, 'lastname_filter'=>$lastname_filter, 'email_filter'=>$email_filter, 'course_fullname_filter'=>$course_fullname_filter, 'course_shortname_filter'=>$course_shortname_filter, 'course_category_filter'=>$course_category_filter, 'startdate_filter'=>$startdate_filter, 'enddate_filter'=>$enddate_filter, 'branch_filter'=>$branch_filter, 'dept_filter'=>$dept_filter));


//count total records with filter but without pagination

echo $OUTPUT->paging_bar($total_value, $page, $perpage, $paging_url);

echo $OUTPUT->footer();

?>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>

$(document).ready(function() { 
			

	$(".iconimg").click(function(event){  
				
		window.location.href = "user_table_excel.php?condition_string=<?php echo urlencode($condition); ?>"; 
					
	});
	$(".fa-cloud-download").click(function(event){  
				
		window.location.href = "fullreport.php"; 
					
	});
				
});

</script>


<?php

function make_child_nodes_list_select($table, $frameworkid, $nodeid, &$list, $index=null) { 
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
            make_child_nodes_list_select($table, $frameworkid, $record->id, $list, $index); //if that child has children, append them first (recursively)
            
            //when done with this child and its descendents, continue with the siblings of the child 
        
        }
    }
    
}


?>