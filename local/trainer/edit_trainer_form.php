<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once('classes/form/edit_trainer_form.php');
require_once($CFG->dirroot . '/local/dashboard/lib.php');
require_once($CFG->dirroot . '/local/trainer/lib.php');

global $PAGE, $CFG, $DB;

require_login();

$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title('Edit Trainer Details');
$PAGE->navbar->add('Trainer Management', "$CFG->wwwroot/local/trainer/trainer_manage.php");
$PAGE->navbar->add('Edit Trainer', "$CFG->wwwroot/local/trainer/edit_trainer_form.php?id=$id");
$PAGE->set_heading('Edit Trainer Details');

$trainer_record = (array)$DB->get_record('trainer', ['userid' => $id]);
$trainer_record1 = (array)$DB->get_record('user', ['id' => $id]);
$trainer_data = array_merge($trainer_record, $trainer_record1);
unset($trainer_data['password']);

$pocuserid = $trainer_record['createdby'] ?? $USER->id;
if (isset($_SESSION['userIdPoc'])) {
    $pocuserid = $_SESSION['userIdPoc'];
}

$form = new edit_trainer_form(null, ['id' => $id, 'trainerid' => $id, 'pocuserid' => $pocuserid]);

$form->set_data($trainer_data);

if ($form->is_cancelled()) {
    redirect("$CFG->wwwroot/local/trainer/trainer_manage.php");
} elseif ($data = $form->get_data()) {
    $user = $DB->get_record('user', array('id' => $id), '*', MUST_EXIST);

    $userupdate = new stdClass();
    $userupdate->id = $data->id;
    $userupdate->username = $data->username;
    $userupdate->firstname = $data->firstname;
    $userupdate->lastname = $data->lastname;
    $userupdate->email = $data->email;
    if (!empty($data->password)) {
        $userupdate->password = $data->password;
    }
    user_update_user($userupdate);

    $trainer = new stdClass();
    $trainer->id = $trainer_record['id'];
    $trainer->userid = $id;
    $trainer->username = $data->username;
    $trainer->firstname = $data->firstname;
    $trainer->lastname = $data->lastname;
    $trainer->dob = $data->dob;
    $trainer->blood_group = $data->blood_group;
    $trainer->email = $data->email;
    $trainer->contact_number = $data->contact_number;
    $trainer->permanent_address = $data->permanent_address;
    $trainer->current_address = $data->current_address;
    $trainer->alternative_address = $data->alternative_address;
    $trainer->experience = $data->experience;
    $trainer->ctc = $data->ctc;
    $trainer->date_of_joining = $data->date_of_joining;
    $trainer->designation = $data->designation;
    $trainer->schoolid = !empty($data->schoolid) ? $data->schoolid : null;
    
      
    $DB->update_record('trainer',$trainer);
    local_trainer_sync_school_assignment((int) $id, (int) $data->schoolid, (int) $pocuserid);
    $mappedcourses = $DB->get_records('poc_copy_course', [
        'schoolid' => $data->schoolid,
        'status' => 1
    ]);

    $trainername = fullname((object) [
        'firstname' => $trainer->firstname,
        'lastname' => $trainer->lastname,
    ]);
    $firstmapping = !empty($mappedcourses) ? reset($mappedcourses) : false;
    $gradename = $firstmapping ? local_dashboard_get_grade_name((int) $firstmapping->gradeid) : '';
    local_dashboard_log_activity(
        'trainer_assigned',
        'Trainer assigned',
        trim('Trainer ' . $trainername . ' mapped' . ($gradename ? ' to ' . $gradename : '')),
        (int) $data->schoolid,
        [
            'metadata' => [
                'traineruserid' => (int) $id,
                'schoolid' => (int) $data->schoolid,
            ],
        ]
    );


    redirect("$CFG->wwwroot/local/trainer/trainer_manage.php", get_string('updatesuccess', 'local_trainer'), 2);
} else {
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
