<?php
require('../../config.php');
require_login();

$data = optional_param_array('status', [], PARAM_ALPHA);
$remarks = optional_param_array('remark', [], PARAM_TEXT);
$attendancecardid = required_param('attendancecardid', PARAM_INT);


// print_r($data);
// die;

foreach ($data as $studentid => $status) {
    $remark = $remarks[$studentid] ?? '';

    // Check if the record already exists
    $existing_record = $DB->get_record('attendancecard_student', ['attendancecardid' => $attendancecardid, 'studentid' => $studentid]);

    if ($existing_record) {
        // Update existing record
        $existing_record->status = $status;
        $existing_record->remark = $remark;
        $existing_record->timecreated = time();
        $DB->update_record('attendancecard_student', $existing_record);
    } else {
        // Insert new record
        $record = new stdClass();
        $record->studentid = $studentid;
        $record->status = $status;
        $record->remark = $remark;
        $record->attendancecardid = $attendancecardid;
        $record->timecreated = time();
        $DB->insert_record('attendancecard_student', $record);
    }
}


redirect(new moodle_url('/local/attendancecard/index.php'), 'attendancecard saved successfully');
