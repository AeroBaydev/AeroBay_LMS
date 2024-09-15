<?php

require('../../../config.php');
require('../lib.php');

require('form_process.php'); //contains code to process submitted data 

global $CFG, $DB, $PAGE;

require_capability('local/hierarchy:manage', context_system::instance(), null, true, "Capability 'Manage hierarchies' required"); //check capability 

$deptid = required_param('deptid', PARAM_INT);

require_once($CFG->libdir . '/adminlib.php'); 
admin_externalpage_setup('locate'); 

$deptrecord=$DB->get_record('loc', array('id'=>$deptid, 'deleted'=>0));
$branchrecord=$DB->get_record('loc_framework', array('id'=>$deptrecord->frameworkid, 'deleted'=>0));

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/hierarchy/dept_assignment/assignment.php', array('deptid'=>$deptid));
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Department enrolment');
$PAGE->set_heading('Department enrolment');

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('site_administration', 'local_hierarchy'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('hierarchies', 'local_hierarchy'), new moodle_url('/admin/category.php?category=hierarchy'));
$PAGE->navbar->add(get_string('locate_frameworks', 'local_hierarchy'), new moodle_url('/local/hierarchy/nodes/node_framework.php?prefix=locate'));
$PAGE->navbar->add($branchrecord->fullname, new moodle_url('/local/hierarchy/nodes/node.php?prefix=locate&frameworkid='.$deptrecord->frameworkid));
$PAGE->navbar->add($deptrecord->fullname . ' assignment');


//get options for enrolment duration 
$periodmenu = array();
$periodmenu[0] = get_string('unlimited');
for ($i=1; $i<=365; $i++) {
    $seconds = $i * 86400;
    $periodmenu[$seconds] = get_string('numdays', '', $i); //array for enrolment duration select menu 
}


//get all roles under course context. These roles can be assigned at course level. All courses have the same assignable roles. 
$sql='SELECT c.roleid, r.name FROM {role_context_levels} c INNER JOIN {role} r ON c.roleid=r.id WHERE c.contextlevel=50 ORDER BY c.roleid ASC';
$roles = $DB->get_records_sql_menu($sql); //array for roles select menu 
$roles = $roles;

set_names($roles); //set names of roles predefined in moodle 


$sql1="SELECT e.courseid FROM {enrol} AS e INNER JOIN {dept_enrolments} AS d ON e.id = d.enrolid WHERE d.deptid=$deptid"; //query to get courseids of all courses that this dept is enrolled in 


//get existing courses 
$result=$DB->get_records_sql("SELECT c.* FROM {course} AS c INNER JOIN ($sql1) AS b ON c.id=b.courseid"); //using $sql1 get records of all courses that this dept is enrolled in 
$existing_select=''; 
foreach($result as $record) {
	$existing_select .= "<option value='$record->id'> $record->fullname ($record->shortname) </option>";
}
if($existing_select=='') { //if no course found 
	$existing_select = "<option disabled='disabled'> No courses found </option>";
}


//get potential courses 
$result=$DB->get_records_sql("SELECT c.* FROM {course} AS c LEFT JOIN ($sql1) AS b ON c.id=b.courseid WHERE b.courseid IS NULL AND c.id!=1"); //using $sql1 get records of all courses that this dept is 'NOT' enrolled in 
//first record in course table is for site name 
$potential_select='';
foreach($result as $record) {
	$potential_select .= "<option value='$record->id'> $record->fullname ($record->shortname) </option>";
}
if($potential_select=='') { //if no course found 
	$potential_select = "<option disabled='disabled'> No courses found </option>";
}


echo $OUTPUT->header();


if(count($errors)>0) { //if errors exist 
	echo "<div style='background-color: #ffcccc; padding:3px'>";
	foreach($errors as $error) { //$errors array used in 'form_process.php' 
		echo $error;
	}
	echo "</div>";
}

echo html_writer::start_tag('a', array('href'=>'../nodes/node.php?prefix=locate&frameworkid=' . $deptrecord->frameworkid)) .
	html_writer::start_tag('font', array('size'=>'2')).
		"&lt&lt " . "Back to " . $branchrecord->fullname . 
	html_writer::end_tag('font') . 
html_writer::end_tag('a');


echo $OUTPUT->heading('Department enrolment');


?>

<form id="assignform" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>"><div>

  <input type="hidden" name="deptid" value="<?php echo $deptid; ?>">
  <!-- hidden element to store deptid -->

  <table summary="" class="roleassigntable generaltable generalbox boxaligncenter" cellspacing="0">
    <tr>
      <td id="existingcell">
          <p><label for="removeselect"><?php echo "Enrolled courses"; ?></label></p>
          <?php display_select($existing_select, 'existing'); ////////////////left select ?>
      </td>
      <td id="buttonscell">
          <div id="addcontrols">
              <input name="add" id="add" type="submit" value="<?php echo $OUTPUT->larrow().'&nbsp;'.get_string('add'); ?>" title="<?php print_string('add'); ?>" /><br />
			  <!----------------------- central selects -->

              <div class="enroloptions">

              <p><label for="menuroleid"><?php print_string('assignrole', 'enrol_manual') ?></label><br />
              <?php echo html_writer::select($roles, 'roleid', 5, false); ?></p>

              <p><label for="menuperiod"><?php print_string('enrolperiod', 'enrol') ?></label><br />
              <?php echo html_writer::select($periodmenu, 'period', 0, false); ?></p>

              </div>
          </div>

          <div id="removecontrols">
              <input name="remove" id="remove" type="submit" value="<?php echo get_string('remove').'&nbsp;'.$OUTPUT->rarrow(); ?>" title="<?php print_string('remove'); ?>" />
          </div>
      </td>
      <td id="potentialcell">
          <p><label for="addselect"><?php echo "Not enrolled courses"; ?></label></p>
          <?php display_select($potential_select, 'potential'); //////////////////////// right select ?>
      </td>
    </tr>
  </table>
</div></form>

<?php

echo $OUTPUT->footer();


function display_select($select, $name) {
	
	$text1='';
	if($name=='potential') {
		$text1='Not enrolled courses';
	} elseif($name=='existing') {
		$text1='Enrolled courses';
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


function set_names(&$array) { //array passed by reference 

	foreach($array as $value=>&$text) { //$text passed by reference, otherwise foreach() doesn't modify the original array 	
	
		//names of these roles not given by moodle in 'role' table 
		if($value==1) {$text = "Manager";}
		if($value==2) {$text = "Course creator";}
		if($value==3) {$text = "Teacher";}
		if($value==4) {$text = "Non-editing teacher";}
		if($value==5) {$text = "Student";}
		if($value==6) {$text = "Guest";}
		if($value==7) {$text = "User";}
		if($value==8) {$text = "Frontpage";}

		//this is not a problem as long as the 'role' table is not truncated, something which cannot be done from the front end
	}
	
	unset($text); //unset $text to stop referencing in future 
	
}


?>

<script> var dept_id = <?php echo $deptid; ?> </script> <!-- global variable dept_id for external js to use -->

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

<script src="search.js?new=<?php echo time(); ?>"></script> <!-- echo time to prevent caching --> 

<link rel="stylesheet" type="text/css" href="custom.css?new=<?php echo time(); ?>">

