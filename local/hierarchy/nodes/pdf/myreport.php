<?php
require('../../../../config.php');


global $DB,$CFG,$USER,$OUTPUT;
require_once($CFG->dirroot.'/lib/enrollib.php');
require_once($CFG->dirroot.'/lib/completionlib.php');
require_once($CFG->dirroot.'/lib/filestorage/file_storage.php');


$userid = $USER->id;



$courses = $DB->get_records_sql("select * from {course} where id > 1");
$currentUser = $DB->get_record('user', array('id' => $userid)); 

$totalTime =  (time() - $currentUser->timecreated);
$totalTime =secondsToWords($totalTime);

$phone = '-';

if($currentUser->phone1 !='') {
	$phone = $currentUser->phone1;
}

$country="-";
if($currentUser->country !='' && $currentUser->country ) {
	$country = get_string($currentUser->country, 'countries');
}

$userCourses = enrol_get_users_courses($currentUser->id, $onlyactive = true, $fields = null, $sort = null);



// print_object($userCourses);
$userCoursesCount = count($userCourses);

if($currentUser->lastaccess)
{
	$lastaccess=date('d/m/Y', $currentUser->lastaccess).'<br>'.date('h:i:s', $currentUser->lastaccess);
}else
{
	$lastaccess="-";
}

$userDepartement = $DB->get_record_sql('select * from {loc} where id = '.$currentUser->dept.'');
$userBranch = $DB->get_record_sql('select * from {loc_framework} where id = '.$currentUser->branch.'');
$poweruser='-';
if($USER->deptpoweruser || $USER->branchpoweruser)
{
	$poweruser='Power User';
}
// print_object($userCourses);
// print_object($userBranch);

echo $OUTPUT->header();
// echo '<img src="'.$OUTPUT->favicon().'" alt="images" width="100"/>';

