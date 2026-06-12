<?php
$host = '127.0.0.1';
$db   = 'lms_db';
$user = 'root';
$pass = 'root';
$port = 8889;

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$userid = 3043;
$schoolid = 470;
$gradeid = 471;
$monthstart = mktime(0, 0, 0, (int) date('n'), 1, (int) date('Y'));
$nextmonthstart = mktime(0, 0, 0, (int) date('n') + 1, 1, (int) date('Y'));

echo "monthstart: $monthstart\n";
echo "nextmonthstart: $nextmonthstart\n";

$sql = "SELECT COUNT(ast.id) AS totalcount,
               SUM(CASE WHEN UPPER(ast.status) = 'P' THEN 1 ELSE 0 END) AS presentcount
          FROM mdl_attendance_student ast
          JOIN mdl_attendance att ON att.id = ast.attendanceid
         WHERE ast.studentid = :userid
           AND att.schoolid = :schoolid
           AND att.gradeid = :gradeid
           AND att.date >= :monthstart
           AND att.date < :nextmonthstart
           AND UPPER(ast.status) IN ('P', 'A')";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'userid' => $userid,
    'schoolid' => $schoolid,
    'gradeid' => $gradeid,
    'monthstart' => $monthstart,
    'nextmonthstart' => $nextmonthstart
]);
$result = $stmt->fetch();

$attendancepresent = (int) $result['presentcount'];
$attendancetotal = (int) $result['totalcount'];

$attendancepercent = $attendancetotal > 0 ? ($attendancepresent / $attendancetotal) * 100 : 0;

echo "presentcount: $attendancepresent\n";
echo "totalcount: $attendancetotal\n";
echo "attendancepercent raw value: $attendancepercent\n";

$sql2 = "SELECT ast.id AS ast_id, att.id AS att_id, ast.studentid, ast.status, att.date, att.schoolid, att.gradeid
           FROM mdl_attendance_student ast
           JOIN mdl_attendance att ON att.id = ast.attendanceid
          WHERE ast.studentid = :userid
            AND att.schoolid = :schoolid
            AND att.gradeid = :gradeid
            AND att.date >= :monthstart
            AND att.date < :nextmonthstart
            AND UPPER(ast.status) IN ('P', 'A')";

$stmt2 = $pdo->prepare($sql2);
$stmt2->execute([
    'userid' => $userid,
    'schoolid' => $schoolid,
    'gradeid' => $gradeid,
    'monthstart' => $monthstart,
    'nextmonthstart' => $nextmonthstart
]);
$rows = $stmt2->fetchAll();

echo "MATCHING ROWS:\n";
foreach ($rows as $row) {
    echo "attendance.id: {$row['att_id']}, attendance_student.id: {$row['ast_id']}, studentid: {$row['studentid']}, status: {$row['status']}, date: {$row['date']}, human: " . date('Y-m-d H:i:s', $row['date']) . "\n";
}
