<?php

require('../../../../config.php');
require('../../lib.php');

global $CFG, $DB, $PAGE;

require_capability('local/hierarchy:manage', context_system::instance(), null, true, "Capability 'Manage hierarchies' required"); //check capability 

$branchid = required_param('branchid', PARAM_INT);

require_once($CFG->libdir . '/adminlib.php'); 
admin_externalpage_setup('locate'); 

$branchrecord=$DB->get_record('loc_framework', array('id'=>$branchid, 'deleted'=>0));

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/hierarchy/poweruser_assignment/branch/assignment.php', array('branchid'=>$branchid));
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Assign powerusers');
$PAGE->set_heading('Assign powerusers');

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('site_administration', 'local_hierarchy'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('hierarchies', 'local_hierarchy'), new moodle_url('/admin/category.php?category=hierarchy'));
$PAGE->navbar->add(get_string('locate_frameworks', 'local_hierarchy'), new moodle_url('/local/hierarchy/nodes/node_framework.php?prefix=locate'));
$PAGE->navbar->add($branchrecord->fullname . ' poweruser assignment');


$poweruser_shortnames=array('branch_unit_admin', 'branch_enrollment_admin', 'branch_reporting_admin'); //shortnames of poweruser roles


/////////////form processing

if(isset($_POST['add'])) { //if user has selected 'Add' 
	
	if(isset($_POST["potential_select"])) { //if something is selected 

		$roleid = $_POST['roleid'];

		if($roleid!=0) { // if a role is selected
		
			//Retrieve each selected option
			foreach($_POST['potential_select'] as $userid) { //select a user 
			
				$DB->execute("UPDATE {user} SET branchpoweruser=1 WHERE id=$userid"); //update field in user

				$context = context_system::instance();
				role_assign($roleid, $userid, $context->id); //no error given if user already has the role

			}

		}	

	} //if potential_select
	
} //if add


if(isset($_POST['remove'])) { //if user has selected 'Remove' 
	
	if(isset($_POST["existing_select"])) { //if something is selected 

		foreach($_POST['existing_select'] as $userid) { //select a user 
		
			$DB->execute("UPDATE {user} SET branchpoweruser=0 WHERE id=$userid"); //update field in user table

			foreach($poweruser_shortnames as $shortname) {

				$role = $DB->get_record('role', array('shortname'=>$shortname)); 
				
				$context = context_system::instance();
				role_unassign($role->id, $userid, $context->id); //no error given if user doesn't actually have the role
				
			}
		
		}
	
	} //if existing_select
	
} //if remove

/////////////////


//get poweruser roles
$roles = array();

foreach($poweruser_shortnames as $shortname) {

	if( $DB->record_exists('role', array('shortname'=>$shortname)) ) {
		$record = $DB->get_record('role', array('shortname'=>$shortname)); 
		$roles = $roles + array($record->id => $record->name);
	}

}


//get existing powerusers 
$result=$DB->get_records('user', array('branchpoweruser'=>1, 'branch'=>$branchid, 'deleted'=>0)); 
$existing_select=''; 
foreach($result as $record) {
	$existing_select .= "<option value='$record->id'> $record->firstname $record->lastname ($record->email) </option>";
}
if($existing_select=='') { //if no poweruser found 
	$existing_select = "<option disabled='disabled'> No users found </option>";
}


//get potential powerusers 
$result=$DB->get_records('user', array('branchpoweruser'=>0, 'branch'=>$branchid, 'deleted'=>0)); 
$potential_select='';
foreach($result as $record) {
	$potential_select .= "<option value='$record->id'> $record->firstname $record->lastname ($record->email) </option>";
}
if($potential_select=='') { //if no potential user found 
	$potential_select = "<option disabled='disabled'> No users found </option>";
}


echo $OUTPUT->header();

echo html_writer::start_tag('a', array('href'=>'../../nodes/node_framework.php?prefix=locate')) .
	html_writer::start_tag('font', array('size'=>'2')).
		"&lt&lt " . "Back to branches" . 
	html_writer::end_tag('font') . 
html_writer::end_tag('a');


echo $OUTPUT->heading("$branchrecord->fullname powerusers");


?>

<form id="assignform" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>"><div>

  <input type="hidden" name="branchid" value="<?php echo $branchid; ?>">
  <!-- hidden element to store branchid -->

  <table summary="" class="roleassigntable generaltable generalbox boxaligncenter" cellspacing="0">
    <tr>
      <td id="existingcell">
          <p><label for="removeselect"><?php echo "Existing powerusers"; ?></label></p>
          <?php display_select($existing_select, 'existing'); ////////////////left select ?>
      </td>
      <td id="buttonscell">
          <div id="addcontrols">
              <input name="add" id="add" type="submit" value="<?php echo $OUTPUT->larrow().'&nbsp;'.get_string('add'); ?>" title="<?php print_string('add'); ?>" /><br />

              <div class="enroloptions">

              <p><label for="menuroleid"><?php print_string('assignrole', 'enrol_manual') ?></label><br />
              <?php echo html_writer::select($roles, 'roleid', 0, false); ?></p>

              </div>
              
          </div>

          <div id="removecontrols">
              <input name="remove" id="remove" type="submit" value="<?php echo get_string('remove').'&nbsp;'.$OUTPUT->rarrow(); ?>" title="<?php print_string('remove'); ?>" />
          </div>
      </td>
      <td id="potentialcell">
          <p><label for="addselect"><?php echo "Potential powerusers"; ?></label></p>
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
		$text1='Potential powerusers';
	} elseif($name=='existing') {
		$text1='Existing powerusers';
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

<script> var branch_id = <?php echo $branchid; ?> </script> <!-- global variable branchid for external js to use -->

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

<script src="search.js?new=<?php echo time(); ?>"></script> <!-- echo time to prevent caching --> 

<link rel="stylesheet" type="text/css" href="custom.css?new=<?php echo time(); ?>">
