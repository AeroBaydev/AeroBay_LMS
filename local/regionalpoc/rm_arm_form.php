<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once('classes/form/regionalpoc_form.php');
require_once('../../lib/moodlelib.php');


global $PAGE, $CFG,$DB,$USER;

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title('Regional Poc Registration');
$PAGE->navbar->add('RM/ARM Management', "$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?roleid=12");
$PAGE->navbar->add('Add New RM/ARM', "$CFG->wwwroot/local/regionalpoc/rm_arm_form.php");
// $PAGE->set_heading('Regionalpoc Registration Form');

$mform = new regionalpoc_form();

if ($mform->is_cancelled()) {
    redirect("$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?roleid=0");
} elseif ($data = $mform->get_data()) {
    $regionalpoc = new stdClass();
    $regionalpoc->username = $data->username;
    $regionalpoc->firstname = $data->firstname;
    $regionalpoc->lastname = $data->lastname;
    $regionalpoc->password = $data->password;
    $regionalpoc->dob = $data->dob;
    $regionalpoc->mnethostid = 1;
    $regionalpoc->confirmed = 1;
    $regionalpoc->blood_group = $data->blood_group;
    $regionalpoc->email = $data->email;
    $regionalpoc->contact_number = $data->contact_number;
    $regionalpoc->permanent_address = $data->permanent_address;
    $regionalpoc->current_address = $data->current_address;
    $regionalpoc->alternative_address = $data->alternative_address;
    $regionalpoc->experience = $data->experience;
    $regionalpoc->ctc = $data->ctc;
    // $regionalpoc->roleid = $data->role;
    $regionalpoc->date_of_joining = $data->date_of_joining;
    $regionalpoc->designation = $data->designation;
    $regionalpoc->pocid = $USER->id;
    $regionalpoc->usertype = $data->role;
        
  
    // var_dump($regionalpoc);die;


    //$user_id = user_create_user($regionalpoc);
    // set_user_preference('auth_forcepasswordchange', 1, $user_id);
    //$user_id = user_create_user($regionalpoc);
    // var_dump($regionalpoc->role);die;
    // if ($regionalpoc->role == 'regionalmanager') {

    //     $context = context_system::instance();
    //     $role=$DB->get_record_sql("SELECT id from {role} where shortname = 'regionalmanager'");
    //     $regionalpoc->roleid=$role->id;
    //     role_assign($role->id, $user_id, $context->id);
    
  
    // } elseif ($regionalpoc->role == 'asstmanager') {
    //     $context = context_system::instance();
    //     $role=$DB->get_record_sql("SELECT id from {role} where shortname = 'asstmanager'");
    //     $regionalpoc->roleid=$role->id;
    //     role_assign($role->id, $user_id, $context->id);

    // }
     $user_id = user_create_user($regionalpoc);
    if ($user_id !== false) {
        $regionalpoc->userid = $user_id;
        $DB->insert_record('regionalpoc', $regionalpoc);

        if($regionalpoc->usertype=="regionalmanager"){
            $usertype="rm";
            }
            elseif($regionalpoc_record->usertype=="asstmanager"){
                $usertype="arm";
            }


        redirect("$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?usertype=$usertype", get_string('regionalpocsuccess', 'local_regionalpoc'), 2);
    } else {
        print_error('usercreationerror', 'local_regionalpoc');
    }
} else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
