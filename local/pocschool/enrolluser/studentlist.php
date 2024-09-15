<?php
require_once "../../../config.php";
require_once(__DIR__ . '/form_process.php');
require_login();
// require_capability('moodle/site:config', context_system::instance());
global $DB, $OUTPUT, $PAGE;
// $PAGE->requires->css(new moodle_url('/local/Enroll_school_ARM/style.css'));
$CourseId = $_GET['id'];

if($_SESSION['CourseId'])
{

	$getCourseId=$_SESSION['CourseId'];
}
else{
	$getCourseId=$CourseId;
	$_SESSION['CourseId']=$getCourseId;
}
//$PAGE->requires->js(new moodle_url('/local/poc/script.js'));
$course = get_course($getCourseId);

$category = $DB->get_record('course_categories', array('id' => $course->category));
$PAGE->set_context(context_system::instance());
$PAGE->set_url("$CFG->wwwroot/local/pocschool/enrolluser/studentlist.php?id=$CourseId", array('id'=>$CourseId));
$PAGE->set_pagelayout('course');
$PAGE->set_title('Enroll Student POC');
$PAGE->set_heading('Enroll Student POC');
if (is_siteadmin()) {
                
    $PAGE->navbar->add('POC Management', "$CFG->wwwroot/local/pocschool/viewcourse.php?catId=$course->category");        
    $PAGE->navbar->add("Enroll Student", "$CFG->wwwroot/local/pocschool/studentlist.php?gradeid=$CourseId");
}
else{
	$PAGE->navbar->add("Enroll School's", "$CFG->wwwroot/local/pocschool/studentlist.php?gradeid=$CourseId");
    
}


// echo "<a href=\"{$CFG->wwwroot}/local/regionalpoc/rm_arm_manage.php?roleid=4\" class='btn btn-primary'>BACK</a>";




$context = context_course::instance($getCourseId);
$studentrole = $DB->get_record('role', ['shortname' => 'student']);
$students = get_role_users($studentrole->id, $context, false, 'u.*');
// $result=$DB->get_records_sql("SELECT cc.* FROM {student} cc  "); //get records of all users that are enrolled in 
$existing_select=''; 

foreach($students as $record) {
	$existing_select .= "<option value='$record->id'>  $record->firstname  $record->lastname </option>";

	
}
if($existing_select=='') { //if no user found 
	$existing_select = "<option disabled='disabled'> No Student found </option>";
}

// print_r($existUserArray);
// die;
//get potential users

if (isset($_SESSION['userIdPoc'])) {
    $userid=$_SESSION['userIdPoc'];
  
 }
 else{
    $userid= $USER->id;
 }




$existUserArray=[];
foreach ($students as $key => $value) {
	$existUserArray[]=$value->id;
}

$notUserid=0;
if(!empty($existUserArray))
{
	$notUserid = implode(',',$existUserArray);
}



// print_r($category->id);
// die;

$result=$DB->get_records_sql("SELECT u.* FROM {student} s  join {user} u on s.userid=u.id where  s.gradeid=$category->id and u.id not in($notUserid)"); //get records of all users that are 'NOT enrolled in any dept' and ('Not enrolled in any branch' or 'enrolled in the selected branch')
//first record in user table is for guest
$potential_select='';
foreach($result as $record) {
	$potential_select .= "<option value='$record->id'>  $record->firstname  $record->lastname </option>";
}
if($potential_select=='') { //if no user found 
	$potential_select = "<option disabled='disabled'> No Student found </option>";
}


echo $OUTPUT->header();


echo html_writer::start_tag('a', array('href'=>"../viewcourse.php?catId=$course->category")) .
	html_writer::start_tag('font', array('size'=>'2')).
		"&lt&lt " . "Back to " ."". 
	html_writer::end_tag('font') . 
html_writer::end_tag('a');


?>

<form id="Enrollform" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>"><div>

  <input type="hidden" name="id" value="<?php echo $getCourseId; ?>">
  <!-- hidden element to store deptid -->

  <!-- school list-->
   

  <table summary="" class="roleEnrolltable generaltable generalbox boxaligncenter" cellspacing="0">
    <tr>
      <td id="existingcell">
          <p><label for="removeselect"><?php echo "Student list"; ?></label></p>
          <?php display_select($existing_select, 'existing'); ////////////////left select ?>
      </td>
      <td id="buttonscell">
          <div id="addcontrols">
              <input name="add" id="add" type="submit" value="<?php echo $OUTPUT->larrow().'&nbsp;'.get_string('add'); ?>" title="<?php print_string('add'); ?>" /><br />
			  <!----------------------- central selects -->
          </div>

          <div id="removecontrols">
              <input name="remove" id="remove" type="submit" value="<?php echo get_string('remove').'&nbsp;'.$OUTPUT->rarrow(); ?>" title="<?php print_string('remove'); ?>" />
          </div>
      </td>
      <td id="potentialcell">
          <p><label for="addselect"><?php echo "Student list"; ?></label></p>
          <?php display_select($potential_select, 'potential'); //////////////////////// right select ?>
      </td>
    </tr>
  </table>
</div></form>

<?php




function display_select($select, $name) {
	
	$text1='';
	if($name=='potential') {
		$text1='Student list';
	} elseif($name=='existing') {
		$text1='Existing Student';
	}  
	
	echo "

	<div class='userselector' id='id_".$name."_wrapper'>
		
		<div class='positioned'>

			<select name='".$name."_select[]' id='id_".$name."_select' multiple='multiple' size='10' class='form-control no-overflow'>
			   
				<optgroup id='id_".$name."_optgroup' label='".$text1."'>
					".$select."
					<option disabled='disabled'>&nbsp;</option>
				</optgroup>

			</select>

			<div class='load' id='id_".$name."_load' style='display:none'></div>

		</div>
		
		<div class='form-inline'>
		
			<label>search</label>
			<input type='text' name='".$name."_searchtext' id='id_".$name."_searchtext' size='15' class='form-control'>
			
			<div class='form-inline'>
		
				<input type='submit' name='".$name."_clearbutton' id='id_".$name."_clearbutton' value='clear' class='btn btn-secondary'>
		
			</div>
		
		</div>
		
	</div>	

	";
		
}	
echo $OUTPUT->footer();

?>

<script> var dept_id = <?php echo $deptid; ?> </script> <!-- global variable dept_id for external js to use -->

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

<script src="search.js?new=<?php echo time(); ?>"></script> <!-- echo time to prevent caching --> 

<link rel="stylesheet" type="text/css" href="custom.css?new=<?php echo time(); ?>">


