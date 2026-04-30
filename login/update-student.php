<?php
require('../config.php');
require_once('edit_student_form.php');
require_once($CFG->dirroot.'/local/emailtemplates/email_sender.php');
// $userid = 120; // Get user ID from the URL or request
$token = $_GET['token'];
if(isset($token))
$_SESSION['token']=$token;

$userid=base64_decode($_SESSION['token']);
 $student = $DB->get_record('student', array('userid' => $userid));
// $student = $DB->get_record('students', array('userid' => $userid));
// $student->schoolid = $data->school;
// $student->gradeid = $data->grade;
// $student->courseid = $data->course;
// $student->section = $data->section;
$PAGE->set_pagelayout('login');

if ($student->status == 0) {
   



$form = new edit_student_form($userid);

if ($form->is_cancelled()) {
    redirect("$CFG->wwwroot/login");
} elseif ($data = $form->get_data()) {
    global $DB;

    // Update user record
    $user = new stdClass();
    $user->id = $userid;
    $user->firstname = $data->firstname;
    $user->lastname = $data->lastname;
    $user->email = $data->email;
    $user->mobile_number = $data->mobile_number;
    $DB->update_record('user', $user);

    // Update student record
    $student = $DB->get_record('student', array('userid' => $userid));

if ($student) {
    // Ensure the 'id' field is set correctly
    $student->id;
    $student->schoolid = $data->school;
    $student->gradeid = $data->grade;
    $student->courseid = $data->course;
    $student->section = $data->section;
    $student->status =2;
    // Update the student record
    $DB->update_record('student', $student);



    \local_emailtemplates\email_sender::send_email("update", $userid, "update",0);



} 

    // Redirect to a success page
    redirect("$CFG->wwwroot/login/update_success.php");
} else {
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
}
else{
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('Permissiondenied', 'local_students'), 'notifyproblem');
    $continueurl1 = new moodle_url('/login');
    echo $OUTPUT->single_button($continueurl1, get_string('continue'),'get');
    echo $OUTPUT->footer();
    die();
}