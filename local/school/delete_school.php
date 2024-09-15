<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot.'/course/classes/category.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Delete School');
$PAGE->set_heading('Delete School');
require_login();


global $CFG, $DB;

$schoolid = optional_param('id', 0, PARAM_INT);
$sortname = optional_param('school_sortname', '', PARAM_TEXT);

if (optional_param('confirm', 0, PARAM_INT)) {
    
    
    function delete_category_with_subcategories($categoryid) {
        global $DB;
    
        // Fetch the main category
   
        // Fetch subcategories
        $subcategories = $DB->get_records('course_categories', ['parent' => $categoryid]);
        if($subcategories){
        // Recursively delete subcategories
        foreach ($subcategories as $subcategory) {
             // Delete the main category
        $coursecat = \core_course_category::get($subcategory->id);
            if($coursecat){
        $coursecat->delete_full();
    } 

        }
    }
       
    }
    

    $course_categories = $DB->get_record('course_categories', array('idnumber' => $sortname), 'id, visible');
    //
    // print_r($course_categories);
    // die;

    $deleted = $DB->delete_records('school', array('id' => $schoolid));

               delete_category_with_subcategories($course_categories->id);
              // $DB->delete_records('cohort', array('name'=>$sortname));
              // $DB->delete_records('course_categories', array('idnumber'=>$sortname));
              $coursecat = \core_course_category::get($course_categories->id);
              if($coursecat){     $coursecat->delete_full();} 

    if ($deleted !== false) {
       
        redirect("$CFG->wwwroot/local/school/index.php", get_string('deletesuccess', 'local_school'), 2);
    } else {
        print_error('deletion_failed', 'local_school', "$CFG->wwwroot/my/");
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('deleteconfirm', 'local_school'), 
                         new moodle_url("$CFG->wwwroot/local/school/delete_school.php?confirm=1&id=$schoolid&school_sortname=$sortname"), 
                         new moodle_url("$CFG->wwwroot/local/school/index.php"));
    echo $OUTPUT->footer();
}
