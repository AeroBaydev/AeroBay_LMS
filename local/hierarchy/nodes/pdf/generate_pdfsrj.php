<?php
require_once ('../../../../config.php');
global $CFG,$DB,$USER,$PAGE;
require_once($CFG->dirroot.'/lib/filestorage/file_storage.php');
require_login();
require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
$courses=$DB->get_records('course');



       $context =context_user::instance($USER->id);

        // Get files in the user icon area.
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'user', 'icon', 0,'id desc');


        $file = null;
        foreach ($files as $filefound) {
            if (!$filefound->is_directory()) {
                $file = $filefound;
                break;
            }
        }
        // Show image if we found one.
        if ($file) {
            $location = make_request_directory() . '/target';
            $file->copy_content_to($location);
		}else{
			 $location="image.png";
		}
ob_start();
?>
<!DOCTYPE html>
<html>
	<head>
		<title></title>
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








.flex-wrapper {
  display: flex;
  flex-flow: row nowrap;
}

.single-chart {
  width: 33%;
  justify-content: space-around ;
}

.circular-chart {
  display: block;
  margin: 10px auto;
  max-width: 80%;
  max-height: 250px;
}

.circle-bg {
  fill: none;
  stroke: #eee;
  stroke-width: 3.8;
}

.circle {
  fill: none;
  stroke-width: 2.8;
  stroke-linecap: round;
  animation: progress 1s ease-out forwards;
}

@keyframes progress {
  0% {
    stroke-dasharray: 0 100;
  }
}

.circular-chart.orange .circle {
  stroke: #ff9f00;
}

.circular-chart.green .circle {
  stroke: #4CC790;
}

.circular-chart.blue .circle {
  stroke: #3c9ee5;
}

.percentage {
  fill: #666;
  font-family: sans-serif;
  font-size: 0.5em;
  text-anchor: middle;
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
								<img src="<?php echo $location;?>" width="100px" alt="images" />
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
				<td  width="100%">
					<table  class="pdf-table" >
						<tr>
							<td ><b>Organization Name</b></td>
							<td>Sarovar Hotels & Resorts </td>
						</tr>
						
						<tr>
							<td ><b>Designation </b></td>
							<td>VPHR </td>
						</tr>
						
						<tr>
							<td ><b>City </b></td>
							<td>Mumbai  </td>
						</tr>
						
						<tr>
							<td ><b>Parent Company</b></td>
							<td>Sarovar Hotels & resorts  </td>
						</tr>
						
						<tr>
							<td ><b>Mobile No. </b></td>
							<td>&nbsp; </td>
						</tr>
					</table>
				</td>
				<td  width="100%">
					<table class="pdf-table " >
						<tr>
							<td ><b>Department Name</b></td>
							<td>Training </td>
						</tr>
						
						<tr>
							<td ><b>Job Role </b></td>
							<td>Leadership </td>
						</tr>
						
						<tr>
							<td ><b>Country </b></td>
							<td>India </td>
						</tr>
						
						<tr>
							<td ><b>Mailing Address</b></td>
							<td>&nbsp; </td>
						</tr>
						
						<tr>
							<td >&nbsp;</td>
							<td>&nbsp; </td>
						</tr>
						
					</table>
				</td>
			</tr>
			</table>
				
			<div style="clear:both"></div>
			
			<div class="flex-wrapper" style="margin-top: 100px;
	position:relative;">
			<div class="single-chart graph-one" style="position: absolute;
    top: 100px;
    left: 6px;">
				<svg viewBox="0 0 36 36" class="circular-chart orange">
				  <path class="circle-bg"
					d="M18 2.0845
					  a 15.9155 15.9155 0 0 1 0 31.831
					  a 15.9155 15.9155 0 0 1 0 -31.831"
				  />
				  <path class="circle"
					stroke-dasharray="30, 100"
					d="M18 2.0845
					  a 15.9155 15.9155 0 0 1 0 31.831
					  a 15.9155 15.9155 0 0 1 0 -31.831"
				  />
				</svg>
			</div>
  
			<div class="single-chart graph-two" style="position: absolute;
    top: 65px;
    left: 6px;">
				<svg viewBox="0 0 36 36" class="circular-chart green" style="max-height: 320px;">
				  <path class="circle-bg"
					d="M18 2.0845
					  a 15.9155 15.9155 0 0 1 0 31.831
					  a 15.9155 15.9155 0 0 1 0 -31.831"
				  />
				  <path class="circle"
					stroke-dasharray="60, 100"
					d="M18 2.0845
					  a 15.9155 15.9155 0 0 1 0 31.831
					  a 15.9155 15.9155 0 0 1 0 -31.831"
				  />
				 
				</svg>
			</div>

			<div class="single-chart graph-three" style=" position: absolute;
    top: 21px;
    left: 5px;">
				<svg viewBox="0 0 36 36" class="circular-chart blue" style="max-height: 407px;">
				  <path class="circle-bg"
					d="M18 2.0845
					  a 15.9155 15.9155 0 0 1 0 31.831
					  a 15.9155 15.9155 0 0 1 0 -31.831"
				  />
				  <path class="circle"
					stroke-dasharray="90, 100"
					d="M18 2.0845
					  a 15.9155 15.9155 0 0 1 0 31.831
					  a 15.9155 15.9155 0 0 1 0 -31.831"
				  />
				  
				</svg>
			</div>
			
			<div style="clear:both"></div>	
		</div>
			
			
			

			<div class="containers">
				<div id="activeBorder" class="active-border">
					<div id="circle" class="circle">
						<span class="prec 270" id="prec">20%</span>
					</div>
				</div>
			</div>
			
		
		
     <div class="line-chart">
       <div class='grafico'>
		   <ul class='eje-y'>
			 <li data-ejeY='30'></li>
			 <li data-ejeY='20'></li>
			 <li data-ejeY='10'></li>
			 <li data-ejeY='0'></li>
		   </ul>
		   <ul class='eje-x'>
			 <li>Apr</li>
			 <li>May</li>
			 <li>Jun</li>
		   </ul>
			<span data-valor='25'>
				<span data-valor='8'>
					<span data-valor='13'>
						<span data-valor='5'>   
							<span data-valor='23'>   
								<span data-valor='12'>
									<span data-valor='15'>
									</span>
								</span>
							</span>
						</span>
					</span>
				</span>
			</span>
       </div>
       
     </div>


				
			<br>
			<br>
			<br>
			<br>
			<br>
			<br>
			<table cellpadding="0" cellspacing="0" width="100%" class="pdf-table " >
			<tr>
				<th>Course Code</th>
				<th>Course Name</th>
				<th>User Status</th>
				<th>Enrolled</th>
				<th>First Access Date</th>
				<th>Course Completion</th>
				<th>Credits</th>
				<th>Total Time</th>
				<th>Score</th>
			</tr>
			<?php 
			 
			
			
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
					?>
				<tr>
					<td><?php echo $name=$course->shortname;?></td>
					<td><?php echo $fname=$course->fullname;?></td>
					<td><?php echo $completiontext="dssdf";?></td>
					<td>Enrolled</td>
					<td>First Access Date</td>
					<td>Course Completion</td>
					<td>Credits</td>
					<td>Total Time</td>
					<td>Score</td>
				</tr>
				<?php 
				//}
			}
			?>
		</table>
			<p id="demo"> sdgsdgsdg</p>
		</div>
		
	</body>
</html>
<?php
// $html = ob_get_clean();
// $dompdf = new DOMPDF();
// $dompdf->load_html($html);
// $dompdf->render();
// $dompdf->stream("sample.pdf");

?>