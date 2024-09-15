<?php
require('../../../../config.php');


global $DB,$CFG,$USER,$OUTPUT;
require('../../../../lib/tcpdf/tcpdf.php');
require_once($CFG->dirroot.'/lib/enrollib.php');
require_once($CFG->dirroot.'/lib/completionlib.php');
require_once($CFG->dirroot.'/lib/filestorage/file_storage.php');


$userid = required_param('userid', PARAM_INT);
$graphdata = optional_param('hiddenelement', 0, PARAM_RAW);



$courses = $DB->get_records_sql("select * from {course} where id > 1");
$currentUser = $DB->get_record('user', array('id' => $userid)); 

$totalTime =  (time() - $currentUser->timecreated);
$totalTime =secondsToWords($totalTime);

$phone = '-';

if($currentUser->phone1 !='') {
	$phone = $currentUser->phone1;
}
$country="-";
if($currentUser->country !='' && $currentUser->country) {
	$country = get_string($currentUser->country, 'countries');
}



$userCourses = enrol_get_users_courses($currentUser->id, $onlyactive = true, $fields = null, $sort = null);
$poweruser='-';
if($USER->deptpoweruser || $USER->branchpoweruser)
{
	$poweruser='Power User';
}


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
// print_object($userCourses);
// print_object($userBranch);

class MYPDF extends TCPDF {

//Page header
	public function Header() {
		// Logo
		//$image_file ='image.png';
		// $this->Rect(0,0,230,20,'F','',$fill_color = array(210, 38, 48));
		//$this->Image($image_file, 5, 10, 15, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
		// Set font
		//$this->SetFont('helvetica', 'B', 20);
		//$this->SetTextColor(255,255,255);
		// Title
		//$this->Cell(0, 15, 'Report Card', 0, false, 'C', 0, '', 0, false, 'M', 'M');
		//$this->SetTextColor(0,0,0);
		//make a dummy empty cell as a vertical spacer
	}

	// Page footer
	public function Footer() {
		// Position at 15 mm from bottom
		$this->SetY(-10);
		// Set font
		$this->SetFont('helvetica', 'I', 8);
		// Page number
		$this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
	}

    // Colored table
    public function ColoredTable($header,$data) {
        // Colors, line width and bold font
        $this->SetFillColor(255, 0, 0);
        $this->SetTextColor(255);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(0.3);
        $this->SetFont('', 'B');
        // Header
        $w = array(40, 35, 40, 45);
        $num_headers = count($header);
        for($i = 0; $i < $num_headers; ++$i) {
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
        }
        $this->Ln();
        // Color and font restoration
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
        // Data
        $fill = 0;
        foreach($data as $row) {
            $this->Cell($w[0], 6, $row[0], 'LR', 0, 'L', $fill);
            $this->Cell($w[1], 6, $row[1], 'LR', 0, 'L', $fill);
            $this->Cell($w[2], 6, number_format($row[2]), 'LR', 0, 'R', $fill);
            $this->Cell($w[3], 6, number_format($row[3]), 'LR', 0, 'R', $fill);
            $this->Ln();
            $fill=!$fill;
        }
        $this->Cell(array_sum($w), 0, '', 'T');
    }
}



// create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);



// set default header data
//$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 011', PDF_HEADER_STRING);


// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
// $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
// $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
// $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set font

// add a page
$pdf->AddPage();
// echo $htmlf;
$image_file =$OUTPUT->favicon();
$pdf->Rect(0,0,230,20,'F','',$fill_color = array(255, 255, 255));
$pdf->Image($image_file, 5, 10, 15, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
// Set font
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetTextColor(0,0,0);
// Title
$pdf->Cell(0, 15, 'My Activity Report', 0, false, 'C', 0, '', 0, false, 'M', 'M');
$pdf->SetTextColor(0,0,0);

$pdf->SetFont('helvetica', '', 11);
	
		$picx = 7;  // Picture horizontal position.
		$picy = 40;   // Picture vertical position.
		$picw = 40; // Picture width.
		$pich = 40; // Picture height.


        $context =context_user::instance($currentUser->id);

        // Get files in the user icon area.
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'user', 'icon', 0,'id desc');

        // Get the file we want to display.
        $file = null;
        foreach ($files as $filefound) {
            if (!$filefound->is_directory()) {
                $file = $filefound;
                break;
            }
        }
	$pdf->StartTransform();
	// set clipping mask
	$pdf->StarPolygon(30, 60, 100, 100, 2, 0, 0, 'CNZ');
        // Show image if we found one.
        if ($file) {
            $location = make_request_directory() . '/target';
            $file->copy_content_to($location);
			
		 $pdf->Image($location, $picx, $picy, $picw,$pich);
		}else{
			 $pdf->Image('adminimage.png', $picx, $picy, $picw,$pich);
		}
	$pdf->StopTransform();

