<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once('classes/form/edit_school_form.php');
global $PAGE, $CFG, $DB;
$PAGE->requires->js(new moodle_url('/local/school/amd/src/numeric_validation.js'));
$schoolid = optional_param('id', 0, PARAM_INT);
require_login();
require_admin();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title('School Registration');
$PAGE->navbar->add('School Management', "$CFG->wwwroot/local/school/index.php");
$schoolid = optional_param('id', 0, PARAM_INT);
$PAGE->navbar->add('Update School Details', "$CFG->wwwroot/local/school/edit_school.php?id=$schoolid");
$PAGE->set_heading('Update School Details');

$school_record = $DB->get_record('school', ['id' => $schoolid]);
$school = new stdClass();
$school->id = $school_record->schoolid;
$school->school_name = $school_record->school_name;
$school->school_sortname = $school_record->school_sortname;
$school->school_address = $school_record->school_address;
$school->principal_name = $school_record->principal_name;
$school->principal_email = $school_record->principal_email;
$school->principal_contact = $school_record->principal_contact;
$school->state_name = $school_record->state_name;
$school->coordinator_name1 = $school_record->coordinator_name1;
$school->coordinator_email1 = $school_record->coordinator_email1;
$school->coordinator_contact1 = $school_record->coordinator_contact1;
$school->coordinator_name2 = $school_record->coordinator_name2;
$school->coordinator_email2 = $school_record->coordinator_email2;
$school->coordinator_contact2 = $school_record->coordinator_contact2;
$school->coordinator_name3 = $school_record->coordinator_name3;
$school->coordinator_email3 = $school_record->coordinator_email3;
$school->coordinator_contact3 = $school_record->coordinator_contact3;
$school->coordinator_name4 = $school_record->coordinator_name4;
$school->coordinator_email4 = $school_record->coordinator_email4;
$school->coordinator_contact4 = $school_record->coordinator_contact4;
$school->syllabus = $school_record->syllabus;
$school->aerobay_fees = $school_record->aerobay_fees;
$school->about = $school_record->about;

$form = new edit_school_form($schoolid);

$form->set_data($school);

if ($form->is_cancelled()) {
    redirect("$CFG->wwwroot/local/school/");
} elseif ($data = $form->get_data()) {

    $school_record = $DB->get_record('school', ['id' => $data->schoolid]);

    $school = new stdClass();
    $school->id = $data->schoolid;
    $school->school_name = $data->school_name;
    $school->school_sortname = $data->school_sortname;
    $school->school_address = $data->school_address;
    $school->principal_name = $data->principal_name;
    $school->principal_email = $data->principal_email;
    $school->principal_contact = $data->principal_contact;
    $school->coordinator_name1 = $data->coordinator_name1;
    $school->coordinator_email1 = $data->coordinator_email1;
    $school->coordinator_contact1 = $data->coordinator_contact1;
    $school->coordinator_name2 = $data->coordinator_name2;
    $school->coordinator_email2 = $data->coordinator_email2;
    $school->coordinator_contact2 = $data->coordinator_contact2;
    $school->coordinator_name3 = $data->coordinator_name3;
    $school->coordinator_email3 = $data->coordinator_email3;
    $school->coordinator_contact3 = $data->coordinator_contact3;
    $school->coordinator_name4 = $data->coordinator_name4;
    $school->coordinator_email4 = $data->coordinator_email4;
    $school->coordinator_contact4 = $data->coordinator_contact4;
    $school->syllabus = $data->syllabus;
    $school->state_name = $school_record->state_name;
    $school->aerobay_fees = $data->aerobay_fees;
    $school->about = $data->about;
  
    $DB->update_record('school', $school);
    
    $cohort = $DB->get_record('cohort', ['name' => $school_record->school_sortname]);
    $objcohort = new stdClass();
    $objcohort->id = $cohort->id;
    $objcohort->name = $data->school_sortname;
    
    // $DB->update_record('cohort', $objcohort);
   
     $school_categories = $DB->get_record('course_categories', ['idnumber' => $school_record->school_id]);
     $school_categories_grade = $DB->get_records('course_categories', ['parent' => $school_categories->id]);
  
    $optionSubCatSchool=[];
    foreach ($school_categories_grade as $category) {
        $optionSubCatSchool[$category->id] = $category->name;
    }
  
  
    $optionSubCat=[];
    $categories = $DB->get_records_sql("
        SELECT cc.id, cc.name, cc.description
        FROM {course_categories} cc
        WHERE
        cc.visible = 1 and cc.parent=171
      ");

foreach ($categories as $category) {
$optionSubCat[$category->id] = $category->name;
}

         $optionSubCatselected=[];
        foreach ($categories as $category) {
            if(!in_array($category->name,$optionSubCatSchool)){
            $optionSubCatselected[$category->id] = $category->name;
         }
        }
//         print_r($optionSubCatSchool);
//         echo "</br>";
//         print_r($optionSubCat);
//         echo "</br>a";
// print_r($optionSubCatselected);
// echo "</br>";
//  print_r($data->selectsubcategory);
// die;
   $Schoolcategories = $DB->get_record('course_categories', array('idnumber' => $school_record->school_id)); 
   if($data->selectsubcategory){
    $categorydata = new stdClass();
    foreach ($optionSubCatselected as $key => $value) {
        if(in_array($key,$data->selectsubcategory)){
        $category_grade = \core_course_category::get($key); 
        $categorydata->name = $category_grade->name;
        $categorydata->description = 'This is the sub category for grade';
        $categorydata->parent =$Schoolcategories->id;
        $categorydata->idnumber = $school_record->school_id.$category_grade->name;
        $categoryid_sub = core_course_category::create($categorydata);
        }

    }
}









    $category = $DB->get_record('course_categories', ['idnumber' => $school_record->school_id]);
    $objcategory = new stdClass();
    $objcategory->id = $category->id;
    $objcategory->name = $data->school_name;
     $DB->update_record('course_categories', $objcategory);


    redirect("$CFG->wwwroot/local/school/index.php", get_string('updatesuccess', 'local_school'), 2);
} else {

    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
