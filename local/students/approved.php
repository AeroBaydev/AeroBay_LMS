<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');
require_once($CFG->dirroot . '/local/students/approval_lib.php');

require_login();

if (local_pocschool_is_trainer_user()) {
    throw new required_capability_exception(context_system::instance(), 'local/pocschool:view', 'nopermissions', '');
}

$ids = required_param('ids', PARAM_RAW);
$student_ids = json_decode($ids, true);

if (empty($student_ids)) {
    echo json_encode(['status' => 'error', 'message' => 'No students selected.']);
    exit;
}

global $DB, $USER;

$approvedby = local_students_get_action_actor_key((int) $USER->id);
$enrolledcount = 0;
foreach ($student_ids as $studentid) {
    $enrolledcount += local_students_approve_student((int)$studentid, $approvedby, $USER->id);
}

$event = \local_studentapproval\event\user_approved::create([
    'context'  => context_system::instance(),
    'objectid' => (int) reset($student_ids) ?: 0,
    'other'    => [
        'studentids' => $student_ids,
        'count'      => count($student_ids),
    ],
]);
$event->trigger();

echo json_encode([
    'status' => 'success',
    'message' => 'Selected students approved successfully.',
    'enrolledcount' => $enrolledcount,
]);
exit;
