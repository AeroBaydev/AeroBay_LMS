<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once('classes/form/subcard_form.php');
// require_once($CFG->dirroot.'/local/assessmentcard/lib.php');

global $PAGE, $CFG, $DB, $OUTPUT;
$parent = optional_param('id', '', PARAM_INT);
require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title('New assessmentcard');
// $PAGE->navbar->add('assessmentcard Management', "$CFG->wwwroot/local/assessmentcard/");
// $PAGE->navbar->add('Add assessmentcard', "$CFG->wwwroot/local/assessmentcard/addassessmentcard.php");
$PAGE->set_heading('Create New assessmentcard');
$actionurl = new moodle_url("/local/assessmentcard/addsub_card.php?id=$parnetid");
$parentiddata = $DB->get_record('assessmentcard', ['parentid' => $parent]);
if($parentiddata){
$mform = new subcard_form($actionurl,$parent,$parent);
}
else{
   
    $mform = new subcard_form($actionurl,$parent,$parent);  
}
if ($mform->is_cancelled()) {
print_r($mform->get_data());
$parent=$_POST['parentid'];

    redirect("$CFG->wwwroot/local/assessmentcard/subcardindex.php?id=$parent");
}
 elseif ($data = $mform->get_data()) {    
    global $DB, $USER;
    // die("as");
    // File storage API
        $new_name = $mform->get_new_filename('badgefile');
        // $parent = isset($parent) ? $parent : 0;
        $path= 'badgesimg/'.$new_name;
        $fullpath = "$CFG->httpswwwroot/local/assessmentcard/". $path;
        $success = $mform->save_file('badgefile', $path, true);

        // Save record to DB
        $assessmentcard = new stdClass();
        $assessmentcard->name = $data->name;
    
        $assessmentcard->imgpath = $fullpath;
        $assessmentcard->rang1 = 0; 
        $assessmentcard->rang2 = 0; 
        $assessmentcard->parentid = $data->parentid;
   

        $DB->insert_record('assessmentcard', $assessmentcard);
    
    
    redirect("$CFG->wwwroot/local/assessmentcard/subcardindex.php?id=$data->parentid", get_string('assessmentcardsuccess', 'local_assessmentcard'), 2);
} else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
?>
