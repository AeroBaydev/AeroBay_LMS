<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');

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
