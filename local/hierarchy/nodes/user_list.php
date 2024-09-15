<?php

require_once(__DIR__ . '/../../../config.php'); //this way it works in case of nested file inclusions

require_once(__DIR__ . '/../lib.php');
global $CFG, $DB,$PAGE;

require_capability('local/hierarchy:manage', context_system::instance(), null, true, "Capability 'Manage hierarchies' required"); //check capability 

$deptid = required_param('deptid', PARAM_INT);
$prefix='locate';

require_once($CFG->libdir . '/adminlib.php'); 
admin_externalpage_setup($prefix); 

$page=optional_param('page', 0, PARAM_INT);
$perpage=optional_param('perpage', 10, PARAM_INT);
$paging=$page*$perpage;

$PAGE->set_context(context_system::instance());
$PAGE->set_title('User list');
$PAGE->set_pagelayout('frametop');

$url = new moodle_url('/local/hierarchy/nodes/user_list.php', array('prefix'=>$prefix, 'page'=> $page, 'perpage'=>$perpage));
$PAGE->set_url($url);

$deptrecord=$DB->get_record('loc', array('id'=>$deptid, 'deleted'=>0));
$branchrecord=$DB->get_record('loc_framework', array('id'=>$deptrecord->frameworkid, 'deleted'=>0));

$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('site_administration', 'local_hierarchy'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('hierarchies', 'local_hierarchy'), new moodle_url('/admin/category.php?category=hierarchy'));
$PAGE->navbar->add(get_string('locate_frameworks', 'local_hierarchy'), new moodle_url('/local/hierarchy/nodes/node_framework.php?prefix=locate'));
$PAGE->navbar->add($branchrecord->fullname, new moodle_url('/local/hierarchy/nodes/node.php?prefix=locate&frameworkid='.$deptrecord->frameworkid));
$PAGE->navbar->add($deptrecord->fullname . ' user list');

echo $OUTPUT->header();

echo html_writer::start_tag('a', array('href'=>'../nodes/node.php?prefix=locate&frameworkid=' . $deptrecord->frameworkid)) .
	html_writer::start_tag('font', array('size'=>'2')).
		"&lt&lt " . "Back to " . $branchrecord->fullname . 
	html_writer::end_tag('font') . 
html_writer::end_tag('a');

echo html_writer::start_tag('h2') . 'User list' . html_writer::end_tag('h2');
echo html_writer::start_tag('br');


$result=$DB->get_records('user_dept_enrolments', array('deptid'=>$deptid), $sort='', $fields='*', $limitfrom=$paging, $limitnum=$perpage);

$htmltable = new html_table();
$htmltable->attributes['class'] = 'table table-striped table-hover table-bordered';
$htmltable->head = array(get_string('user', 'local_hierarchy'), get_string('actions', 'local_hierarchy')); 

if(count($result) > 0) {

	foreach ($result as $record) {

	
	$time=strtotime(Date('01 F Y',strtotime('-11 months',time())));
	$userid=$record->userid;
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
	
	
		$htmlCode = '';
		$htmlCode .= '<form class="submitForm'.$record->userid.'" method = "post" action = "pdf/tcpdftest.php">
		
		<button type="button" id="btn" onclick = "btnClick('.$data.','.$xval.','.$userid.')"><img src="../images/download.svg" height=20 width=20/></button>
		<input type="hidden" id="svgHidden'.$record->userid.'" name="hiddenelement" value="">
		<input type="hidden" id="userid" name="userid" value="'.$record->userid.'">
		
		</form>';
		
		$user=$DB->get_record('user', array('id'=>$record->userid));

		$htmlrow = new html_table_row(); 

		$htmlrow->id= $record->id;
	
		$htmlrow->cells[] = new html_table_cell($user->firstname . " " . $user->lastname . " (" . $user->email. ")"); 
		
		$htmlrow->cells[] = new html_table_cell( 
		$htmlCode
		
		); //end cell

		$htmltable->data[] = $htmlrow; //add the row to table
       
	}
			
	echo html_writer::table($htmltable);

	
	$table=get_table_prefix($prefix) . '_framework';
	$total_count=$DB->count_records($table); 
	
	$url = new moodle_url('/local/hierarchy/nodes/node_framework.php', array('prefix'=>$prefix, 'page'=>$page, 'perpage'=>$perpage));
	
	echo $OUTPUT->paging_bar($total_count, $page, $perpage, $url);    

} else {
	echo html_writer::start_tag('p') . 'No users found' . html_writer::end_tag('p');
}
//$data="[4,5,6,8,9,10,11,12,13,14,15,16]";

?>





<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script src="pdf/js/highcharts.js"></script>
<script src="pdf/js/exporting.js"></script>

<script>
function btnClick(vdata,xval,ele) {
	// alert(vdata);
	// alert(xval);
	// alert(ele);
var x = $(document.createElement('div')).highcharts({

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
			categories: xval
		  },
    yAxis: {
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
        data: vdata
    },],

});
	get_svg(x.highcharts(), ele);
	$('.submitForm'+ele).submit();
}


	
	
function get_svg(chart, ele) {
	// alert(ele);
  var svg = chart.getSVG();
  
  	$('#svgHidden'+ele).val(svg);
	
    
  
}
</script>


