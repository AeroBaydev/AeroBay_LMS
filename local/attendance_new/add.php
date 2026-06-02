<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');

global $DB, $PAGE, $OUTPUT;

require_login();
$context = context_system::instance();
// require_capability('moodle/course:manageactivities', $context);

// Get schoolid and gradeid from URL
$schoolid = optional_param('schoolid', 0, PARAM_INT);
$gradeid = optional_param('catid', 0, PARAM_INT);
local_pocschool_require_grade_access($schoolid, $gradeid);

$PAGE->set_url(new moodle_url('/local/attendance_new/create_attendance.php', ['schoolid' => $schoolid, 'catid' => $gradeid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('createattendance', 'local_attendance_new'));
$PAGE->set_heading(get_string('createattendance', 'local_attendance_new'));

class create_attendance_form extends moodleform {
    public function definition() {
        global $schoolid, $gradeid;
        $mform = $this->_form;
        $mform->addElement('textarea', 'description', get_string('description', 'local_attendance_new'), 'wrap="virtual" rows="10" cols="50"');
        $mform->setType('description', PARAM_TEXT);
        // Date selection
        $mform->addElement('date_selector', 'attendancedate', get_string('date', 'local_attendance_new'));
        $mform->addRule('attendancedate', null, 'required');

        // Hidden fields for schoolid and gradeid
        $mform->addElement('hidden', 'schoolid');
        $mform->setType('schoolid', PARAM_INT);
        $mform->setDefault('schoolid', $schoolid);

        $mform->addElement('hidden', 'gradeid');
        $mform->setType('gradeid', PARAM_INT);
        $mform->setDefault('gradeid', $gradeid);

        // Submit button
        $mform->addElement('submit', 'submitbutton', get_string('submit'));
    }
}

$mform = new create_attendance_form();
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/attendance_new/index.php'));
} elseif ($data = $mform->get_data()) {
    local_pocschool_require_grade_access($data->schoolid, $data->gradeid);
    if (local_pocschool_is_trainer_user() && userdate($data->attendancedate, '%Y-%m-%d') !== userdate(time(), '%Y-%m-%d')) {
        throw new required_capability_exception(context_system::instance(), 'local/pocschool:view', 'nopermissions', '');
    }

    $record = new stdClass();
    $record->description = $data->description;
    $record->date = $data->attendancedate;
    $record->schoolid = $data->schoolid;
    $record->gradeid = $data->gradeid;
    $record->timecreated = time();

    // Insert into database
    $DB->insert_record('attendance', $record);

    // Redirect with parameters
    redirect(new moodle_url('/local/attendance_new/create_attendance.php', [
        'catid' => $data->gradeid, 
        'schoolid' => $data->schoolid
    ]), get_string('attendancecreated', 'local_attendance_new'),'notifysuccess');
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
