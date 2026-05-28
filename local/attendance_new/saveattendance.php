<?php
require('../../config.php');
require_login();
require_sesskey();
require_once($CFG->dirroot . '/local/attendance_new/lib.php');
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');
require_once($CFG->dirroot . '/local/dashboard/lib.php');

$data         = optional_param_array('status', [], PARAM_ALPHA);
$remarks      = optional_param_array('remark', [], PARAM_TEXT);
$schoolid     = required_param('schoolid', PARAM_INT);
$gradeid      = required_param('catid', PARAM_INT);
$attendanceid = required_param('attendanceid', PARAM_INT);
local_pocschool_require_grade_access($schoolid, $gradeid);

$attendance = $DB->get_record('attendance', ['id' => $attendanceid], '*', MUST_EXIST);
if ((int)$attendance->schoolid !== $schoolid || (int)$attendance->gradeid !== $gradeid) {
    throw new required_capability_exception(context_system::instance(), 'local/pocschool:view', 'nopermissions', '');
}
if (local_pocschool_is_trainer_user() && userdate($attendance->date, '%Y-%m-%d') !== userdate(time(), '%Y-%m-%d')) {
    throw new required_capability_exception(context_system::instance(), 'local/pocschool:view', 'nopermissions', '');
}

$students = get_students($schoolid, $gradeid);
foreach ($students as $student) {
    $studentid = (int) $student->id;
    $status = isset($data[$studentid]) && in_array($data[$studentid], ['P', 'A'], true) ? $data[$studentid] : 'A';

    $record = new stdClass();
    $record->attendanceid = $attendanceid;
    $record->studentid    = $studentid;
    $record->schoolid     = $schoolid;
    $record->gradeid      = $gradeid;
    $record->status       = $status;
    $record->remark       = isset($remarks[$studentid])
                                ? clean_param($remarks[$studentid], PARAM_TEXT)
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

$gradename = local_dashboard_get_grade_name((int) $gradeid);
local_dashboard_log_activity(
    'attendance_submitted',
    'Attendance submitted',
    ($gradename ? $gradename . ' attendance completed' : 'Attendance completed'),
    (int) $schoolid,
    [
        'metadata' => [
            'attendanceid' => (int) $attendanceid,
            'gradeid' => (int) $gradeid,
            'date' => (int) $attendance->date,
        ],
    ]
);

redirect(
    new moodle_url('/local/attendance_new/create_attendance.php', [
        'catid'    => $gradeid,
        'schoolid' => $schoolid,
    ]),
    get_string('attendancecreated', 'local_attendance'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
