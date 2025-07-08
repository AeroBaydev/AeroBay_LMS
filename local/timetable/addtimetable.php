<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once('classes/form/timetable_form.php');
require_once($CFG->dirroot.'/local/timetable/lib.php');
 $catid = optional_param('catid', '', PARAM_INT);
$schoolid = optional_param('schoolid', '', PARAM_INT);

global $PAGE, $CFG, $DB, $OUTPUT;

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title('New timetable');
$PAGE->navbar->add('timetable Management', "$CFG->wwwroot/local/timetable/");
$PAGE->navbar->add('Add timetable', "$CFG->wwwroot/local/timetable/addbadgecard.php");
$PAGE->set_heading('Create New timetable');

$customdata = ['catid' => $catid, 'schoolid' => $schoolid];
$mform = new timetable_form(null, $customdata);


if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/timetable/create_timetable.php', [
        'catid' => $catid,
        'schoolid' => $schoolid
    ]));
}

 elseif ($data = $mform->get_data()) {    
    global $DB, $USER;
    $record = new stdClass();
    $record->schoolid = $data->school;
    $record->gradeid = $data->grade;
    $record->period = $data->period;
    $record->day = $data->day;
    $record->timecreated = time();
    $record->createdby = $USER->id;
    
    // Insert data into the timetable table
    if (!empty($data->timetableid)) {
        // Update existing record
        $record->id = $data->timetableid;
        $DB->update_record('timetable', $record);
    } else {
        // Insert new record
        $DB->insert_record('timetable', $record);
    }
    
    // Redirect with success message
    redirect(new moodle_url("/local/timetable/create_timetable.php?catid=$catid&schoolid=$schoolid"), get_string('timetablesaved', 'local_timetable'));
} else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
?>
