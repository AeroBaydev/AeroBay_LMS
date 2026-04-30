<?php
require_once('../../config.php');

require_login();

$ids = required_param('ids', PARAM_RAW);
$student_ids = json_decode($ids, true);

if (empty($student_ids)) {
    echo json_encode(['status' => 'error', 'message' => 'No students selected.']);
    exit;
}

global $DB;

// 1) Bulk DB update – this is already good and fast
list($insql, $params) = $DB->get_in_or_equal($student_ids, SQL_PARAMS_NAMED, 'uid');
$sql = "UPDATE {student} SET status = 1 WHERE userid $insql";
$DB->execute($sql, $params);

// 2) Single bulk event instead of one per user
$event = \local_studentapproval\event\user_approved::create([
    'context'  => context_system::instance(),
    'objectid' => (int) reset($student_ids) ?: 0,
    'other'    => [
        'studentids' => $student_ids,
        'count'      => count($student_ids),
    ],
]);
$event->trigger();

// 3) Return response quickly
echo json_encode(['status' => 'success', 'message' => 'Selected students approved successfully.']);
exit;
