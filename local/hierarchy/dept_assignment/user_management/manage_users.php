<?php

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../../lib.php');

require_once(__DIR__ . '/form_process.php'); //contains code to process submitted data 
require_once($CFG->dirroot.'/cohort/lib.php');

global $CFG, $DB, $PAGE;

require_capability('local/hierarchy:manage', context_system::instance(), null, true, "Capability 'Manage hierarchies' required"); //check capability 

$deptid = required_param('deptid', PARAM_INT);

require_once($CFG->libdir . '/adminlib.php'); 
admin_externalpage_setup('locate'); 

$deptrecord=$DB->get_record('loc', array('id'=>$deptid, 'deleted'=>0));

if($deptrecord->idnumber){
$cohortrecord=$DB->get_record('cohort', array('idnumber'=>$deptrecord->idnumber));
}

$branchrecord=$DB->get_record('loc_framework', array('id'=>$deptrecord->frameworkid, 'deleted'=>0));

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/hierarchy/dept_assignment/user_management/manage_users.php', array('deptid'=>$deptid));
$PAGE->set_pagelayout('course');
$PAGE->set_title('User management');
$PAGE->set_heading('User management');

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('site_administration', 'local_hierarchy'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('hierarchies', 'local_hierarchy'), new moodle_url('/admin/category.php?category=hierarchy'));
$PAGE->navbar->add(get_string('locate_frameworks', 'local_hierarchy'), new moodle_url('/local/hierarchy/nodes/node_framework.php?prefix=locate'));
$PAGE->navbar->add($branchrecord->fullname, new moodle_url('/local/hierarchy/nodes/node.php?prefix=locate&frameworkid='.$deptrecord->frameworkid));
$PAGE->navbar->add($deptrecord->fullname . ' user management');


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
$result=$DB->get_records_sql("SELECT * FROM {user} WHERE  id!=1 AND deleted=0"); //get records of all users that are 'NOT enrolled in any dept' and ('Not enrolled in any branch' or 'enrolled in the selected branch')
//first record in user table is for guest
$potential_select='';
foreach($result as $record) {
	$potential_select .= "<option value='$record->id'> $record->firstname $record->lastname ($record->email) </option>";
}
if($potential_select=='') { //if no user found 
	$potential_select = "<option disabled='disabled'> No users found </option>";
}


echo $OUTPUT->header();


echo html_writer::start_tag('a', array('href'=>'../../nodes/node.php?prefix=locate&frameworkid=' . $deptrecord->frameworkid)) .
	html_writer::start_tag('font', array('size'=>'2')).
		"&lt&lt " . "Back to " . $branchrecord->fullname . 
	html_writer::end_tag('font') . 
html_writer::end_tag('a');


echo $OUTPUT->heading('User management');


?>

<form id="assignform" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>"><div>

  <input type="text" name="deptid" value="<?php echo $deptid; ?>">
  <!-- hidden element to store deptid -->

  <!-- hidden element to store cohortid -->
   <input type="hidden" name="cohortid" value="<?php echo $cohortrecord->id; ?>">

  <table summary="" class="roleassigntable generaltable generalbox boxaligncenter" cellspacing="0">
    <tr>
      <td id="existingcell">
          <p><label for="removeselect"><?php echo "Existing users"; ?></label></p>
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
          <p><label for="addselect"><?php echo "Potential users"; ?></label></p>
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
		$text1='Potential users';
	} elseif($name=='existing') {
		$text1='Existing users';
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

?>

<script> var dept_id = <?php echo $deptid; ?> </script> <!-- global variable dept_id for external js to use -->

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

<script src="search.js?new=<?php echo time(); ?>"></script> <!-- echo time to prevent caching --> 

<link rel="stylesheet" type="text/css" href="custom.css?new=<?php echo time(); ?>">

