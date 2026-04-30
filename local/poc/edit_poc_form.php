<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once('classes/form/edit_poc_form.php');

global $PAGE, $CFG, $DB;

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title('Poc Registration Update');
$PAGE->navbar->add('POC Management', "$CFG->wwwroot/local/poc/poc_management.php");
$PAGE->navbar->add('Update Poc Details', "$CFG->wwwroot/local/poc/edit_poc_form.php?id=$id");
// $PAGE->set_heading('Poc Update Form');

$id = optional_param('id', 0, PARAM_INT);

$poc_record = (array)$DB->get_record('poc', ['userid' => $id]);
$poc_record1 = (array)$DB->get_record('user', ['id' => $id]);
$poc_data = array_merge($poc_record, $poc_record1);
unset($poc_data['password']);

$poc = new stdClass();

$form = new edit_poc_form(null, ['id' => $id, 'pocid' => $id]);

$form->set_data($poc_data);

if ($form->is_cancelled()) {
    redirect("$CFG->wwwroot/local/poc/poc_management.php");
} elseif ($data = $form->get_data()) {
    // var_dump($data);die;
    $user = $DB->get_record('user', array('id' => $id), '*', MUST_EXIST);
    $poc = new stdClass();
    if(empty($data->password)){
        $poc->password = $user->password;}
    else{
        $poc->password = $data->password;}
    $poc->id = $data->id; // Ensure the ID is set here
    $poc->username = $data->username;
    $poc->firstname = $data->firstname;
    $poc->lastname = $data->lastname;
    // $poc->password = $data->password;
    $poc->dob = $data->dob;
    $poc->blood_group = $data->blood_group;
    $poc->email = $data->email;
    $poc->contact_number = $data->contact_number;
    $poc->permanent_address = $data->permanent_address;
    $poc->current_address = $data->current_address;
    $poc->alternative_address = $data->alternative_address;
    $poc->experience = $data->experience;
    $poc->ctc = $data->ctc;
    $poc->date_of_joining = $data->date_of_joining;
    $poc->designation = $data->designation;
   // $DB->update_record('user', $poc);
    user_update_user($poc);
    $poc->id = $poc_record['id'];

    $DB->update_record('poc', $poc);

    redirect("$CFG->wwwroot/local/poc/poc_management.php", get_string('updatesuccess', 'local_poc'), 2);
} else {
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
