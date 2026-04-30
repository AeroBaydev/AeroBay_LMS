<?php

require_once "../../config.php";
require_once($CFG->dirroot.'/local/emailtemplates/email_sender.php');
require_login();
global $PAGE, $CFG, $DB;
$id = required_param('id', PARAM_INT);

$context = context_user::instance($id);
$PAGE->set_context($context);
// require_capability('moodle/user:edit', $context);

if (!$DB->record_exists('student', array('userid' => $id))) {
    print_error('invalidstudent', 'local_students');
}

$DB->set_field('student', 'status', 1, array('userid' => $id));

if(is_siteadmin()){
$DB->set_field('student', 'approvedby', "admin", array('userid' => $id));
$approvedby="Admin";
}
else{
    $DB->set_field('student', 'approvedby', "poc", array('userid' => $id));
    $approvedby="POC";
}
\local_studentapproval\event\user_approved::create([
    'context'  => context_system::instance(),
    'objectid' => $id, // The ID of the user being approved.
])->trigger();

$studentdata = $DB->get_record('user', array('id' => $id));
// $result = \local_emailtemplates\email_sender::send_email("approved", $id,"0",$approvedby);
if($student){
    $DB->set_field('user', 'confirmed', 1, array('id' => $id));
    

    $student = $DB->get_record('student', array('userid' => $id));
    $context = context_course::instance($student->courseid);
    $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
    // if (!is_enrolled($context, $id)) {
    //     // Not already enrolled so try enrolling them.
    //     if (!enrol_try_internal_enrol($student->courseid, $id, $studentroleid, time())) {
    //         // There's a problem.
    //         throw new moodle_exception('unabletoenrolerrormessage', 'langsourcefile');
    //     }
        
    // }


}
// Send email notification
// $email_subject = "Approval Notification";
// $email_body = "Dear $student->firstname $student->lastname,\n\nYour registration has been approved.\n\nRegards,\nAdmin";
// $email_to = $studentdata;
    
// Retrieve the support user to use as the sender
// $studentdata = core_user::get_support_user();
// email_to_user($email_to, $supportuser, $email_subject, $email_body);

redirect(new moodle_url('/local/students/student_manage.php'), 'Student approved successfully.', null, \core\output\notification::NOTIFY_SUCCESS);
