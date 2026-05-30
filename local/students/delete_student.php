<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');
require_once($CFG->dirroot . '/local/dashboard/lib.php');
require_once($CFG->dirroot . '/local/students/approval_lib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title('Delete Student');
$PAGE->set_heading('Delete Student');
require_login();

global $CFG, $DB;
    
$id = optional_param('id', 0, PARAM_INT);
// $userid = optional_param('userid', 0, PARAM_INT);

if (local_pocschool_is_trainer_user()) {
    throw new required_capability_exception(context_system::instance(), 'local/pocschool:view', 'nopermissions', '');
}

if (optional_param('confirm', 0, PARAM_INT)) {

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
        $deleted1 = user_delete_user($user);
        $deleted = $DB->delete_records('student', array('userid' => $id));
        }
    
    if ($deleted !== false) {
        redirect("$CFG->wwwroot/local/students/student_manage.php", get_string('deletesuccess', 'local_students'), 2);
    } else {
        print_error('deletion_failed', 'local_students', "$CFG->wwwroot/my/");
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('deleteconfirm', 'local_students'), 
                         new moodle_url("$CFG->wwwroot/local/students/delete_student.php?confirm=1&id=$id"), 
                         new moodle_url("$CFG->wwwroot/local/students/student_manage.php"));
    echo $OUTPUT->footer();
}
