<?php 

 require_once('../../config.php');
 require_once("$CFG->dirroot/course/classes/external/course_summary_exporter.php");
 require_once($CFG->dirroot.'/course/renderer.php');
//  echo "$CFG->dirroot/local/subject/classes/management/helper.php";
//  die;
 require_once("$CFG->dirroot/local/subject/classes/management/helper.php");
 $abc = new \local_subject\management\helper();
 unset($_SESSION['parentid']);
  require_login();
  if(!isloggedin()){

  return redirect(new moodle_url('/login'));
}
GLOBAL $DB,$USER,$OUTPUT;
$title = 'School Listing';
$pagetitle = $title;
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url('/local/subject/index.php');


if($_GET['parent']>0){
  $catId=$_GET['parent'];
 




  
$category_listing = $DB->get_records_sql("select * from {course_categories} where parent=$catId and visible=1", [], $limitfrom=0, $limitnum=0);
foreach ($category_listing as $key => $value_list) {
  $context = context_coursecat::instance($value_list->id);
  //$test="";
  //$test=file_rewrite_pluginfile_urls($value_list->description,'pluginfile.php', $context->id, 'coursecat', 'description', null);
  $course_url=$url."=".$value_list->id;
  $createdate = date('l, F d,Y H:i a', $value_list->$timemodified);

 

  $urlCat = new moodle_url("/local/subject/index.php?parent=$value_list->id");

  $urlCatEdit = new moodle_url("/local/subject/editcategorySchool.php?id=$value_list->id");


//barcrum
  $view_text="Create Chapter";
  $category_listing = $DB->get_record_sql("select path from {course_categories} where id=$catId and visible=1", [], $limitfrom=0, $limitnum=0);
  if($category_listing){
    $array = explode('/', $category_listing->path);
    $array = array_filter($array, 'strlen');
    $count = count($array);
   
    if($count==2){
      $data['is_true'] = false;
      $view_text="View Chapters";
      $courseurl= new moodle_url("/local/chapter/index.php?catId=$value_list->id");
     }else{
      $data['is_true'] = true;
     }
    
  }
  //end bradcrum
  $manageurl = new \moodle_url('/local/subject/managementSchool.php', array('categoryid' => $value_list->id));
  $baseurl = new \moodle_url($manageurl, array('sesskey' => \sesskey()));
  $urlCatDelete= new \moodle_url($baseurl, array('action' => 'deletecategory'));
  $urlCatDelete= new \moodle_url($urlCatDelete, array('parent' =>  $value_list->parent));
  $urlCatDelete = htmlspecialchars_decode($urlCatDelete);


  
  $courseArray[]=array('cateid'=>$value_list->id,
    'name'=>$value_list->name,
    'image'=>$test ,
    'parentid' => $value_list->parent,
    'createdate'=>$createdate,
   'path'=>$value_list->path,
   'urlCat'=>$urlCat,
   'urlCatEdit'=>$urlCatEdit,
   'courseurl'=>$courseurl,
   'viewchapter'=>$view_text,
   'urlCatDelete'=>$urlCatDelete


   );
}




$category_listing = $DB->get_record_sql("select path from {course_categories} where id=$catId and visible=1", [], $limitfrom=0, $limitnum=0);
if($category_listing){
  $url = new moodle_url("/local/subject/index.php?parent=0");
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
  $url = new moodle_url("/local/subject/index.php?parent=$value");
  $badcrudurl .='<li class="breadcrumb-item">
  <a href="'.$url.'">'.$category_listing->name.'</a>
  </li>';}}
$badcrudurl .='</ol>
</nav>
</div>';
// echo $badcrudurl;
// die; 


//create url
$url = new moodle_url("/local/subject/editcategorySchool.php?parent=$catId");
$data['badcrudurl']=$badcrudurl;
$data['courses']=$courseArray;
$data['view']=$viename;
$data['url']=$url;


}

else{
$view_category=""; 
$category_listing = $DB->get_records_sql("select * from {course_categories} where parent=0 and visible=1", [], $limitfrom=0, $limitnum=0);
foreach ($category_listing as $key => $value_list) {
  $context = context_coursecat::instance($value_list->id);
  //$test="";
  //$test=file_rewrite_pluginfile_urls($value_list->description,'pluginfile.php', $context->id, 'coursecat', 'description', null);
  $course_url=$url."=".$value_list->id;
  $createdate = date('l, F d,Y H:i a', $value_list->$timemodified);

 

  $urlCat = new moodle_url("/local/subject/index.php?parent=$value_list->id");
  $urlCatEdit = new moodle_url("/local/subject/editcategorySchool.php?id=$value_list->id");
  
//   $category = core_course_category::get($value_list->id);
// $actions = $abc->get_category_listitem_actions($category);

// foreach ($actions as $key => $action) {
// $urlCatDelete= $action['url'];
// $urlCatDelete = htmlspecialchars_decode($urlCatDelete);
// }

        $manageurl = new \moodle_url('/local/subject/managementSchool.php', array('categoryid' => $value_list->id));
        $baseurl = new \moodle_url($manageurl, array('sesskey' => \sesskey()));
        $urlCatDelete= new \moodle_url($baseurl, array('action' => 'deletecategory'));
        $urlCatDelete= new \moodle_url($urlCatDelete, array('parent' =>  $value_list->parent));
        $urlCatDelete = htmlspecialchars_decode($urlCatDelete);


  $courseArray[]=array('cateid'=>$value_list->id,
    'name'=>$value_list->name,
    'image'=>$test ,
    'parentid' => $value_list->parent,
    'createdate'=>$createdate,
   'path'=>$value_list->path,
   'urlCat'=>$urlCat,
   'urlCatEdit'=>$urlCatEdit,
   'urlCatDelete'=>$urlCatDelete

   );
}

$url = new moodle_url("/course/editcategorySchool.php?parent=0");

$data['courses']=$courseArray;
$data['url']=$url;
$data['is_true'] = true;
}


echo  $OUTPUT->header();
echo  $OUTPUT->render_from_template('local_subject/subject', $data);
echo $OUTPUT->footer();

?>