$html='
		<link rel="stylesheet" href="css/circle.css">
		<link rel="stylesheet" href="css/line.css">

		
		<form class="submitForm'.$userid.'" method = "post" action = "tcpdftest.php">
			<input type="hidden" id="svgHidden" name="hiddenelement" value="">
			<input type="hidden" id="userid" name="userid" value="'.$userid.'">
			<button type="submit" id="btn" class="pull-right">Print</button>
		</form>
		<div class="clear-fix"></div>
		
		<div>
			<div class="">
				<table class="table table-border pdf-table">
					<tr>
						<td rowspan="2" style="vertical-align:middle">
							<div class="">
									'.$OUTPUT->user_picture($USER, array('size'=>200)).'
							</div>
						</td>
						<td align="center">
							<div>
								<h3>'.$currentUser->firstname.'<br>'.$currentUser->lastname.'</h3>
								<p>'.$currentUser->email.'</p>
							</div>
							
						</td>
						<td align="center">
							<div class="">
								<div class="">
									<img src="usericon.png" style="width:60px;" alt="images" />
								</div>
								<h3 style="color:#0465ac">'.date('d/m/Y', $currentUser->timecreated).'<br>
								    '.date('h:i:s', $currentUser->timecreated).'
								</h3>
								<p>Subscription date </p>
							</div>
						</td>
						<td align="center">
							<div class="">
								<div class="">
									<img src="calander.png" style="width:60px" alt="images" />
								</div>
								<h3 style="color:#0465ac">'.$lastaccess.'</h3>
								<p>Last Access Date</p>
							</div>
						</td>
					</tr>
					
					
					<tr>
						<td>
							<table class="table-border pdf-table">
								<!-- commented <tr>
									<td><b>Level</b></td>
									<td>'.$poweruser.'</td>
								</tr> -->
								
								<tr>
									<td><b>E-mail </b></td>
									<td>'.$currentUser->email.'</td>
								</tr>
								
								
							</table>
						</td>
						<td align="center" style="border-top:none;">
							<div class="">
								<div class="">
									<img src="alarmicon.png" style="width:60px" alt="images" />
								</div>
								<h3 style="color:#0465ac">'.$totalTime.'</h3>
								<p>Total time </p>
							</div>
						</td>
						
						<td align="center" style="border-top:none;">
							<div class="">
								<div class="">
									<img src="course.png" style="width:60px" alt="images" />
								</div>
								<h3 style="color:#0465ac">'.$userCoursesCount.'</h3>
								<p>Active courses </p>
							</div>
						</td>
						
					</tr>
				</table>
			</div>
			<div class="clear:both"></div>
			<div class="row">
				<div class="col-md-6">
					<table class="table table-border pdf-table">
						<tr>
							<td colspan="2"><b>Branch</b></td>
							<td colspan="2">'.$userBranch->fullname.'</td>
						</tr>
						
						
						<tr>
							<td colspan="2"><b>City </b></td>
							<td colspan="2">'.$currentUser->city.'</td>
						</tr>
						
						<!-- commented <tr>
							<td colspan="2"><b>Company</b></td>
							<td colspan="2">Vaidusi</td>
						</tr> -->
						
						<tr>
							<td colspan="2"><b>Mobile No. </b></td>
							<td colspan="2">'.$phone.'</td>
						</tr>
					</table>
				</div>
				<div class="col-md-6">
					<table class="table table-border pdf-table">
						<tr>
							<td colspan="2"><b>Department Name</b></td>
							<td colspan="2">'.$userDepartement->fullname.'</td>
						</tr>
						
						<!-- commented <tr>
							<td colspan="2"><b>Job Role </b></td>
							<td colspan="2">Learner</td>
						</tr> -->
						
						<tr>
							<td colspan="2"><b>Country </b></td>
							<td colspan="2">'.$country.'</td>
						</tr>
						
						<tr>
							<td colspan="2"><b>Mailing Address</b></td>
							<td colspan="2">'.$currentUser->email.'</td>
						</tr>
						
					</table>
				</div>
			</div>
				
			<div style="clear:both"></div>';
			
			
			$nhtml='
			
			<table class="table table-border pdf-table">
				<tr>
					<td><b>Course Code</b></td>
					<td><b>Course Name</b></td>
					<td><b>User Status</b></td>
					<td><b>Enrolled</b></td>
					<td><b>First Access Date</b></td>
					<td><b>Course<br>Completion</b></td>
					<td><b>Score</b></td>
				</tr>';

			$progresscount=0;
			$completecount=0;
			foreach($courses as $course)
			{
				 $coursecontext=context_course::instance($course->id);
				if(!is_enrolled($coursecontext,$currentUser->id))
				{
					countinue;
				}else{
					
					$enrolText = '';
					$timeCompleted = '';
					
					
					$enrolidData = $DB->get_records_sql("select * from {enrol} where courseid = $course->id");
					// echo "select * from {enrol} where courseid = $course->id";
					// print_object($enrolidData);
					foreach($enrolidData as $enrolid) {
						$enrolText .= $enrolid->id.',';
						
					}
					
					$enrolText = trim($enrolText, ',');
					$userEnrolled = $DB->get_record_sql("select * from {user_enrolments} where enrolid in($enrolText) and userid = $currentUser->id limit 0, 1");

					
					$completion=new completion_info($course);
					if($completion->is_course_complete($currentUser->id))
					{
						
						$completiontext="Completed";
						
						$time_completion = $DB->get_record_sql("select * from {course_completions} where userid = $currentUser->id and course = $course->id");
						
						$timeCompleted = date("d/m/Y",$time_completion->timecompleted);
						$completecount++;
					}else{
						$completiontext="In progress";
						$timeCompleted = "-";
						$progresscount++;
					}


					///////////////////
					$score = '-';

				    $sql0="SELECT * FROM {grade_items} WHERE courseid=$course->id AND itemtype!='course' ";

				   	$sql1="SELECT gg.id, gi.grademax, gg.finalgrade FROM ($sql0) AS gi INNER JOIN {grade_grades} AS gg ON gi.id=gg.itemid WHERE gg.userid=$currentUser->id AND gg.finalgrade IS NOT NULL";

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
				    ///////////////
					
					
				$nhtml.='<tr>
							<td>'.$name=$course->shortname.'</td>
							<td>'.$fname=$course->fullname.'</td>
							<td>'.$completiontext.'</td>';
							
							
								$nhtml.='<td>'.date('d/m/y', $userEnrolled->timecreated).' '. date('h:i:s', $userEnrolled->timecreated) .'</td>';
							
							$nhtml.='<td>'.date('d/m/y', $userEnrolled->timestart).'</td>
							<td>'.$timeCompleted.'</td>
							
							<td>' . $score . '</td>
						</tr>';
				}
			}
			
		$nhtml.='</table>
		
		
	';
				



if($progresscount)
{
	$progressper=round(($progresscount*100)/$userCoursesCount,2);
	$progresssvg=round(($progresscount*78.53975)/$userCoursesCount,2);
}else{
	$progressper=0;
	$progresssvg=0;
}
if($completecount)
{
	$completeper=round(($completecount*100)/$userCoursesCount,2);
	$compsvg=round(($completecount*59.69021)/$userCoursesCount,2);
}else{
	$completeper=0;
	$compsvg=0;
}