$html='<html>
	<head>
		<style>
			.pdf-table tr td{
				padding-top:8px;
				border-bottom:1px solid #ccc;
			}
			.test{
				float:left;
				width:47%;
				margin:0px 10px;

			}
			.containers{
				width: 110px;
				height: 110px;
				margin: 100px auto;
			}
			

			.circle{
				position: relative;
				
				width: 100px;
				height: 100px;
				border-radius: 50px;
				background-color: #E6F4F7;
			}
			
			#activeBorder{
				text-align: center;
				margin:0 auto;
				width: 120px;
				height: 120px;
				border-radius: 60px;
				background-color: #39B4CC;
			}

			
		
			
		</style>
		<link rel="stylesheet" href="css/circle.css">
		<link rel="stylesheet" href="css/line.css">
	</head>
	
	<body>
	
		<div class="container" style="">
			<div class="">
				<table cellpadding="0" cellspacing="0" width="100%" class="pdf-table">
					<tr>
						<td rowspan="2" style="vertical-align:middle">
							<div class="">
								
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
							<table cellpadding="0" cellspacing="0" width="100%" class="pdf-table">
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
			<table  width="100%">
			<tr>
				<td  width="48%">
					<table  class="pdf-table" >
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
				</td>
				<td  width="4%">
				</td>
				<td  width="48%">
					<table class="pdf-table " >
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
				</td>
			</tr>
			</table>
				
			<div style="clear:both"></div>';
			
			
			$nhtml='
			<style>
				.pdf-table tr td{
					padding-top:8px;
					border-bottom:1px solid #ccc;
				}
			</style>
			<table cellpadding="1" class="pdf-table " >
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
		
		
	</body>
</html>';
				



if($progresscount)
{
	$progressper=round(($progresscount*100)/$userCoursesCount,2);
	$progresssvg=round(($progresscount*63)/$userCoursesCount,2);
}else{
	$progressper=0;
	$progresssvg=0;
}
if($completecount)
{
	$completeper=round(($completecount*100)/$userCoursesCount,2);
	$compsvg=round(($completecount*48)/$userCoursesCount,2);
}else{
	$completeper=0;
	$compsvg=0;
}






// echo $html;
$svgdata='
    <svg viewBox="0 0 36 36" class="circular-chart green"
				style="display: block;
						margin: 10px auto;
						max-width: 5%;
						max-height: 100px;"
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
        stroke-dasharray="0, 80"
        d="M18 2.4
          a 15.6 15.6 0 0 1 0 31.2
          a 15.6 15.6 0 0 1 0 -31.2"
      />

      <path class="circle"
		  style="fill: none;
				  stroke-width: 2.8;
				  stroke-linecap: round;
				  stroke: #52a1dc"
        stroke-dasharray="'.$progresssvg.', 63"
       d="M18 5.5
          a 12.5 12.5 0 0 1 0 25.0
          a 12.5 12.5 0 0 1 0 -25.0"
      />
	  
	   <path class="circle"
		  style="fill: none;
				  stroke-width: 2.8;
				  stroke-linecap: round;
				  stroke: #e84c3d"
        stroke-dasharray="'.$compsvg.', 48"
        d="M18 8.5
          a 9.5 9.5 0 0 1 0 19.0
          a 9.5 9.5 0 0 1 0 -19.0"
      />
    </svg>
 ';





$pdf->writeHTML($html, true, false, true, false, '');




if($svgdata)
{
	
	$pdf->MultiCell(30, 5, 'Progress', 0, 'L', 0, 1, '12', '190', true, 0, true);
	$pdf->ImageSVG('@'.$svgdata, $x=10, $y=200, $w='30', $h='40', '', $align='L', $palign='', $border=0, $fitonpage=true);
	
	// $pdf->Cell(70, 60, 'Text', 0, false, 'C', 0, '', 0, false, 'T', 'M');
	
	$txt = 'gdgdgd<br>gdg<br> dg';
	
	
	$pdf->MultiCell(30, 5, '<span style="color:#0465ac">TO BEGIN 0(0%)<br></span>', 0, 'L', 0, 1, '45', '205', true, 0, true);
	$pdf->MultiCell(30, 5, '<span style="color:#52a1dc">IN Progress '.$progresscount.'('.$progressper.'%)<br></span>', 0, 'L', 0, 1, '45', '215', true, 0, true);
	$pdf->MultiCell(30, 5, '<span style="color:#e84c3d">COMPLETED '.$completecount.'('.$completeper.'%)<br></span>', 0, 'L', 0, 1, '45', '225', true, 0, true);
}
if($graphdata)
{

	$pdf->ImageSVG('@'.$graphdata, $x=100, $y=190, $w='100', $h='80', '', $align='', $palign='', $border=0, $fitonpage=true);

}


$pdf->AddPage();
$pdf->SetFont('helvetica', '', 11);

// echo $nhtml;
$pdf->writeHTML($nhtml, true, false, true, false, '');

// print colored table
// $pdf->ColoredTable($header, $data);

// ---------------------------------------------------------

// close and output PDF document
$pdf->Output('report.pdf', 'D');

//============================================================+
// END OF FILE
//============================================================+


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