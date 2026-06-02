<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');
require_once($CFG->dirroot . '/local/dashboard/lib.php');
require_once($CFG->dirroot . '/local/students/approval_lib.php');
require_login();
$id = required_param('id', PARAM_INT);


global $DB;

if (local_pocschool_is_trainer_user()) {
    throw new required_capability_exception(context_system::instance(), 'local/pocschool:view', 'nopermissions', '');
}



if ($user = $DB->get_record('user', array('id' => $id))) {
    $student = $DB->get_record('student', ['userid' => $id], '*', IGNORE_MISSING);
    $studentname = fullname($user);
    $gradename = $student ? local_dashboard_get_grade_name((int) $student->gradeid) : '';
    $schoolid = $student ? (int) $student->schoolid : 0;
    $deletedby = local_students_get_action_actor_key((int) $USER->id);
    $deletedbylabel = local_students_format_action_actor($deletedby);
    local_dashboard_log_activity(
        'student_deleted',
        'Student removed',
        trim($studentname . ' removed' . ($gradename ? ' from ' . $gradename : '') . '. Deleted By: ' . $deletedbylabel),
        $schoolid,
        [
            'actorid' => (int) $USER->id,
            'actorname' => $deletedbylabel,
            'metadata' => [
                'studentuserid' => (int) $id,
                'studentrecordid' => $student ? (int) $student->id : 0,
                'gradeid' => $student ? (int) $student->gradeid : 0,
                'deletedby' => $deletedby,
                'deletedbylabel' => $deletedbylabel,
            ],
        ]
    );
    $deleted = $DB->delete_records('student', array('userid' => $id));
    $deleted1 = user_delete_user($user);
    echo json_encode(['status' => 'success']);
    }
