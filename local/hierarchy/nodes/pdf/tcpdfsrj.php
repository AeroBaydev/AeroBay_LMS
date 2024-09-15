<?php
require('../../../../config.php');
require('../../../../lib/tcpdf/tcpdf.php');
require_once($CFG->dirroot.'/lib/filestorage/file_storage.php');
global $DB,$CFG,$USER,$OUTPUT;
$courses=$DB->get_records('course');



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
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 011', PDF_HEADER_STRING);

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
$pdf->SetFont('helvetica', '', 11);

// add a page
$pdf->AddPage();
// echo $htmlf;
	
		$picx = 7;  // Picture horizontal position.
		$picy = 40;   // Picture vertical position.
		$picw = 40; // Picture width.
		$pich = 40; // Picture height.


        $context =context_user::instance($USER->id);

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
			 $pdf->Image('img.jpg', $picx, $picy, $picw,$pich);
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
								<h3>Zubin <br> Ghiara</h3>
								<p>zubin@sarovarhotels.com </p>
							</div>
							
						</td>
						<td align="center">
							<div class="">
								<div class="">
									<img src="image.png" alt="images" />
								</div>
								<h3 style="color:#0465ac">28/06/2018 <br>
								    12:24:37 
								</h3>
								<p>Subscription date </p>
							</div>
						</td>
						<td align="center">
							<div class="">
								<div class="">
									<img src="image.png" alt="images" />
								</div>
								<h3 style="color:#0465ac">28/06/2018 <br>
								    12:24:37 
								</h3>
								<p>Subscription date </p>
							</div>
						</td>
					</tr>
					
					
					<tr>
						<td>
							<table cellpadding="0" cellspacing="0" width="100%" class="pdf-table">
								<tr>
									<td><b>Level</b></td>
									<td>Power User </td>
								</tr>
								
								<tr>
									<td><b>E-mail </b></td>
									<td>&nbsp; </td>
								</tr>
								
								<tr>
									<td><b>Groups </b></td>
									<td>Sarovar - Managers & Administrators </td>
								</tr>
								
								<tr>
									<td >&nbsp;</td>
									<td>&nbsp; </td>
								</tr>
							</table>
						</td>
						<td align="center" style="border-top:none;">
							<div class="">
								<div class="">
									<img src="image.png" alt="images" />
								</div>
								<h3 style="color:#0465ac">7h 57m
								</h3>
								<p>Total time </p>
							</div>
						</td>
						
						<td align="center" style="border-top:none;">
							<div class="">
								<div class="">
									<img src="image.png" alt="images" />
								</div>
								<h3 style="color:#0465ac">292 </h3>
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
							<td colspan="2"><b>Organization Name</b></td>
							<td colspan="2">Sarovar Hotels & Resorts </td>
						</tr>
						
						<tr>
							<td colspan="2"><b>Designation </b></td>
							<td colspan="2">VPHR </td>
						</tr>
						
						<tr>
							<td colspan="2"><b>City </b></td>
							<td colspan="2">Mumbai  </td>
						</tr>
						
						<tr>
							<td colspan="2"><b>Parent Company</b></td>
							<td colspan="2">Sarovar Hotels & resorts  </td>
						</tr>
						
						<tr>
							<td colspan="2"><b>Mobile No. </b></td>
							<td colspan="2">&nbsp; </td>
						</tr>
					</table>
				</td>
				<td  width="4%">
				</td>
				<td  width="48%">
					<table class="pdf-table " >
						<tr>
							<td colspan="2"><b>Department Name</b></td>
							<td colspan="2">Training </td>
						</tr>
						
						<tr>
							<td colspan="2"><b>Job Role </b></td>
							<td colspan="2">Leadership </td>
						</tr>
						
						<tr>
							<td colspan="2"><b>Country </b></td>
							<td colspan="2">India </td>
						</tr>
						
						<tr>
							<td colspan="2"><b>Mailing Address</b></td>
							<td colspan="2">&nbsp; </td>
						</tr>
						
						<tr>
							<td colspan="2">&nbsp;</td>
							<td colspan="2">&nbsp; </td>
						</tr>
						
					</table>
				</td>
			</tr>
			</table>
				
			<div style="clear:both"></div>
			';
			
			
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
					<td><b>Credits</b></td>
					<td><b>Total Time</b></td>
					<td><b>Score</b></td>
				</tr>';

			
			foreach($courses as $course)
			{
				/* $coursecontext=context_course::instance($course->id);
				if(!is_enrolled($coursecontext,$USER->id))
				{
					countinue;
				}else{
					$flag=0;
					$completion=new completion_info($course);
					if($completion->is_course_complete($USER->id))
					{
						$flag=1;
						$completiontext="Completed";
					}else{
						$completiontext="In progress";
					}
					if($flag)
					{
						
					} */
					
				$nhtml.='<tr>
							<td>'.$name=$course->shortname.'</td>
							<td>'.$fname=$course->fullname.'</td>
							<td>'.$completiontext="dssdf".'</td>
							<td>Enrolled</td>
							<td>22/06/1996</td>
							<td>04/06/1996</td>
							<td>50h</td>
							<td>22hours</td>
							<td>00</td>
						</tr>';
				//}
			}
			
		$nhtml.='</table>
		
		
	</body>
