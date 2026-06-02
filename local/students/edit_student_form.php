<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once('classes/form/edit_student_form.php');
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');

global $PAGE, $CFG, $DB;

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title('Edit Student Details');
$PAGE->navbar->add('Student Management', new moodle_url("$CFG->wwwroot/local/students/student_manage.php"));
$PAGE->navbar->add('Edit Student', new moodle_url("$CFG->wwwroot/local/students/edit_student_form.php", ['id' => $id]));
$PAGE->set_heading('Edit Student Details');

$id = optional_param('id', 0, PARAM_INT);

if (local_pocschool_is_trainer_user()) {
    throw new required_capability_exception(context_system::instance(), 'local/pocschool:view', 'nopermissions', '');
}

$student_record = (array)$DB->get_record('student', ['userid' => $id]);
$user_record = (array)$DB->get_record('user', ['id' => $id]);
$student_data = array_merge($student_record, $user_record);

$form = new edit_student_form(null, [
    'id' => $id,
    'studentid' => $student_record['id'], // Pass the student table id
    'schoolid' => $student_data['schoolid'],
    'gradeid' => $student_data['gradeid'],
    'courseid' => $student_data['courseid'],
    'sectionid' => $student_data['sectionid']
]);

$form->set_data($student_data);

if ($form->is_cancelled()) {
    redirect("$CFG->wwwroot/local/students/student_manage.php");
} elseif ($data = $form->get_data()) {
    // Prepare user update data
    $user_update = new stdClass();
    $gradeid = $_POST['gradeid'];
    $courseid = $_POST['courseid'];
    $sectionid = $_POST['sectionid'];
    $user_update->id = $id; // This is the user id
    $user_update->username = $data->username;
    $user_update->firstname = $data->firstname;
    $user_update->lastname = $data->lastname;
    $user_update->email = $data->email;

    // Update user record
    $DB->update_record('user', $user_update);

    // Prepare student update data
    $student_update = new stdClass();
    $student_update->id = $student_record['id']; // Use the id from the student table
    $student_update->userid = $id;
    $student_update->dob = $data->dob;
    $student_update->schoolid = $data->schoolid;
    $student_update->gradeid = $gradeid;
    $student_update->courseid = $courseid;
    $student_update->sectionid = $sectionid;
    $student_update->parent = $data->parent;
    $student_update->registrationNo = $data->registrationNo;
    $student_update->contact_number = $data->contact_number;
    $student_update->address = $data->address;
    $student_update->interest = $data->interest;
    $student_update->hobbies = $data->hobbies;
    $student_update->section =$data->section;
    // Update student record
    $DB->update_record('student', $student_update);

    redirect("$CFG->wwwroot/local/students/student_manage.php", get_string('updatesuccess', 'local_students'), 2);
} else {
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
