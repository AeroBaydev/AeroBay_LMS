<?php
define('CLI_SCRIPT', true);
require('config.php');

$userid = 3043;
$student = $DB->get_record('student', ['userid' => $userid]);
if (!$student) {
    die("Student 3043 not found\n");
}

$schoolid = (int) ($student->schoolid ?? 0);
$gradeid = (int) ($student->gradeid ?? 0);

$monthstart = mktime(0, 0, 0, (int) date('n'), 1, (int) date('Y'));
$nextmonthstart = mktime(0, 0, 0, (int) date('n') + 1, 1, (int) date('Y'));

echo "userid: $userid\n";
echo "schoolid: $schoolid\n";
echo "gradeid: $gradeid\n";
echo "monthstart: $monthstart\n";
echo "nextmonthstart: $nextmonthstart\n";

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
        'userid' => $userid,
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

echo "presentcount: $attendancepresent\n";
echo "totalcount: $attendancetotal\n";
echo "attendancepercent raw value: $attendancepercent\n";

$rows = $DB->get_records_sql(
    "SELECT ast.id AS ast_id, att.id AS att_id, ast.studentid, ast.status, att.date, att.schoolid, att.gradeid
       FROM {attendance_student} ast
       JOIN {attendance} att ON att.id = ast.attendanceid
      WHERE ast.studentid = :userid
        AND att.schoolid = :schoolid
        AND att.gradeid = :gradeid
        AND att.date >= :monthstart
        AND att.date < :nextmonthstart
        AND UPPER(ast.status) IN ('P', 'A')",
    [
        'userid' => $userid,
        'schoolid' => $schoolid,
        'gradeid' => $gradeid,
        'monthstart' => $monthstart,
        'nextmonthstart' => $nextmonthstart,
    ]
);

echo "MATCHING ROWS:\n";
foreach ($rows as $row) {
    echo "attendance.id: {$row->att_id}, attendance_student.id: {$row->ast_id}, studentid: {$row->studentid}, status: {$row->status}, date: {$row->date}, human: " . date('Y-m-d H:i:s', $row->date) . "\n";
}
