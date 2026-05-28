<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once('classes/form/poc_form.php');
require_once('../../lib/moodlelib.php');
require_once($CFG->dirroot.'/local/emailtemplates/email_sender.php');
require_once($CFG->dirroot . '/local/dashboard/lib.php');
require_once($CFG->dirroot . '/course/lib.php');


global $DB, $OUTPUT, $PAGE, $USER,$CFG;
require_login();
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title('poc Registration');
$PAGE->navbar->add('POC Management', "$CFG->wwwroot/local/poc/poc_management.php");
$PAGE->navbar->add('Add POC', "$CFG->wwwroot/local/poc/poc_form.php");

echo $OUTPUT->header();
$mform = new poc_form();

if ($mform->is_cancelled()) {
    redirect("$CFG->wwwroot/local/poc/poc_management.php");
} elseif ($data = $mform->get_data()) {
 //   $temp_password = generate_random_password(12);
    $poc = new stdClass();
    $poc->username = $data->username; 
    $poc->firstname = $data->firstname;
    $poc->lastname = $data->lastname;
    $poc->password = $data->password;
    $poc->dob = $data->dob;
    $poc->mnethostid = 1;
    $poc->confirmed = 1;
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
    // $_SESSION['password'] = $poc->password = $data->password;
    $year = date('y');
    $school_id_prefix = $year.'POC';
    $lastNumber = $DB->get_field_sql('SELECT MAX(id) FROM {poc}', null);;
    $newLastNumber = $lastNumber + 1;
    $poc_id = $school_id_prefix . str_pad($newLastNumber, 3, '0', STR_PAD_LEFT);
    
    
$context = context_system::instance();
$contextid = $context->id;
    $role=$DB->get_record_sql("SELECT id from {role} where archetype = 'manager'");
   

    $user_id = user_create_user($poc);
    set_user_preference('auth_forcepasswordchange', 1, $user_id);
    if ($user_id !== false) {

        try {
            // Assign the role.
       role_assign($role->id, $user_id, $contextid);
        }
        catch (Exception $e) {
            echo "Failed to assign role: " . $e->getMessage();
        }
        $poc->userid = $user_id;
        $poc->roleid = $role->id;
        $poc->poc_id =$poc_id;
        $poc->contextid = $context->id;
        $insert = $DB->insert_record('poc', $poc);
   
        if($insert){
            $pocname = fullname((object) [
                'firstname' => $poc->firstname,
                'lastname' => $poc->lastname,
            ]);
            $site = get_site();
            local_dashboard_log_activity(
                'poc_added',
                'POC added',
                $pocname . ' added as POC',
                0,
                [
                    'schoolname' => format_string($site->fullname),
                    'metadata' => [
                        'pocrecordid' => (int) $insert,
                        'pocuserid' => (int) $user_id,
                    ],
                ]
            );

            $poc_session_date = new stdClass();
            $currentYear = date("Y");
            $poc_session_date->session_date = $currentYear;
            $poc_session_date->pocid = $user_id;
            $poc_session_date->status = 1;
            $sessionid = $DB->insert_record('poc_session_date', $poc_session_date);
            local_dashboard_log_activity(
                'session_scheduled',
                'Session scheduled',
                'POC session scheduled for ' . $currentYear,
                0,
                [
                    'metadata' => [
                        'sessionid' => (int) $sessionid,
                        'pocuserid' => (int) $user_id,
                    ],
                ]
            );



            $result = \local_emailtemplates\email_sender::send_email("poc", $user_id, $data->password,0);
        }
        redirect("$CFG->wwwroot/local/poc/poc_management.php", get_string('pocsuccess', 'local_poc'), 2);
    } else {
        print_error('usercreationerror', 'local_poc'); 
    }
} else {
  
    $mform->display();
    echo $OUTPUT->footer();
}