$svgdata='
	<div class="row">
		
		<div class="col-md-4">
			<h3>Progress </h3>
			<svg viewBox="0 0 36 36" class="circular-chart green"
						style="display: block;
								margin: 10px auto;
								max-width: 75%;
								max-height: 75%;"
			>
			  <path class="circle-bg"
				style="fill: none;
						stroke: #eee;
						stroke-width: 3.8;"
				d="M18 2.0845
				  a 15.9155 15.9155 0 0 1 0 31.831
				  a 15.9155 15.9155 0 0 1 0 -31.831"
			  />
			  <path class="circle"
				  style="fill: none;
						  stroke-width: 2.8;
						  stroke-linecap: round;
						  stroke: #0465ac;"
				stroke-dasharray="0, 100"
				d="M18 2.4
				  a 15.6 15.6 0 0 1 0 31.2
				  a 15.6 15.6 0 0 1 0 -31.2"
			  />

			  <path class="circle"
				  style="fill: none;
						  stroke-width: 2.8;
						  stroke-linecap: round;
						  stroke: #52a1dc"
				stroke-dasharray="'.$progresssvg.', 78.53975"
			   d="M18 5.5
				  a 12.5 12.5 0 0 1 0 25.0
				  a 12.5 12.5 0 0 1 0 -25.0"
			  />
			  
			   <path class="circle"
				  style="fill: none;
						  stroke-width: 2.8;
						  stroke-linecap: round;
						  stroke: #e84c3d"
				stroke-dasharray="'.$compsvg.', 59.69021"
				d="M18 8.5
				  a 9.5 9.5 0 0 1 0 19.0
				  a 9.5 9.5 0 0 1 0 -19.0"
			  />
			</svg>
		</div >
		<div class="col-md-2">
			<div class="custom-add-margin">
				<span style="color:#0465ac">TO BEGIN 0(0%)<br></span>
				<span style="color:#52a1dc">IN Progress '.$progresscount.'('.$progressper.'%)<br></span>
				<span style="color:#e84c3d">COMPLETED '.$completecount.'('.$completeper.'%)<br></span>
			</div>
		</div>
	
		<div class="col-md-6" >
			<div id="linegrp" >
			</div>
		</div>
	</div>
 ';











echo $html;
echo $svgdata;
echo $nhtml;

$time=time();
$userid=$record->userid;
	$xval="[";
	//$xval.="'".Date("F",$time)."',";

	$data="[";
	for($i=0;$i<12;$i++)
	{
					
		$prevtime=strtotime("-$i months ",$time);

		$nexttime=strtotime('-1 months',$prevtime);
		
		$datacount=$DB->get_records_sql("select id from {course_modules_completion} where userid=".$USER->id." and timemodified>$nexttime and timemodified<$prevtime");
		

		$data.=count($datacount).",";
		
		$xval.="'".Date("F",$prevtime)."',";
	}

	$xval=rtrim($xval,",");
	$xval.="]";


	$data=rtrim($data,",");
	$data.="]";

function secondsToWords($seconds)
{
    $ret = "";

    /*** get the days ***/
    $days = intval(intval($seconds) / (3600*24));
    if($days> 0)
    {
        $ret .= "$days D ";
    }

    /*** get the hours ***/
    $hours = (intval($seconds) / 3600) % 24;
    if($hours > 0)
    {
        $ret .= "$hours H ";
    }

    /*** get the minutes ***/
    $minutes = (intval($seconds) / 60) % 60;
    if($minutes > 0)
    {
        $ret .= "$minutes M ";
    }

    /*** get the seconds ***/
	/* 
    $seconds = intval($seconds) % 60;
    if ($seconds > 0) {
        $ret .= "$seconds S";
    } */

    return $ret;
}
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script src="js/highcharts.js"></script>
<script src="js/exporting.js"></script>
<script>
$( document ).ready(function() {
$("#linegrp").highcharts({

    title: {
        text: 'See all activity '
    },

    subtitle: {
        text: 'within the last 12 months'
    },
	xAxis: {
			
			title: {
				text: 'Number of Sessions'
			},
			categories: <?php echo $xval; ?>
		  },
    yAxis: {
		minRange: 2,
        title: {
            text: 'Number of Activities'
        }
    },
    legend: {
        layout: 'vertical',
        align: 'right',
        verticalAlign: 'middle'
    },

    plotOptions: {
        series: {
            label: {
                connectorAllowed: true
            },
        }
    },

    series: [{
        name: 'Activities',
        data: <?php echo $data; ?>
    },],

});
	
	
	//$("#linegrp").append(x);
	$(".highcharts-button").hide();
	get_svg($("#linegrp").highcharts());
});


	
	
function get_svg(chart) {
	// alert(ele);
  var svg = chart.getSVG();
  $('#svgHidden').val(svg);
	
}
</script>
<?php echo $OUTPUT->footer();
?>