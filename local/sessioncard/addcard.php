<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once('classes/form/card_form.php');
// require_once($CFG->dirroot.'/local/sessioncard/lib.php');

global $PAGE, $CFG, $DB, $OUTPUT;

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title('New sessioncard');
$PAGE->navbar->add('Session Badge Management', "$CFG->wwwroot/local/sessioncard/");
$PAGE->navbar->add('Add sessioncard', "$CFG->wwwroot/local/sessioncard/addsessioncard.php");
$PAGE->set_heading('Create New sessioncard');

$mform = new card_form();

if ($mform->is_cancelled()) {
    redirect("$CFG->wwwroot/local/sessioncard/");
}
 elseif ($data = $mform->get_data()) {    
    global $DB, $USER;
    // die("as");
    // File storage API
        $new_name = $mform->get_new_filename('badgefile');

        $path= 'badgesimg/'.$new_name;
        $fullpath = "$CFG->httpswwwroot/local/sessioncard/". $path;
        $success = $mform->save_file('badgefile', $path, true);

        // Save record to DB
        $sessioncard = new stdClass();
        $sessioncard->name = $data->name;
        // $sessioncard->schoolid = $data->school;
        // $sessioncard->gradeid = $data->grade ?? 0;
        $sessioncard->imgpath = $fullpath;
        $sessioncard->percentages = $data->completion;; // Store file URL in DB
   

        $DB->insert_record('sessioncard', $sessioncard);
    
    
    redirect("$CFG->wwwroot/local/sessioncard/", get_string('sessioncardsuccess', 'local_sessioncard'), 2);
} else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
?>
