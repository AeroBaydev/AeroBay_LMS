<?php

require('../../../config.php');
require('../lib.php');

global $CFG, $DB, $PAGE;

require_capability('local/hierarchy:manage', context_system::instance(), null, true, "Capability 'Manage hierarchies' required"); //check capability 

$deptid = required_param('deptid', PARAM_INT);




////page setup
require_once($CFG->libdir . '/adminlib.php'); 
admin_externalpage_setup('locate'); 

$deptrecord=$DB->get_record('loc', array('id'=>$deptid, 'deleted'=>0));
$branchrecord=$DB->get_record('loc_framework', array('id'=>$deptrecord->frameworkid, 'deleted'=>0));

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/hierarchy/dept_category_assignment/assignment.php', array('deptid'=>$deptid));
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Department enrolment');
$PAGE->set_heading('Department enrolment');

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('site_administration', 'local_hierarchy'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('hierarchies', 'local_hierarchy'), new moodle_url('/admin/category.php?category=hierarchy'));
$PAGE->navbar->add(get_string('locate_frameworks', 'local_hierarchy'), new moodle_url('/local/hierarchy/nodes/node_framework.php?prefix=locate'));
$PAGE->navbar->add($branchrecord->fullname, new moodle_url('/local/hierarchy/nodes/node.php?prefix=locate&frameworkid='.$deptrecord->frameworkid));
$PAGE->navbar->add($deptrecord->fullname . ' assignment');





//////////form processing
//this should be done before anything else to show updated select menu


if(isset($_POST['add'])) { //if user has selected 'Add' 
	
	if(isset($_POST["potential_select"])) { //if something is selected 
		
		$roleid = $_POST['roleid'];
		$timestart = time();
		
		$timeend = 0;
		
		if($_POST['period']!=0) {
			$timeend = $timestart + $_POST['period'];
		}
		
		$users = $DB->get_records('user_dept_enrolments', array('deptid'=>$deptid)); //get all users in that dept. These have to be enrolled. 
		
		// Retrieve each selected option 
		foreach($_POST['potential_select'] as $categoryid) { //select a category


			$category = $DB->get_record('course_categories', array('id'=>$categoryid)); //get record

			$params = array($category->path,        //path of the node itself
			"$category->path/%",        //path pattern of descendents 
			);	

			$sql = "SELECT * from {course_categories} WHERE (path = ? || path LIKE ?)"; //select the node itself and its descendents

			$result=$DB->get_records_sql($sql, $params); //get records of the category and its subcategories

			foreach($result as $cat) { //iterate through the categories

				if( !($DB->record_exists('dept_category_enrolments', array('categoryid'=>$cat->id, 'deptid'=>$deptid))) ) { //check that a category isn't already added for this dept

					$courselist=$DB->get_records('course', array('category'=>$cat->id)); //get courses in the category

					foreach($courselist as $course) {

						$check = $DB->record_exists('enrol', array('courseid'=>$course->id, 'enrol'=>'manual')); //check if manual enrolment is added for that course 
			
						if($check==true) { //if manual enrolment is added for the course 

							foreach($users as $user) { //iterate through users 
						
								enrol($course->id, $user->userid, $roleid, $timestart, $timeend); //enrol the users 
									
							}

							//update 'dept_enrolments' table accordingly because user sync (automatic enrolment of user in course when dept is assigned while creating user) happens according to it

							$enrol_record = $DB->get_record('enrol', array('courseid'=>$course->id, 'enrol'=>'manual')); //get manual enrolment record of that course

							if( $DB->record_exists('dept_enrolments', array('enrolid'=>$enrol_record->id, 'deptid'=>$deptid)) ) { //if entry of course is already there

								$time=time();

								$sql="UPDATE {dept_enrolments} SET roleid=$roleid, timestart=$timestart, timeend=$timeend, modifierid=$USER->id, timemodified=$time WHERE enrolid=$enrol_record->id AND deptid=$deptid";

								$DB->execute($sql); //update record with category settings 

							} else {

								$dept_enrolment_record = new stdclass();

								$dept_enrolment_record->enrolid = $enrol_record->id;
								$dept_enrolment_record->deptid = $deptid;
								$dept_enrolment_record->roleid = $roleid;
								$dept_enrolment_record->timestart = $timestart;
								$dept_enrolment_record->timeend = $timeend;
								$dept_enrolment_record->modifierid = $USER->id;
								$dept_enrolment_record->timecreated = time();
								$dept_enrolment_record->timemodified = time();

								$DB->insert_record('dept_enrolments', $dept_enrolment_record, $returnid=false, $bulk=false);
								
							}

						} //if check

					} //course loop

					$record = new stdclass();
		
					$record->categoryid = $cat->id;
					$record->deptid = $deptid;
					$record->roleid = $roleid;
					$record->timestart = $timestart;
					$record->timeend = $timeend;
					$record->modifierid = $USER->id;
					$record->timecreated = time();
					$record->timemodified = time();

					$DB->insert_record('dept_category_enrolments', $record, $returnid=false, $bulk=false);
					//make entry of the category

				} //if category isn't added already
				
			} //iteration through all categories


		} //selected category
		
	} //if potential_select
		
} //if add


