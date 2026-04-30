<?php
require_once "../../config.php";
require_once(__DIR__ . '/form_process.php');
require_login();
// require_capability('moodle/site:config', context_system::instance());
global $DB, $OUTPUT, $PAGE;
// $PAGE->requires->css(new moodle_url('/local/assign_school_ARM/style.css'));

$PAGE->requires->js(new moodle_url('/local/poc/script.js'));

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/hierarchy/dept_assignment/user_management/manage_users.php', array('deptid'=>$deptid));
$PAGE->set_pagelayout('course');
$PAGE->set_title('User management');
$PAGE->set_heading('User management');
$userId = $_GET['id'];
if (is_siteadmin()) {
                
    $PAGE->navbar->add('POC Management', "$CFG->wwwroot/local/poc/poc_management.php");        
    $PAGE->navbar->add("Assign School's", "$CFG->wwwroot/local/poc/school.php?userId=$userId");
}
else{
    $PAGE->navbar->add("Assign School's", "$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?roleid=13");
    
}


// echo "<a href=\"{$CFG->wwwroot}/local/regionalpoc/rm_arm_manage.php?roleid=4\" class='btn btn-primary'>BACK</a>";


//get existing users
$result=$DB->get_records_sql("SELECT * FROM {user} WHERE  deleted=0"); //get records of all users that are enrolled in the dept
$existing_select=''; 
foreach($result as $record) {
	$existing_select .= "<option value='$record->id'> $record->firstname $record->lastname ($record->email) </option>";
}
if($existing_select=='') { //if no user found 
	$existing_select = "<option disabled='disabled'> No users found </option>";
}


//get potential users
$result=$DB->get_records_sql("SELECT * FROM {course_categories} cc  join {school} s on s.school_id=cc.idnumber"); //get records of all users that are 'NOT enrolled in any dept' and ('Not enrolled in any branch' or 'enrolled in the selected branch')
//first record in user table is for guest
$potential_select='';
foreach($result as $record) {
	$potential_select .= "<option value='$record->id'> $record->name</option>";
}
if($potential_select=='') { //if no user found 
	$potential_select = "<option disabled='disabled'> No users found </option>";
}


echo $OUTPUT->header();


echo html_writer::start_tag('a', array('href'=>'poc_management.php')) .
	html_writer::start_tag('font', array('size'=>'2')).
		"&lt&lt " . "Back to " . $branchrecord->fullname . 
	html_writer::end_tag('font') . 
html_writer::end_tag('a');


echo $OUTPUT->heading('User management');


?>

<form id="assignform" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>"><div>

  <input type="hidden" name="deptid" value="<?php echo $deptid; ?>">
  <!-- hidden element to store deptid -->

  <!-- hidden element to store cohortid -->
   <input type="hidden" name="cohortid" value="<?php echo $cohortrecord->id; ?>">

  <table summary="" class="roleassigntable generaltable generalbox boxaligncenter" cellspacing="0">
    <tr>
      <td id="existingcell">
          <p><label for="removeselect"><?php echo "Existing School"; ?></label></p>
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
          <p><label for="addselect"><?php echo "School list"; ?></label></p>
          <?php display_select($potential_select, 'potential'); //////////////////////// right select ?>
      </td>
    </tr>
  </table>
</div></form>

<?php




function display_select($select, $name) {
	
	$text1='';
	if($name=='potential') {
		$text1='School list';
	} elseif($name=='existing') {
		$text1='Existing School';
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


