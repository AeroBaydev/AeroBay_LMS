<?php 

 require_once('../../config.php');
 require_once("$CFG->dirroot/course/classes/external/course_summary_exporter.php");
  require_login();
  if(!isloggedin()){

  return redirect(new moodle_url('/login'));
}
GLOBAL $DB,$USER,$OUTPUT,$CFG;
$context = context_system::instance();
if (!has_capability('local/pocschool:view', $context)) {
    throw new required_capability_exception($context, 'local/pocschool:view', 'nopermissions', '');
}

if(is_siteadmin()){
  if (!isset($_SESSION['userIdPoc'])) {
   
  // $_SESSION['userIdPoc'] = $userIdPoc;
  $userid =$userIdPoc;
  }
  
}
else{
  $userid=$USER->id;
}

if (isset($_SESSION['userIdPoc'])) {
   $userid=$_SESSION['userIdPoc'];
 
}
if(is_siteadmin()){
  $PAGE->navbar->add('POC Control', "$CFG->wwwroot/local/poc/pocmange/?userid=$userid");
      $PAGE->navbar->add('POC School  Allotted', "");
  }
$PAGE->set_url('/local/pocschool/index.php');

function get_category_details($categoryid) {
  global $DB;
  $category = $DB->get_record('course_categories', array('id' => $categoryid), '*', MUST_EXIST);
  return $category;
}

// Function to get courses in a category
function get_courses_in_category($categoryid) {
  global $DB;
  $courses = $DB->get_record('course', array('category' => $categoryid));
  return $courses;
}

// Get category details



//grade 
if($_GET['parent']>0){
    $title = 'Grade Listing';
    $pagetitle = $title;
    $PAGE->set_title($title);
    $PAGE->set_heading($title);   


  $catId=$_GET['parent'];  
$category_listing = $DB->get_records_sql("select * from {course_categories} where parent=$catId and visible=1", [], $limitfrom=0, $limitnum=0);
foreach ($category_listing as $key => $value_list) {
  $context = context_coursecat::instance($value_list->id);
  $course_url=$url."=".$value_list->id;


  $countCatlist=  $DB->count_records("poc_copy_course",['gradeid'=>$value_list->id , 'status'=>1]);
  $countCatlist="<h5 class='card-title'>Total no of course: $countCatlist</h5>";

  $urlCat = new moodle_url("/local/pocschool/index.php?parent=$value_list->id");

  $urlCatEdit = new moodle_url("/local/pocschool/editcategorySchool.php?id=$value_list->id");

  $uploadeurl = new moodle_url("/local/csvpoc/?schoolid=$catId&gradeid=$value_list->id");
  $url_with_amp = str_replace('&amp;', '&', $uploadeurl->out(false));
//barcrum
  
  $category_listing = $DB->get_record_sql("select path from {course_categories} where id=$catId and visible=1", [], $limitfrom=0, $limitnum=0);
  if($category_listing){
    $array = explode('/', $category_listing->path);
    $array = array_filter($array, 'strlen');
    $count = count($array);
   
    if($count==1){
      $data['is_true'] = false;
      $view_text="View course";
      $courseUrl= new moodle_url("/local/pocschool/viewcourse.php?catId=$value_list->id");
      $uploadetext="Uploade Users";

      //  $category = get_category_details($catId);

      // $parent_category = get_category_details($category->parent);
      // $courses = get_courses_in_category($catId);

     
   
     }else{
      $data['is_true'] = true;
     }
    
  }
  //end bradcrum


  $courseArray[]=array('cateid'=>$value_list->id,
    'name'=>$value_list->name,
    'image'=>$test ,
    'parentid' => $value_list->parent,
    'createdate'=>$createdate,
   'path'=>$value_list->path,
   'urlCat'=>$urlCat,
   'urlCatEdit'=>$urlCatEdit,
   'courseUrl'=>$courseUrl,
   'viewcourse'=>$view_text,
   'urlCatDelete'=>$urlCatDelete,
   'countCatlist'=>$countCatlist,
  'uploadetext'=>$uploadetext,
  'uploadeurl'=>$url_with_amp

   );
}




$category_listing = $DB->get_record_sql("select path from {course_categories} where id=$catId and visible=1", [], $limitfrom=0, $limitnum=0);
if($category_listing){
  $url = new moodle_url("/local/pocschool/index.php?parent=0");
  $array = explode('/', $category_listing->path);
  $array = array_filter($array, 'strlen');
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
// echo $badcrudurl;
// die; 


//create url
$url = new moodle_url("/local/pocschool/editcategorySchool.php?parent=$catId");
$data['badcrudurl']=$badcrudurl;
$data['courses']=$courseArray;
$data['view']=$viename;
$data['url']=$url;


}

else{
    $title = 'School Allotted ';
    $pagetitle = $title;
    $PAGE->set_title($title);
    $PAGE->set_heading($title);


$view_category=""; 
$category_listing = $DB->get_records_sql("
    SELECT cc.*
    FROM {course_categories} cc
    JOIN {schoolassign} sa ON cc.id = sa.schoolid
    WHERE cc.parent = 0
      AND cc.visible = 1 and sa.userid=$userid
");

foreach ($category_listing as $key => $value_list) {
  $context = context_coursecat::instance($value_list->id);

  $course_url=$url."=".$value_list->id;
  $createdate = date('l, F d,Y ', $value_list->$timemodified);
      
  $urlCat = new moodle_url("/local/pocschool/index.php?parent=$value_list->id");
  $urlpocschool = new moodle_url("/local/pocschool/copycourse.php?catid=$value_list->id");
  $countCatlist=  $DB->count_records("course_categories",['parent'=>$value_list->id]);
  $countCatlist="<h5 class='card-title'>Total no of grade: $countCatlist</h5>";


  $courseArray[]=array('cateid'=>$value_list->id,
    'name'=>$value_list->name,
    'image'=>$test ,
    'parentid' => $value_list->parent,
    'createdate'=>$createdate,
    'path'=>$value_list->path,
    'urlCat'=>$urlCat,
    "urlpocschool"=>$urlpocschool,
    "countCatlist"=>$countCatlist


   );
}



$data['courses']=$courseArray;
$data['url']=$url;
$data['is_true'] = true;
}


echo  $OUTPUT->header();
echo  $OUTPUT->render_from_template('local_pocschool/pocschool', $data);
echo $OUTPUT->footer();

?>

