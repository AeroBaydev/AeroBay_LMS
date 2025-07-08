<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once('classes/form/news_form.php');
require_once($CFG->dirroot.'/local/school/lib.php');

global $PAGE, $CFG, $DB, $OUTPUT;

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title('New School');
$PAGE->navbar->add('School Management', "$CFG->wwwroot/local/school/school_manage.php");
$PAGE->navbar->add('Add School', "$CFG->wwwroot/local/school/news_form.php");
$PAGE->set_heading('Create New School');

$mform = new news_form();

if ($mform->is_cancelled()) {
    redirect("$CFG->wwwroot/local/school/school_manage.php");
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
    $school_id_prefix = $year . 'AB' . $stateCode;
    
    $lastNumber = $DB->get_field_sql('SELECT MAX(id) FROM {school}', null);
    $newLastNumber = $lastNumber + 1;
    $school_id = $school_id_prefix . str_pad($newLastNumber, 3, '0', STR_PAD_LEFT);

    // Insert school record
    $data->school_id = $school_id;
    
    
    // Update last_number field
    $DB->set_field('school', 'last_number', $newLastNumber, array('id' => $lastNumber));
    
    // Create cohort
    $cohort = new stdClass();
    $cohort->contextid = context_system::instance()->id;
    $cohort->name = $data->school_sortname;
    $cohort->idnumber = 'testid';
    $cohort->description = 'NOTHING';
    $cohort->descriptionformat = FORMAT_HTML;
    $cohortid = cohort_add_cohort($cohort);
    
    // Create category
    $category = new stdClass();
    $category->name = $data->school_sortname;
    $category->description = 'This is the main category';
    $category->parent = 0;
    $categoryid = core_course_category::create($category);
    
    
    $data->course_cat_id = $categoryid->id;
    $id = $DB->insert_record('school', $data);

    $schoolassign = new stdClass();
    $schoolassign->schoolid = $id;
    $schoolassign->userid = $USER->id;
    $schoolassign->timecreated = time();


    $DB->insert_record('schoolassign', $schoolassign);
    // print_r($categoryid->id);
    // die;

    redirect("$CFG->wwwroot/local/school/school_manage.php", get_string('schoolsuccess', 'local_school'), 2);
} else {
    echo $OUTPUT->header();
    $mform->display();
    // var_dump($categoryid);
    // die;

    echo $OUTPUT->footer();
}
?>
