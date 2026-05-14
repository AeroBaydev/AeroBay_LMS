<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');

$id = required_param('id', PARAM_INT); // Get the attendance ID safely
$gradeid = optional_param('catid', 0, PARAM_INT);
$schoolid = optional_param('schoolid', 0, PARAM_INT);

// Check if the user is logged in
require_login();
if (!is_siteadmin($USER->id) && !local_pocschool_is_poc_user()) {
    print_error('Access denied');
}

// Confirm the record exists
$record = $DB->get_record('attendance', ['id' => $id]);
if (!$record) {
    print_error('Attendance record not found');
}

local_pocschool_require_grade_access($record->schoolid, $record->gradeid);

// If the user confirmed deletion, delete the record
if (optional_param('confirm', 0, PARAM_BOOL)) {
    $DB->delete_records('attendance', ['id' => $id]);

    // Redirect to create_attendance.php with parameters
    redirect(new moodle_url('/local/attendance_new/create_attendance.php', [
        'catid' => $record->gradeid,
        'schoolid' => $record->schoolid
    ]), 'Attendance record deleted successfully', null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display confirmation page
echo $OUTPUT->header();
echo $OUTPUT->confirm(
    "Are you sure you want to delete this attendance record?",
    new moodle_url('delete_attendance.php', ['id' => $id, 'catid' => $record->gradeid, 'schoolid' => $record->schoolid, 'confirm' => 1]),
    new moodle_url('/local/attendance_new/create_attendance.php', ['catid' => $record->gradeid, 'schoolid' => $record->schoolid])
);
echo $OUTPUT->footer();
