<?php
 require('../../../../config.php');
global $CFG,$OUTPUT,$DB;
/*$PAGE->set_url('/lineargraph.php');
$PAGE->set_pagelayout('noblocks');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('help'));

echo $OUTPUT->header(); */


$time=strtotime(Date('01 F Y',strtotime('-11 months',time())));
$userid=2;
$xval="[";
$xval.="'".Date("F",$time)."',";

$data="[";
for($i=1;$i<12;$i++)
{
	
	$time=strtotime('+1 months',$time);
	
	$nexttime=strtotime('+1 months',$time);
	
	$datacount=$DB->get_records_sql("select id from {course_modules_completion} where userid=$userid and timemodified>$time and timemodified<$nexttime");
	
	$data.=count($datacount).",";
	
	$xval.="'".Date("F",$time)."',";
}

$xval=rtrim($xval,",");
$xval.="]";


$data=rtrim($data,",");
$data.="]";
echo $xval;
echo $data;
// $data="[4,5,6,8,9,10,11,12,13,14,15,16]";


?>




<button id='btn' onclick="clickdata(<?php echo $data;?>,<?php echo $xval;?>,1)">Get Chart SVG</button>
<div id='containers'></div>
<textarea id="svg-data" rows=20></textarea>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script src="js/highcharts.js"></script>
<script src="js/exporting.js"></script>

<script>


function clickdata(vdata,xval,ele) {
	alert(vdata);
	alert(ele);
	var x = $(document.createElement('div')).highcharts({

		title: {
			text: 'Activity'
		},

		subtitle: {
			text: 'DATA'
		},
		xAxis: {
			categories: xval
		  },
		yAxis: {
			title: {
				text: 'Number of Activity'
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
			name: 'Number ofActivities',
			data: vdata
		},],

	});
    get_svg(x.highcharts(),ele);
	$("#containers").append(x);
  }
</script>
<script>
function get_svg(chart,ele) {
  var svg = chart.getSVG();
  setTimeout(function() {
  	$('#svg-data'+ele).text(svg);
    console.log(svg);
  }, 0);
}
</script>

<?php 
// echo $OUTPUT->footer();
?>