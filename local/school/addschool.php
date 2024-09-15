<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once('classes/form/school_form.php');
require_once($CFG->dirroot.'/local/school/lib.php');

global $PAGE, $CFG, $DB, $OUTPUT;

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title('New School');
$PAGE->navbar->add('School Management', "$CFG->wwwroot/local/school/");
$PAGE->navbar->add('Add School', "$CFG->wwwroot/local/school/AddSchool.php");
$PAGE->set_heading('Create New School');

$mform = new school_form();

if ($mform->is_cancelled()) {
    redirect("$CFG->wwwroot/local/school/index.php");
} elseif ($data = $mform->get_data()) {
    $state = $data->state_name;
    $stateCodes = [
        "Andhra Pradesh" => "AP",
        "Arunachal Pradesh" => "AR",
        "Assam" => "AS",
        "Bihar" => "BR",
        "Chhattisgarh" => "CG",
        "Goa" => "GA",
        "Gujarat" => "GJ",
        "Haryana" => "HR",
        "Himachal Pradesh" => "HP",
        "Jharkhand" => "JH",  
        "Karnataka" => "KA",
        "Kerala" => "KL",
        "Madhya Pradesh" => "MP",
        "Maharashtra" => "MH",
        "Manipur" => "MN",
        "Meghalaya" => "ML",
        "Mizoram" => "MZ",
        "Nagaland" => "NL",
        "Odisha" => "OR",
        "Punjab" => "PB",
        "Rajasthan" => "RJ",
        "Sikkim" => "SK",
        "Tamil Nadu" => "TN",
        "Telangana" => "TS",
        "Tripura" => "TR",
        "Uttar Pradesh" => "UP",
        "Uttarakhand" => "UK",
        "West Bengal" => "WB",
        "Andaman and Nicobar Islands" => "AN",
        "Chandigarh" => "CH",
        "Dadra and Nagar Haveli and Daman and Diu" => "DN",
        "Lakshadweep" => "LD",
        "Delhi" => "DL",
        "Puducherry" => "PY",
        "Ladakh" => "LA",
        "Jammu and Kashmir" => "JK"
    ];
    $stateCode = $stateCodes[$state];
    
    $year = date('y');
    $school_id_prefix = $year.'AB'.$stateCode;
    
    $lastNumber = $DB->get_field_sql('SELECT MAX(id) FROM {school}', null);
    $newLastNumber = $lastNumber + 1;
    $school_id = $school_id_prefix . str_pad($newLastNumber, 3, '0', STR_PAD_LEFT);

    // Insert school record
    $data->school_id = $school_id;
    $data->timecreated = time();   
    
    // Update last_number field
    $DB->set_field('school', 'last_number', $newLastNumber, array('id' => $lastNumber));
    
    // Create cohort
    // $cohort = new stdClass();
    // $cohort->contextid = context_system::instance()->id;
    // $cohort->name = $data->school_sortname;
    // $cohort->idnumber = 'testid';
    // $cohort->description = 'NOTHING';
    // $cohort->descriptionformat = FORMAT_HTML;
    // $cohortid = cohort_add_cohort($cohort);
    
    // Create category and sub category
    $category = new stdClass();
    $category->name = $data->school_name;
    $category->description = 'This is the sub category for school';
    $category->parent = 0;
    $category->idnumber = $school_id;
     $categoryid = core_course_category::create($category);
    if($categoryid->id){
        // Create school
        $data->course_cat_id = $categoryid->id;
        $school->about = $data->about;
        $id = $DB->insert_record('school', $data);
       // end Create school
        // Create sub category
        $category = new stdClass();
        $cohort = new stdClass();
        if($data->selectsubcategory>1){
        foreach ($data->selectsubcategory as $key => $value) {
        $category_grade = \core_course_category::get($value); 
        $category->name = $category_grade->name;
        $category->description = 'This is the sub category for grade';
        $category->parent = $categoryid->id;
        $category->idnumber = $school_id.$category_grade->name;
        $categoryid_sub = core_course_category::create($category);

        // $cohort->contextid = context_system::instance()->id;
        // $cohort->name = $data->school_sortname."_".$category_grade->name;
        // $cohort->idnumber = 'testid';
        // $cohort->description = 'NOTHING';
        // $cohort->descriptionformat = FORMAT_HTML;
        // $cohortid = cohort_add_cohort($cohort);



        }
      }
        //end  Create sub category
    }
       // Create category and sub category

    $schoolassign = new stdClass();
    $schoolassign->schoolid = $id;
    $schoolassign->userid = $USER->id;
    $schoolassign->timecreated = time();
    // $DB->insert_record('schoolassign', $schoolassign);
    

    redirect("$CFG->wwwroot/local/school/", get_string('schoolsuccess', 'local_school'), 2);
} else {
    echo $OUTPUT->header();
    $mform->display();
    // var_dump($categoryid);
    // die;

    echo $OUTPUT->footer();
}
?>
