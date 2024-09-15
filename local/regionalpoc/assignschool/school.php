<?php
require_once "../../../config.php";
require_once(__DIR__ . '/form_process.php');
require_login();
// require_capability('moodle/site:config', context_system::instance());
global $DB, $OUTPUT, $PAGE;
// $PAGE->requires->css(new moodle_url('/local/assign_school_ARM/style.css'));
$pocId = required_param('id', PARAM_INT);
$usertype = optional_param('usertype', '', PARAM_TEXT);
$_SESSION['usertype']=$usertype;

//$PAGE->requires->js(new moodle_url('/local/poc/script.js'));

$PAGE->set_context(context_system::instance());
$PAGE->set_url("$CFG->wwwroot/local/regionalpoc/assignschool/school.php", array('id'=>$pocId));
$PAGE->set_pagelayout('course');
$PAGE->set_title('School management');
$PAGE->set_heading('School management');
if (is_siteadmin()) {
                
    $PAGE->navbar->add('POC Management', "$CFG->wwwroot/local/regionalpoc/assignschool/");        
    $PAGE->navbar->add("Assign School's", "$CFG->wwwroot/local/regionalpoc/assignschool/?userId=$pocId");
}
else{
	$PAGE->navbar->add("Assign School's", "$CFG->wwwroot/local/regionalpoc/assignschool/?userId=$pocId");
    
}


// echo "<a href=\"{$CFG->wwwroot}/local/regionalpoc/rm_arm_manage.php?roleid=4\" class='btn btn-primary'>BACK</a>";


if($_SESSION['caturlid'])
{

	$getpocid=$_SESSION['caturlid'];
	$usertype=$_SESSION['usertype'];
}
else{
	$getpocid=$pocId;
	$_SESSION['caturlid']=$getpocid;
	$_SESSION['usertype']=$usertype;
}


//get existing school
$result=$DB->get_records_sql("SELECT cc.* FROM {course_categories} cc  join {schoolassign} sa on sa.schoolid=cc.id where sa.userid=$getpocid"); //get records of all users that are enrolled in the dept
$existing_select=''; 

foreach($result as $record) {
	$existing_select .= "<option value='$record->id'> $record->name</option>";

	
}
if($existing_select=='') { //if no user found 
	$existing_select = "<option disabled='disabled'> No school found </option>";
}

// print_r($existUserArray);
// die;

$assignrecord=$DB->get_records('schoolassign', array('schoolassignby'=>$USER->id));
$existUserArray=[];
foreach ($assignrecord as $key => $value) {
	$existUserArray[]=$value->schoolid;
}

$notcatid=0;
if(!empty($existUserArray))
{
	$notcatid = implode(',',$existUserArray);
}

$result=$DB->get_records_sql("SELECT cc.* FROM {course_categories} cc  join {schoolassign} s on s.schoolid=cc.id where s.userid=$USER->id and cc.id not in($notcatid)"); 
$potential_select='';
foreach($result as $record) {
	$potential_select .= "<option value='$record->id'> $record->name</option>";
}
if($potential_select=='') { //if no user found 
	$potential_select = "<option disabled='disabled'> No school found </option>";
}


echo $OUTPUT->header();


echo html_writer::start_tag('a', array('href'=>'../rm_arm_manage.php?usertype='.$_SESSION['usertype'].'')) .
	html_writer::start_tag('font', array('size'=>'2')).
		"&lt&lt " . "Back to " ."". 
	html_writer::end_tag('font') . 
html_writer::end_tag('a');


?>

<form id="assignform" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>"><div>

  <input type="hidden" name="id" value="<?php echo $pocId; ?>">

  <input type="hidden" name="usertype" value="<?php echo $usertype; ?>">
  <!-- hidden element to store deptid -->
<?php $_SESSION['caturlid'] =$pocId; ?>
  <!-- school list-->
   

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


