<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once('classes/form/card_form.php');
// require_once($CFG->dirroot.'/local/attendancecard/lib.php');

global $PAGE, $CFG, $DB, $OUTPUT;

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title('New attendancecard');
$PAGE->navbar->add('Manage Attendance Badge', "$CFG->wwwroot/local/attendancecard/");
$PAGE->navbar->add('Add Attendance Badge', "$CFG->wwwroot/local/attendancecard/addattendancecard.php");
$PAGE->set_heading('Create New Attendance Badge ');
$PAGE->requires->js('/local/attendancecard/js/custom.js');
$mform = new card_form();

if ($mform->is_cancelled()) {
    redirect("$CFG->wwwroot/local/attendancecard/index.php");
}
 elseif ($data = $mform->get_data()) {    
    global $DB, $USER;
    // die("as");
    // File storage API
        $new_name = $mform->get_new_filename('badgefile');

        $path= 'badgesimg/'.$new_name;
        $fullpath = "$CFG->httpswwwroot/local/attendancecard/". $path;
        $success = $mform->save_file('badgefile', $path, true);

        // Save record to DB
        $attendancecard = new stdClass();
        $attendancecard->name = $data->name;
        // $attendancecard->schoolid = $data->school;
        // $attendancecard->gradeid = $data->grade ?? 0;
        $attendancecard->imgpath = $fullpath;
        $attendancecard->percentages = $data->completion;; // Store file URL in DB
   

        $DB->insert_record('attendancecard', $attendancecard);
    
    
    redirect("$CFG->wwwroot/local/attendancecard/", get_string('attendancecardsuccess', 'local_attendancecard'), 2);
} else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
?>
