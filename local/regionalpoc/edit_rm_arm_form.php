<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once('classes/form/edit_regionalpoc_form.php');

global $PAGE, $CFG, $DB;

require_login();
$usertype = optional_param('usertype', '', PARAM_TEXT);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title('Edit RM/ARM');
$PAGE->navbar->add('RM/ARM Management', "$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?usertype=$usertype");
$PAGE->navbar->add('Update RM/ARM Details', "$CFG->wwwroot/local/regionalpoc/edit_rm_arm_form.php?id=$id");
$PAGE->set_heading(' Update RM/ARM Details');

$id = optional_param('id', 0, PARAM_INT);
$regionalpoc_record = (array)$DB->get_record('regionalpoc', ['userid' => $id]);

 if($regionalpoc_record['usertype']=="regionalmanager"){
    $regionalpoc_record['usertype']="rm";
    }
    elseif($regionalpoc_record['usertype']=="asstmanager"){
        $regionalpoc_record['usertype']="arm";
    }
$form = new edit_regionalpoc_form(null, ['userid' => $regionalpoc_record->userid,'id' => $regionalpoc_record->userid]);

$form->set_data($regionalpoc_record);

if ($form->is_cancelled()) { 
    $usertype= $_POST['usertype'];
     redirect("$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?usertype=$usertype");
} elseif ($data = $form->get_data()) {

    $user = $DB->get_record('user', array('id' => $data->userid), '*', MUST_EXIST);

    $regionalpoc = new stdClass();
    if(empty($data->password)){
        $regionalpoc->password = $user->password;}
    else{
        $regionalpoc->password = $data->password;}
    // Ensure the ID is set here
    $regionalpoc->username = $data->username;
    $regionalpoc->firstname = $data->firstname;
    $regionalpoc->lastname = $data->lastname;
    $regionalpoc->dob = $data->dob;
    $regionalpoc->blood_group = $data->blood_group;
    $regionalpoc->email = $data->email;
    $regionalpoc->contact_number = $data->contact_number;
    $regionalpoc->permanent_address = $data->permanent_address;
    $regionalpoc->current_address = $data->current_address;
    $regionalpoc->alternative_address = $data->alternative_address;
    $regionalpoc->experience = $data->experience;
    $regionalpoc->ctc = $data->ctc;
    $regionalpoc->date_of_joining = $data->date_of_joining;
    $regionalpoc->designation = $data->designation;
    $regionalpoc->id = $data->userid;
    user_update_user($regionalpoc);
    $regionalpoc->id = $data->id;
    $DB->update_record('regionalpoc', $regionalpoc);

    redirect("$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?usertype=$data->usertype", get_string('updatesuccess', 'local_regionalpoc'), 2);
} else {
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
