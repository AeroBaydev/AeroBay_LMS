<?php

require_once(__DIR__ . '/../../../../config.php');
global $CFG, $DB,$USER;

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title('User List');
$PAGE->set_heading('User List');
$PAGE->set_url(new moodle_url('/local/hierarchy/reports/user_list/user_list.php'));
$page=optional_param('page', 0, PARAM_INT);
$perpage=optional_param('perpage', 10, PARAM_INT);
$paging=$page * $perpage;


$teacherRoleId = $DB->get_record('role', array('shortname' => 'editingteacher'));

$managerRoleId = $DB->get_record('role', array('shortname' => 'manager'));

$deptunitadmin = $DB->get_record('role', array('shortname' => 'dept_unit_admin'));
$deptenrolladmin = $DB->get_record('role', array('shortname' => 'dept_enrollment_admin'));
$branchunitadmin = $DB->get_record('role', array('shortname' => 'branch_unit_admin'));
$branchenrolladmin = $DB->get_record('role', array('shortname' => 'branch_enrollment_admin'));
	
	
echo $OUTPUT->header();
 

$table = new html_table();
//$table->attributes['class'] = 'table table-striped table-hover table-bordered custom-table';
$table -> head = array('S.N','Name','Email','City/Town','Country','Edit');

if((is_siteadmin()) || (user_has_role_assignment($USER->id, $managerRoleId->id)) || (user_has_role_assignment($USER->id, $teacherRoleId->id)))
{ 
     $condition = ''; 
}
else if(($USER->branchpoweruser == 1) || (user_has_role_assignment($USER->id, $branchunitadmin->id)))
{
      $condition = " AND branch = $USER->branch";
}
else if(($USER->deptpoweruser == 1) || (user_has_role_assignment($USER->id, $deptunitadmin->id)))
{
      $condition = " AND dept = $USER->dept";  
}

$users_info = $DB->get_records_sql("SELECT firstname,lastname,email,city,country from {user} WHERE deleted = 0 $condition ORDER BY firstname ASC");
$count=count($users_info);
$user_info = $DB->get_records_sql("SELECT id,firstname,lastname,email,city,country from {user} WHERE deleted = 0 $condition ORDER BY firstname ASC limit $paging,$perpage");

$s_n =0;
$s_n =$paging;
 foreach ($user_info as $users) {
     $s_n++;
     $firstname = $users->firstname;
     $lastname  = $users->lastname;
     $email     = $users->email;
     $city      = $users->city;
     if($users->country)
     {
     	$country   = get_string($users->country,'countries');
     }
     else
     {
     	$country = '';
     }
	 $button =html_writer::start_tag('img',array('title'=> 'Delete','src'=>'delete.png','style'=>'width: 20px;height: 20px;'));
     $button .='&nbsp;&nbsp;'.html_writer::start_tag('a',array('href'=>new moodle_url('/user/editadvanced.php', array('id'=>$users->id)))).html_writer::empty_tag('img',array('title'=> 'Edit','src'=>'edit.png','style'=>'width: 20px;height: 20px;')).html_writer::end_tag('a');
	 
  	$table->data[] = array($s_n,$firstname.' '.$lastname,$email,$city,$country,$button);
  } 

  
echo html_writer::table($table);

$paging_url = new moodle_url('/local/user_course_completion/user_list.php',array('page'=>$page,'perpage'=>$perpage));

echo $OUTPUT->paging_bar($count, $page, $perpage, $paging_url);

echo $OUTPUT->single_button(new moodle_url('/user/editadvanced.php', array('id'=>-1)), 'Add New User', 'GET', array('class'=>'pull-left'));



echo $OUTPUT->footer();

?>