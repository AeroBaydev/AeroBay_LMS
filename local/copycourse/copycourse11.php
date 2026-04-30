<?php 

 require_once('../../config.php');
 require_once("$CFG->dirroot/course/classes/external/course_summary_exporter.php");
  require_login();
  if(!isloggedin()){

  return redirect(new moodle_url('/login'));
}

global $DB, $OUTPUT, $PAGE;

$title = 'Master School Content';
$pagetitle = $title;
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('course');
$context = context_system::instance();
$PAGE->set_context($context);
$schoolid=$_GET['catid'];
$category = \core_course_category::get($schoolid);
$categories = $DB->get_records_sql("
    SELECT cc.id, cc.name, cc.description
    FROM {course_categories} cc
     right join  {course_categories} ccc on cc.name=ccc.name
    WHERE
      cc.visible = 1 and cc.parent=171 
");
$sql = "UPDATE {course} SET fullname = TRIM(SUBSTRING_INDEX(fullname, 'copy', 1)) WHERE fullname LIKE '%copy%'";
$DB->execute($sql);

$categories = $DB->get_records_sql("
SELECT cc.id, cc.name, cc.description
FROM {course_categories} cc
 right join  {course_categories} ccc on cc.name=ccc.name
WHERE
  cc.visible = 1 and cc.parent=$schoolid 
");
$url = new moodle_url("/local/copycourse/index.php?parent=0");
$badcrudurl ='<div id="page-navbar">
<nav aria-label="Navigation bar" >
<ol class="breadcrumb" >
<li class="breadcrumb-item">
  <a href="'.$url.'">Home</a>
   
  </li>
  <li class="breadcrumb-item">
  <a href="javascript:void(0);"> Copy to => '.$category->name.'</a>
  </li>
  </ol>
  </nav>
</div>';



$templatecontext = (object)[
    'categories' => array_values($categories),
    'schoolid'=>$schoolid,
    'badcrudurl'=>$badcrudurl
];
$sql = "UPDATE {course} SET fullname = TRIM(SUBSTRING_INDEX(fullname, 'copy', 1)) WHERE fullname LIKE '%copy%'";
$DB->execute($sql);

echo  $OUTPUT->header();
echo  $OUTPUT->render_from_template('local_copycourse/gradelisting', $templatecontext);
echo $OUTPUT->footer();