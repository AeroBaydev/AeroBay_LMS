<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once('classes/form/student_form.php');
require_once($CFG->dirroot . '/local/dashboard/lib.php');
global $PAGE, $CFG;

$PAGE->set_context(context_system::instance());
$PAGE->requires->css(new moodle_url('/local/students/custom.css'));
$mform = new student_form();
if ($mform->is_cancelled()) {
    redirect("$CFG->wwwroot/login/index.php");
} elseif ($data = $mform->get_data()) {

    global $USER;
    $gradeid = $_POST['gradeid'];
    $courseid = $_POST['courseid'];
    $sectionid = $_POST['sectionid'];
    $student = new stdClass();
    $year = date('y');
    $student_id_prefix = $year.'POCSTU';
    $lastNumber = $DB->get_field_sql('SELECT MAX(id) FROM {student}', null);
    $newLastNumber = $lastNumber + 1;
    $student_id = $student_id_prefix . str_pad($newLastNumber, 3, '0', STR_PAD_LEFT);
    $student->student_id = $student_id;
    // $DB->set_field('student', 'last_number', $newLastNumber, array('id' => $lastNumber));

    $student->username = $data->username;
    $student->firstname = $data->firstname;
    $student->lastname = $data->lastname;
    $student->password = $data->password;
    $student->mnethostid = 1;
    $student->dob = $data->dob;
    $student->schoolid = $data->schoolid;
    $student->courseid = $courseid;
    $student->gradeid = $gradeid;
    $student->sectionid = $sectionid;
    $student->parent = $data->parent;
    $student->email = $data->email;
    $student->registrationNo = $data->registrationNo;
    $student->contact_number = $data->contact_number;
    $student->address = $data->address;
    $student->interest = $data->interest;
    $student->hobbies = $data->hobbies;
    $student->confirmed = 0;
    $student->last_number = $newLastNumber;
    $student->status = 2;
    $student->createdby =$USER->id;


    $user_id = user_create_user($student);
    $roleid = $DB->get_record_sql("SELECT id from {role} WHERE shortname = 'student'");
    $context = context_system::instance($courseid);
    role_assign($roleid->id, $user_id, $context->id);
    if ($user_id !== false) {
        $student->userid = $user_id;
        $DB->insert_record('student', $student);
        $studentname = fullname((object) [
            'firstname' => $student->firstname,
            'lastname' => $student->lastname,
        ]);
        $gradename = local_dashboard_get_grade_name((int) $gradeid);
        local_dashboard_log_activity(
            'student_added',
            'Student added',
            trim($studentname . ' added' . ($gradename ? ' to ' . $gradename : '')),
            (int) $student->schoolid,
            [
                'metadata' => [
                    'studentuserid' => (int) $user_id,
                    'gradeid' => (int) $gradeid,
                    'courseid' => (int) $courseid,
                ],
            ]
        );
        redirect("$CFG->wwwroot/local/students/student_manage.php", get_string('studentsuccess', 'local_students'), 2);
    } else {
        print_error('usercreationerror', 'local_students');
    }
} else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
