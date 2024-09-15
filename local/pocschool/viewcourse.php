<?php 

 require_once('../../config.php');
 require_once("$CFG->dirroot/course/classes/external/course_summary_exporter.php");
  require_login();
  if(!isloggedin()){

  return redirect(new moodle_url('/login'));
}
GLOBAL $DB,$USER;
 $catId=$_GET['catId'];
 
 if (isset($_SESSION['userIdPoc'])) {
  $id=$_SESSION['userIdPoc'];

}
else{
  $id = $USER->id;
}



$allCourses = $DB->get_records_sql("SELECT * FROM {course} where visible=1  and category=$catId");
$courseArray=array();
foreach ($allCourses as $course) {
    $course_name = $course->fullname;
    $delivery = $course->delivery;
    $createdate = date('l, F d', $course->startdate);
    
    
    $field_data = $DB->get_record_sql("SELECT c.value as fee FROM {customfield_data} c JOIN {customfield_field} f ON f.id = c.fieldid WHERE c.instanceid = $course->id AND f.shortname = 'fee' ");
    $course_price=$field_data->fee;
  
   //course image
    $course_img =   \core_course\external\course_summary_exporter::get_course_image($course);
    if ($course_img) {
        $image=$course_img;
    } else {
        $image=$CFG->wwwroot."/local/assets/img/course/1/1.jpg";
    }
    //summary
    $summary=trim($course->summary);

    //sections
    $sections=$DB->get_records_sql("SELECT * from {course_sections} where course=$course->id and visible=1");
    $section=COUNT($sections);

  

    //enroll user 
    $context = context_course::instance($course->id);
    $users = $DB->get_records_sql("SELECT ra.id,ra.userid FROM {role_assignments} as ra join {role} as r on ra.roleid=r.id where ra.contextid=$context->id and r.shortname='learner'");
    $enroll_user = COUNT($users);


    $poc_session_date_id = $DB->get_record('poc_session_date', ['pocid' => $id,'status'=>1], '*', MUST_EXIST);
  
   
    $checkCourseCopy = $DB->get_record('poc_copy_course',  ['pocid' => $id,'status'=>1,'gradeid'=>$catId,'sessionid'=>$poc_session_date_id->id]);
    
    if($checkCourseCopy){
    
    if($checkCourseCopy->courseid==$course->id){

      $enrolButton=true;

    }
    else{
      $enrolButton=false;
    }
 }
 else{
  $enrolButton=false;
 }
    
    $courseArray[]=array('courseid'=>$course->id,
    'coursename'=>$course_name,
    'image'=>$course_img ,
    'summary'=>$summary,
    'lesson'=>$section,
    'enrolButton'=>$enrolButton,
    'count'=>$enroll_user, 
    'course_category' => $course->category,
    'course_price'=>$course_price, 'delivery'=>$delivery,'createdate'=>$createdate);

}

$url = new moodle_url("/local/chapter/edit.php?category=$catId");
//bradcrum
$category_listing = $DB->get_record_sql("select path from {course_categories} where id=$catId and visible=1", [], $limitfrom=0, $limitnum=0);
if($category_listing){
  $url = new moodle_url("/local/pocschool/index.php?parent=0");
  $array = explode('/', $category_listing->path);
  $array = array_filter($array, 'strlen');
  // Remove the last element
array_pop($array);
 
  $badcrudurl ='<div id="page-navbar">
<nav aria-label="Navigation bar" >
<ol class="breadcrumb" >
<li class="breadcrumb-item">
  <a href="'.$url.'">Home</a>
  </li>';
foreach ($array as $key => $value) {
  $category_listing = $DB->get_record_sql("select name from {course_categories} where id=$value ");
  $url = new moodle_url("/local/pocschool/index.php?parent=$value");
  $badcrudurl .='<li class="breadcrumb-item">
  <a href="'.$url.'">'.$category_listing->name.'</a>
  </li>';}}
$badcrudurl .='</ol>
</nav>
</div>';



echo  $OUTPUT->header();
$faviconUrl = $OUTPUT->favicon();
 
$data['courses']=$courseArray;
$data['url']=$url;
$data['badcrudurl']=$badcrudurl;
echo  $OUTPUT->render_from_template('local_pocschool/viewcourse', $data);
echo $OUTPUT->footer();

?>