</html>';
				






$svgdata='<svg xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" class="highcharts-root" style="font-family:&quot;Lucida Grande&quot;, &quot;Lucida Sans Unicode&quot;, Arial, Helvetica, sans-serif;font-size:12px;" xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400"><desc>Created with Highcharts 6.2.0</desc><defs><clipPath id="highcharts-53ou9bg-19"><rect x="0" y="0" width="396" height="292" fill="none"></rect></clipPath></defs><rect fill="#ffffff" class="highcharts-background" x="0" y="0" width="600" height="400" rx="0" ry="0"></rect><rect fill="none" class="highcharts-plot-background" x="77" y="71" width="396" height="292"></rect><g class="highcharts-grid highcharts-xaxis-grid " data-z-index="1"><path fill="none" data-z-index="1" class="highcharts-grid-line" d="M 133.5 71 L 133.5 363" opacity="1"></path><path fill="none" data-z-index="1" class="highcharts-grid-line" d="M 221.5 71 L 221.5 363" opacity="1"></path><path fill="none" data-z-index="1" class="highcharts-grid-line" d="M 309.5 71 L 309.5 363" opacity="1"></path><path fill="none" data-z-index="1" class="highcharts-grid-line" d="M 398.5 71 L 398.5 363" opacity="1"></path></g><g class="highcharts-grid highcharts-yaxis-grid " data-z-index="1"><path fill="none" stroke="#e6e6e6" stroke-width="1" data-z-index="1" class="highcharts-grid-line" d="M 77 363.5 L 473 363.5" opacity="1"></path><path fill="none" stroke="#e6e6e6" stroke-width="1" data-z-index="1" class="highcharts-grid-line" d="M 77 314.5 L 473 314.5" opacity="1"></path><path fill="none" stroke="#e6e6e6" stroke-width="1" data-z-index="1" class="highcharts-grid-line" d="M 77 266.5 L 473 266.5" opacity="1"></path><path fill="none" stroke="#e6e6e6" stroke-width="1" data-z-index="1" class="highcharts-grid-line" d="M 77 217.5 L 473 217.5" opacity="1"></path><path fill="none" stroke="#e6e6e6" stroke-width="1" data-z-index="1" class="highcharts-grid-line" d="M 77 168.5 L 473 168.5" opacity="1"></path><path fill="none" stroke="#e6e6e6" stroke-width="1" data-z-index="1" class="highcharts-grid-line" d="M 77 120.5 L 473 120.5" opacity="1"></path><path fill="none" stroke="#e6e6e6" stroke-width="1" data-z-index="1" class="highcharts-grid-line" d="M 77 70.5 L 473 70.5" opacity="1"></path></g><rect fill="none" class="highcharts-plot-border" data-z-index="1" x="77" y="71" width="396" height="292"></rect><g class="highcharts-axis highcharts-xaxis " data-z-index="2"><path fill="none" class="highcharts-tick" stroke="#ccd6eb" stroke-width="1" d="M 133.5 363 L 133.5 373" opacity="1"></path><path fill="none" class="highcharts-tick" stroke="#ccd6eb" stroke-width="1" d="M 221.5 363 L 221.5 373" opacity="1"></path><path fill="none" class="highcharts-tick" stroke="#ccd6eb" stroke-width="1" d="M 309.5 363 L 309.5 373" opacity="1"></path><path fill="none" class="highcharts-tick" stroke="#ccd6eb" stroke-width="1" d="M 398.5 363 L 398.5 373" opacity="1"></path><path fill="none" class="highcharts-axis-line" stroke="#ccd6eb" stroke-width="1" data-z-index="7" d="M 77 363.5 L 473 363.5"></path></g><g class="highcharts-axis highcharts-yaxis " data-z-index="2"><text x="25.65625" data-z-index="7" text-anchor="middle" transform="translate(0,0) rotate(270 25.65625 217)" class="highcharts-axis-title" style="color:#666666;fill:#666666;" y="217"><tspan>Number of Employees</tspan></text><path fill="none" class="highcharts-axis-line" data-z-index="7" d="M 77 71 L 77 363"></path></g><g class="highcharts-series-group" data-z-index="3"><g data-z-index="0.1" class="highcharts-series highcharts-series-0 highcharts-line-series highcharts-color-0 " transform="translate(77,71) scale(1 1)" clip-path="url(#highcharts-53ou9bg-19)"><path fill="none" d="M 3.8823529411765 262.8 L 39.176470588235 243.33333333333334 L 74.470588235294 223.86666666666667 L 109.76470588235 184.93333333333334 L 145.05882352941 165.4666666666667 L 180.35294117647 146 L 215.64705882353 126.53333333333336 L 250.94117647059 107.06666666666669 L 286.23529411765 87.60000000000002 L 321.52941176471 68.13333333333335 L 356.82352941176 48.666666666666686 L 392.11764705882 29.200000000000045" class="highcharts-graph" data-z-index="1" stroke="#7cb5ec" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></path></g><g data-z-index="0.1" class="highcharts-markers highcharts-series-0 highcharts-line-series highcharts-color-0 " transform="translate(77,71) scale(1 1)" clip-path="none"><path fill="#7cb5ec" d="M 7 263 A 4 4 0 1 1 6.999998000000167 262.99600000066664 Z" class="highcharts-point highcharts-color-0"></path><path fill="#7cb5ec" d="M 43 243 A 4 4 0 1 1 42.99999800000017 242.99600000066667 Z" class="highcharts-point highcharts-color-0"></path><path fill="#7cb5ec" d="M 78 224 A 4 4 0 1 1 77.99999800000016 223.99600000066667 Z" class="highcharts-point highcharts-color-0"></path><path fill="#7cb5ec" d="M 113 185 A 4 4 0 1 1 112.99999800000016 184.99600000066667 Z" class="highcharts-point highcharts-color-0"></path><path fill="#7cb5ec" d="M 149 165 A 4 4 0 1 1 148.99999800000018 164.99600000066667 Z" class="highcharts-point highcharts-color-0"></path><path fill="#7cb5ec" d="M 184 146 A 4 4 0 1 1 183.99999800000018 145.99600000066667 Z" class="highcharts-point highcharts-color-0"></path><path fill="#7cb5ec" d="M 219 127 A 4 4 0 1 1 218.99999800000018 126.99600000066667 Z" class="highcharts-point highcharts-color-0"></path><path fill="#7cb5ec" d="M 254 107 A 4 4 0 1 1 253.99999800000018 106.99600000066667 Z" class="highcharts-point highcharts-color-0"></path><path fill="#7cb5ec" d="M 290 88 A 4 4 0 1 1 289.9999980000002 87.99600000066667 Z" class="highcharts-point highcharts-color-0"></path><path fill="#7cb5ec" d="M 325 68 A 4 4 0 1 1 324.9999980000002 67.99600000066667 Z" class="highcharts-point highcharts-color-0"></path><path fill="#7cb5ec" d="M 360 49 A 4 4 0 1 1 359.9999980000002 48.99600000066666 Z" class="highcharts-point highcharts-color-0"></path><path fill="#7cb5ec" d="M 396 29 A 4 4 0 1 1 395.9999980000002 28.996000000666665 Z" class="highcharts-point highcharts-color-0"></path></g></g><text x="300" text-anchor="middle" class="highcharts-title" data-z-index="4" style="color:#333333;font-size:18px;fill:#333333;" y="24"><tspan>wfef</tspan></text><text x="300" text-anchor="middle" class="highcharts-subtitle" data-z-index="4" style="color:#666666;fill:#666666;" y="52"><tspan>Source: thesolarfoundation.com</tspan></text><g class="highcharts-legend" data-z-index="7" transform="translate(485,183)"><rect fill="none" class="highcharts-legend-box" rx="0" ry="0" x="0" y="0" width="104" height="29" visibility="visible"></rect><g data-z-index="1"><g><g class="highcharts-legend-item highcharts-line-series highcharts-color-0 highcharts-series-0" data-z-index="1" transform="translate(8,3)"><path fill="none" d="M 0 11 L 16 11" class="highcharts-graph" stroke="#7cb5ec" stroke-width="2"></path><path fill="#7cb5ec" d="M 12 11 A 4 4 0 1 1 11.999998000000167 10.996000000666664 Z" class="highcharts-point"></path><text x="21" style="color:#333333;font-size:12px;font-weight:bold;cursor:pointer;fill:#333333;" text-anchor="start" data-z-index="2" y="15"><tspan>Installation</tspan></text></g></g></g></g><g class="highcharts-axis-labels highcharts-xaxis-labels " data-z-index="7"><text x="133.82352941176" style="color:#666666;cursor:default;font-size:11px;fill:#666666;" text-anchor="middle" transform="translate(0,0)" y="382" opacity="1">2.5</text><text x="222.05882352941" style="color:#666666;cursor:default;font-size:11px;fill:#666666;" text-anchor="middle" transform="translate(0,0)" y="382" opacity="1">5</text><text x="310.29411764706" style="color:#666666;cursor:default;font-size:11px;fill:#666666;" text-anchor="middle" transform="translate(0,0)" y="382" opacity="1">7.5</text><text x="398.52941176471" style="color:#666666;cursor:default;font-size:11px;fill:#666666;" text-anchor="middle" transform="translate(0,0)" y="382" opacity="1">10</text></g><g class="highcharts-axis-labels highcharts-yaxis-labels " data-z-index="7"><text x="62" style="color:#666666;cursor:default;font-size:11px;fill:#666666;" text-anchor="end" transform="translate(0,0)" y="367" opacity="1">2.5</text><text x="62" style="color:#666666;cursor:default;font-size:11px;fill:#666666;" text-anchor="end" transform="translate(0,0)" y="318" opacity="1">5</text><text x="62" style="color:#666666;cursor:default;font-size:11px;fill:#666666;" text-anchor="end" transform="translate(0,0)" y="270" opacity="1">7.5</text><text x="62" style="color:#666666;cursor:default;font-size:11px;fill:#666666;" text-anchor="end" transform="translate(0,0)" y="221" opacity="1">10</text><text x="62" style="color:#666666;cursor:default;font-size:11px;fill:#666666;" text-anchor="end" transform="translate(0,0)" y="172" opacity="1">12.5</text><text x="62" style="color:#666666;cursor:default;font-size:11px;fill:#666666;" text-anchor="end" transform="translate(0,0)" y="124" opacity="1">15</text><text x="62" style="color:#666666;cursor:default;font-size:11px;fill:#666666;" text-anchor="end" transform="translate(0,0)" y="75" opacity="1">17.5</text></g><text x="590" class="highcharts-credits" text-anchor="end" data-z-index="8" style="cursor:pointer;color:#999999;font-size:9px;fill:#999999;" y="395"></text></svg>';





// echo $html;













$pdf->writeHTML($html, true, false, true, false, '');

if($svgdata)
{
	$pdf->ImageSVG('@'.$svgdata, $x=100, $y=190, $w='100', $h='80', '', $align='', $palign='', $border=0, $fitonpage=false);
}

$pdf->AddPage();
$pdf->SetFont('helvetica', '', 11);


$pdf->writeHTML($nhtml, true, false, true, false, '');

// print colored table
// $pdf->ColoredTable($header, $data);

// ---------------------------------------------------------

// close and output PDF document
$pdf->Output('report.pdf', 'D');

//============================================================+
// END OF FILE
//============================================================+
?>