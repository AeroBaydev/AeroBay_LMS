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

$sql = "UPDATE {course} SET fullname = TRIM(SUBSTRING_INDEX(fullname, 'copy', 1)) WHERE fullname LIKE '%copy%'";
$DB->execute($sql);

$categories = $DB->get_records_sql("
SELECT cc.id, cc.name, cc.description
FROM {course_categories} cc
 right join  {course_categories} ccc on cc.name=ccc.name
WHERE
  cc.visible = 1 and cc.parent=$schoolid 
");
$parentid = $DB->get_record('course_categories', ['idnumber' => 1], 'id');
foreach ($categories as $category) {
  $courses = $DB->get_records_sql("
      SELECT c.id, c.fullname
      FROM {course} c join {course_categories} cc on cc.id= c.category
      WHERE cc.name = '$category->name' AND cc.visible = 1 ANd cc.parent=$parentid->id " );
      $category->courses = array_values($courses);
}


$url = new moodle_url("/local/copycourse/index.php");
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