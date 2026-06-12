<?php
define('CLI_SCRIPT', true);
require('config.php');
$USER = $DB->get_record('user', ['id' => 3043]);
$student = $DB->get_record('student', ['userid' => 3043]);
$schoolid = (int) ($student->schoolid ?? 0);
$gradeid = (int) ($student->gradeid ?? 0);
$monthstart = mktime(0, 0, 0, (int) date('n'), 1, (int) date('Y'));
$nextmonthstart = mktime(0, 0, 0, (int) date('n') + 1, 1, (int) date('Y'));

$attendance = $DB->get_record_sql(
    "SELECT COUNT(ast.id) AS totalcount,
            SUM(CASE WHEN UPPER(ast.status) = 'P' THEN 1 ELSE 0 END) AS presentcount
       FROM {attendance_student} ast
       JOIN {attendance} att ON att.id = ast.attendanceid
      WHERE ast.studentid = :userid
        AND att.schoolid = :schoolid
        AND att.gradeid = :gradeid
        AND att.date >= :monthstart
        AND att.date < :nextmonthstart
        AND UPPER(ast.status) IN ('P', 'A')",
    [
        'userid' => (int) $USER->id,
        'schoolid' => $schoolid,
        'gradeid' => $gradeid,
        'monthstart' => $monthstart,
        'nextmonthstart' => $nextmonthstart,
    ]
);
$attendancepresent = 0;
$attendancetotal = 0;
if ($attendance) {
    $attendancepresent = (int) $attendance->presentcount;
    $attendancetotal = (int) $attendance->totalcount;
}
$attendancepercent = $attendancetotal > 0 ? ($attendancepresent / $attendancetotal) * 100 : 0;
echo "total: $attendancetotal, present: $attendancepresent, percent: $attendancepercent\n";
