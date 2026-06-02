<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/local/emailtemplates/email_sender.php');
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');
require_once($CFG->dirroot . '/local/students/approval_lib.php');
require_login();
$id = required_param('id', PARAM_INT);
$reason = required_param('reason', PARAM_TEXT);

global $DB;

if (local_pocschool_is_trainer_user()) {
    throw new required_capability_exception(context_system::instance(), 'local/pocschool:view', 'nopermissions', '');
}

// header('Content-Type: application/json');

if ($DB->set_field('student', 'status', 0, array('userid' =>$id ))) {
    $DB->set_field('student', 'approvedby', local_students_get_action_actor_key((int) $USER->id), ['userid' => $id]);
    // Optionally log the rejection reason or perform other actions.
    \local_emailtemplates\email_sender::send_email("reject", $id, "reject",$reason);
    
    // Send a success response
    echo json_encode(['status' => 'success']);
} else {
    // Send an error response
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete record']);
}
