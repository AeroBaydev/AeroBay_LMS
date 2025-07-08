<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once('classes/form/card_form.php');
// require_once($CFG->dirroot.'/local/assessmentcard/lib.php');
$parent = optional_param('id', '', PARAM_TEXT);
global $PAGE, $CFG, $DB, $OUTPUT;

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
// $PAGE->set_title('New assessmentcard');
$PAGE->navbar->add('Assessment Badge Management', "$CFG->wwwroot/local/assessmentcard/index.php");
$PAGE->navbar->add('Add Assessment Badge', "$CFG->wwwroot/local/assessmentcard/addassessmentcard.php");
$PAGE->set_heading('Add new Assessment Badge');
$actionurl = new moodle_url("/local/assessmentcard/addcard.php?id=$parent");

$PAGE->requires->js('/local/assessmentcard/js/custom.js');
$mform = new card_form(actionurl: $actionurl);

if ($mform->is_cancelled()) {
    redirect("$CFG->wwwroot/local/assessmentcard/index.php");
}
 elseif ($data = $mform->get_data()) {    
    global $DB, $USER;
         $parent = isset($parent) ? $parent : 0;
        $new_name = $mform->get_new_filename('badgefile');

        $path= 'badgesimg/'.$new_name;
        $fullpath = "$CFG->httpswwwroot/local/assessmentcard/". $path;
        $success = $mform->save_file('badgefile', $path, true);

        // Save record to DB
        $assessmentcard = new stdClass();
        $assessmentcard->name = $data->name;
    
        $assessmentcard->imgpath = $fullpath;
        $assessmentcard->rang1 = $data->rang1; 
        $assessmentcard->rang2 = $data->rang2; 
        $assessmentcard->parentid = 0;

        $DB->insert_record('assessmentcard', $assessmentcard);
    
    
    redirect("$CFG->wwwroot/local/assessmentcard/", get_string('assessmentcardsuccess', 'local_assessmentcard'), 2);
} else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
?>
