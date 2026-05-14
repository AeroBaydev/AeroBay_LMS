<?php

require_once "../../config.php";
require_once($CFG->dirroot.'/local/emailtemplates/email_sender.php');
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');
require_once($CFG->dirroot . '/local/students/approval_lib.php');
require_login();
global $PAGE, $CFG, $DB;
$id = required_param('id', PARAM_INT);

$context = context_user::instance($id);
$PAGE->set_context($context);
// require_capability('moodle/user:edit', $context);

if (!$DB->record_exists('student', array('userid' => $id))) {
    print_error('invalidstudent', 'local_students');
}

if (local_pocschool_is_trainer_user()) {
    throw new required_capability_exception(context_system::instance(), 'local/pocschool:view', 'nopermissions', '');
}

if(is_siteadmin()){
    $approvedby = "Admin";
    $approvedbykey = "admin";
}
else{
    $approvedby = "POC";
    $approvedbykey = "poc";
}

$enrolledcount = local_students_approve_student($id, $approvedbykey, $USER->id);

\local_studentapproval\event\user_approved::create([
    'context'  => context_system::instance(),
    'objectid' => $id, // The ID of the user being approved.
])->trigger();

$studentdata = $DB->get_record('user', array('id' => $id));
// $result = \local_emailtemplates\email_sender::send_email("approved", $id,"0",$approvedby);
// Send email notification
// $email_subject = "Approval Notification";
// $email_body = "Dear $student->firstname $student->lastname,\n\nYour registration has been approved.\n\nRegards,\nAdmin";
// $email_to = $studentdata;
    
// Retrieve the support user to use as the sender
// $studentdata = core_user::get_support_user();
// email_to_user($email_to, $supportuser, $email_subject, $email_body);

$message = $enrolledcount > 0
    ? 'Student approved and enrolled successfully.'
    : 'Student approved, but no mapped course was found for enrolment.';
$type = $enrolledcount > 0
    ? \core\output\notification::NOTIFY_SUCCESS
    : \core\output\notification::NOTIFY_WARNING;

redirect(new moodle_url('/local/students/student_manage.php'), $message, null, $type);
