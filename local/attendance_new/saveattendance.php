<?php
require('../../config.php');
require_login();
require_sesskey();

$data         = optional_param_array('status', [], PARAM_ALPHA);
$remarks      = optional_param_array('remark', [], PARAM_TEXT);
$schoolid     = required_param('schoolid', PARAM_INT);
$gradeid      = required_param('catid', PARAM_INT);
$attendanceid = required_param('attendanceid', PARAM_INT);

if (!empty($data)) {
    foreach ($data as $raw_studentid => $status) {
        $studentid = (int) $raw_studentid;

        if ($studentid <= 0) {
            continue;
        }

        $record = new stdClass();
        $record->attendanceid = $attendanceid;
        $record->studentid    = $studentid;
        $record->schoolid     = $schoolid;
        $record->gradeid      = $gradeid;
        $record->status       = $status;
        $record->remark       = isset($remarks[$raw_studentid])
                                    ? clean_param($remarks[$raw_studentid], PARAM_TEXT)
                                    : '';

        $existing = $DB->get_record('attendance_student', [
            'studentid'    => $studentid,
            'attendanceid' => $attendanceid,
        ]);

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('attendance_student', $record);
        } else {
            $record->timecreated = time();
            $DB->insert_record('attendance_student', $record);
        }
    }
}

redirect(
    new moodle_url('/local/attendance_new/create_attendance.php', [
        'catid'    => $gradeid,
        'schoolid' => $schoolid,
    ]),
    get_string('attendancecreated', 'local_attendance'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
