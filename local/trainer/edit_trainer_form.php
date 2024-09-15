<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once('classes/form/edit_trainer_form.php');

global $PAGE, $CFG, $DB;

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title('Edit Trainer Details');
$PAGE->navbar->add('Trainer Management', "$CFG->wwwroot/local/trainer/trainer_manage.php");
$PAGE->navbar->add('Edit Trainer', "$CFG->wwwroot/local/trainer/edit_trainer_form.php?id=$id&userid=$userid");
$PAGE->set_heading('Edit Trainer Details');

$id = optional_param('id', 0, PARAM_INT);

$trainer_record = (array)$DB->get_record('trainer', ['userid' => $id]);
$trainer_record1 = (array)$DB->get_record('user', ['id' => $id]);
$trainer_data = array_merge($trainer_record, $trainer_record1);
unset($trainer_data['password']);
$form = new edit_trainer_form(null, ['id' => $id, 'trainerid' => $id]);

$form->set_data($trainer_data);

if ($form->is_cancelled()) {
    redirect("$CFG->wwwroot/local/trainer/trainer_manage.php");
} elseif ($data = $form->get_data()) {
    $user = $DB->get_record('user', array('id' => $id), '*', MUST_EXIST);
    $trainer = new stdClass();
    if(empty($data->password)){
        $trainer->password = $user->password;}
    else{
        $trainer->password = $data->password;}

    $trainer = new stdClass();
    $trainer->id = $data->id; // Ensure the ID is set here
    $trainer->username = $data->username;
    $trainer->firstname = $data->firstname;
    $trainer->lastname = $data->lastname;
    // $trainer->password = $data->password;
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
    
      
    // $DB->update_record('user', $trainer);
    user_update_user($trainer);

    $trainer->id = $trainer_record['id'];
    $DB->update_record('trainer',$trainer);


    redirect("$CFG->wwwroot/local/trainer/trainer_manage.php", get_string('updatesuccess', 'local_trainer'), 2);
} else {
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
