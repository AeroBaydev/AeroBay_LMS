<?php 

 require_once('../../config.php');
 require_once("$CFG->dirroot/course/classes/external/course_summary_exporter.php");
  require_login();
  if(!isloggedin()){

  return redirect(new moodle_url('/login'));
}
GLOBAL $DB,$USER,$OUTPUT;
echo  $OUTPUT->header();
$PAGE->set_url('/local/copycourse/index.php');

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
  $createdate = date('l, F d,Y H:i a', $value_list->$timemodified);

 

  $urlCat = new moodle_url("/local/copycourse/index.php?parent=$value_list->id");

  $urlCatEdit = new moodle_url("/local/copycourse/editcategorySchool.php?id=$value_list->id");


//barcrum
  $view_text="Create Chapter";
  $category_listing = $DB->get_record_sql("select path from {course_categories} where id=$catId and visible=1", [], $limitfrom=0, $limitnum=0);
  if($category_listing){
    $array = explode('/', $category_listing->path);
    $array = array_filter($array, 'strlen');
    $count = count($array);
   
    if($count==1){
      $data['is_true'] = false;
      $view_text="View course";
      $courseUrl= new moodle_url("/local/copycourse/viewcourse.php?catId=$value_list->id");
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
   'urlCatDelete'=>$urlCatDelete


   );
}




$category_listing = $DB->get_record_sql("select path from {course_categories} where id=$catId and visible=1", [], $limitfrom=0, $limitnum=0);
if($category_listing){
  $url = new moodle_url("/local/copycourse/index.php?parent=0");
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
  $url = new moodle_url("/local/copycourse/index.php?parent=$value");
  $badcrudurl .='<li class="breadcrumb-item">
  <a href="'.$url.'">'.$category_listing->name.'</a>
  </li>';}}
$badcrudurl .='</ol>
</nav>
</div>';
// echo $badcrudurl;
// die; 


//create url
$url = new moodle_url("/local/copycourse/editcategorySchool.php?parent=$catId");
$data['badcrudurl']=$badcrudurl;
$data['courses']=$courseArray;
$data['view']=$viename;
$data['url']=$url;


}

else{
    $title = 'School Listing';
    $pagetitle = $title;
    $PAGE->set_title($title);
    $PAGE->set_heading($title);


$view_category=""; 
$category_listing = $DB->get_records_sql("
    SELECT cc.*
    FROM {course_categories} cc
    JOIN {school} s ON cc.id = s.course_cat_id
    WHERE cc.parent = 0
      AND cc.visible = 1
");

foreach ($category_listing as $key => $value_list) {
  $context = context_coursecat::instance($value_list->id);

  $course_url=$url."=".$value_list->id;
  $createdate = date('l, F d,Y ', $value_list->$timemodified);

  $urlCat = new moodle_url("/local/copycourse/index.php?parent=$value_list->id");
  $urlCopyCourse = new moodle_url("/local/copycourse/copycourse.php?catid=$value_list->id");

  $courseArray[]=array('cateid'=>$value_list->id,
    'name'=>$value_list->name,
    'image'=>$test ,
    'parentid' => $value_list->parent,
    'createdate'=>$createdate,
    'path'=>$value_list->path,
    'urlCat'=>$urlCat,
    "urlCopyCourse"=>$urlCopyCourse


   );
}



$data['courses']=$courseArray;
$data['url']=$url;
$data['is_true'] = true;
}



echo  $OUTPUT->render_from_template('local_copycourse/copycourse', $data);
echo $OUTPUT->footer();

?>

