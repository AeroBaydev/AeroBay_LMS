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
			
		</style>
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
							<table cellpadding="0" cellspacing="0" class="pdf-table">
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
				<td>
					<table  class="pdf-table" width="100%">
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
				<td>
					<table class="pdf-table" width="100%">
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
$html = ob_get_clean();
$dompdf = new DOMPDF();
$dompdf->load_html($html);
$dompdf->render();
$dompdf->stream("sample.pdf");

?>