if(isset($_POST['remove'])) { //if user has selected 'Remove' 
	
	if(isset($_POST["existing_select"])) { //if something is selected 
		
		$users = $DB->get_records('user_dept_enrolments', array('deptid'=>$deptid)); //get all users in that dept. These have to be unenrolled. 
		
		//Deleted users are automatically unenrolled from all courses by moodle
		
		// Retrieve each selected option
		foreach($_POST['existing_select'] as $categoryid) { //select a category 
		
			
			$category = $DB->get_record('course_categories', array('id'=>$categoryid));

			$params = array($category->path,        //path of the node itself
			"$category->path/%",        //path pattern of descendents 
			);	

			$sql = "SELECT * from {course_categories} WHERE (path = ? || path LIKE ?)"; //select the node itself and its descendents

			$result=$DB->get_records_sql($sql, $params); //get records of the category and its subcategories

			foreach($result as $cat) { //iterate through the categories

				$courselist=$DB->get_records('course', array('category'=>$cat->id));

				foreach($courselist as $course) {

					$check = $DB->record_exists('enrol', array('courseid'=>$course->id, 'enrol'=>'manual')); //check if manual enrolment is added for that course 
		
					if($check==true) { //if manual enrolment is added for the course 

						foreach($users as $user) { //iterate through users 
							unenrol($course->id, $user->userid); //unenrol the users
						}

						//update the 'dept_enrolments' table

						$enrol_record = $DB->get_record('enrol', array('courseid'=>$course->id, 'enrol'=>'manual')); //get manual enrolment record of that course

						$DB->delete_records('dept_enrolments', array('enrolid'=>$enrol_record->id, 'deptid'=>$deptid));
						//entry would exist only if manual enrolment exists for the course, because when manual enrolment is deleted we delete the entry in 'dept_enrolments' table through 'enrol_intance_delete' event

					} //if manual enrolment was deleted, users would have been automatically unenrolled from the course

				}
				
				$DB->delete_records('dept_category_enrolments', array('categoryid'=>$cat->id, 'deptid'=>$deptid));
				//delete entry of the category

				//no issues if some subcategories (of the selected main category) are already deleted

			} //iteration through all categories


		} //selected category	
	
	} //if existing_select
	
} //if remove

////////////





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




$sql1="SELECT d.categoryid FROM {dept_category_enrolments} AS d WHERE d.deptid=$deptid"; //query to get ids of all categories that this dept is enrolled in 


//get potential categories ///////////
$potential_result=$DB->get_records_sql("SELECT c.* FROM {course_categories} AS c LEFT JOIN ($sql1) AS b ON c.id=b.categoryid WHERE b.categoryid IS NULL"); //using $sql1 get records of all categories that this dept is 'NOT' enrolled in 

$potential_list=array(); 
set_child_nodes_list($potential_result, 0, 0, $potential_list, null); //make hierarchical list

$potential_select='';

foreach($potential_list as $item) {

	$record = $DB->get_record('course_categories', array('id'=>$item->id));

	$space='';
	for($i=0; $i<$item->depth; $i++) {$space .= '&nbsp;&nbsp;&nbsp;';}

	$potential_select .= "<option value='$record->id'> $space $record->name </option>";

}

if($potential_select=='') { //if no category found 
	$potential_select = "<option disabled='disabled'> No category found </option>";
}

/////////////


//get existing categories ///////////
$existing_result=$DB->get_records_sql("SELECT c.* FROM {course_categories} AS c INNER JOIN ($sql1) AS b ON c.id=b.categoryid"); //using $sql1 get records of all categories that this dept is enrolled in 

$existing_list=array(); 
set_child_nodes_list($existing_result, 0, 0, $existing_list, null); //make hierarchical list

$existing_select=''; 

foreach($existing_list as $item) {

	$record = $DB->get_record('course_categories', array('id'=>$item->id));

	$space='';
	for($i=0; $i<$item->depth; $i++) {$space .= '&nbsp;&nbsp;&nbsp;';}

	$existing_select .= "<option value='$record->id'> $space $record->name </option>";

}

if($existing_select=='') { //if no category found 
	$existing_select = "<option disabled='disabled'> No category found </option>";
}

/////////////




echo $OUTPUT->header();


echo html_writer::start_tag('a', array('href'=>'../nodes/node.php?prefix=locate&frameworkid=' . $deptrecord->frameworkid)) .
	html_writer::start_tag('font', array('size'=>'2')).
		"&lt&lt " . "Back to " . $branchrecord->fullname . 
	html_writer::end_tag('font') . 
html_writer::end_tag('a');


echo $OUTPUT->heading("$deptrecord->fullname category enrolment");


?>

<form id="assignform" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>"><div>

  <input type="hidden" name="deptid" value="<?php echo $deptid; ?>">
  <!-- hidden element to store deptid -->

  <table summary="" class="roleassigntable generaltable generalbox boxaligncenter" cellspacing="0">
    <tr>
      <td id="existingcell">
          <p><label for="removeselect"><?php echo "Enrolled categories"; ?></label></p>
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
          <p><label for="addselect"><?php echo "Not enrolled categories"; ?></label></p>
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
		$text1='Not enrolled categories';
	} elseif($name=='existing') {
		$text1='Enrolled categories';
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

