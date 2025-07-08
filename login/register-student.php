<?php
require('../config.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->libdir . '/authlib.php');
require_once('register_student_form.php');
 require_once($CFG->dirroot.'/local/emailtemplates/email_sender.php');
$PAGE->set_url('/login/signup.php');
$PAGE->set_context(context_system::instance());

if (isloggedin() and !isguestuser()) {
    // Prevent signing up when already logged in.
    echo $OUTPUT->header();
    echo $OUTPUT->box_start();
    $logout = new single_button(new moodle_url('/login/logout.php', array('sesskey' => sesskey(), 'loginpage' => 1)), get_string('logout'), 'post');
    $continue = new single_button(new moodle_url('/'), get_string('cancel'), 'get');
    echo $OUTPUT->confirm(get_string('cannotsignup', 'error', fullname($USER)), $logout, $continue);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}
function generate_code_from_name($firstname, $lastname, $iteration = 1) {
    global $DB;

    // Clean names: Keep only letters
    $firstname = preg_replace('/[^a-zA-Z]/', '', $firstname);
    $lastname = preg_replace('/[^a-zA-Z]/', '', $lastname);

    // Get the highest user ID and increment it for uniqueness
    $lastUserId = (int) $DB->get_field_sql('SELECT MAX(id) FROM {user}');
    $uniqueId = $lastUserId + $iteration;

    // Ensure the first name and last name have valid lengths
    $firstPart = substr($firstname, 0, 3); // First 3 chars of first name
    $lastPart = substr($lastname, 0, 3);   // First 3 chars of last name
    $uniqueSuffix = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, 3);
    // Convert to lowercase and format the final username
    $username = strtolower("{$firstPart}{$lastPart}{$uniqueSuffix}");

    return $username;
}


function generate_random_password($length = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
$form = new register_student_form(); 
$newaccount = get_string('newaccount');
$login      = get_string('login');

$PAGE->navbar->add($login);
$PAGE->navbar->add($newaccount);

$PAGE->set_pagelayout('login');
$PAGE->set_title($newaccount);
$PAGE->set_heading($SITE->fullname);
if ($form->is_cancelled()) {
    redirect("$CFG->wwwroot/login");
} elseif ($data = $form->get_data()) {
    $schoolid = isset($_POST['school']) ? $_POST['school'] : null;
    $gradeid = isset($_POST['grade']) ? $_POST['grade'] : null;
    $courseid = isset($_POST['course']) ? $_POST['course'] : null;
    $temp_password = generate_random_password(12);
   $temp_password_insert= hash_internal_user_password($temp_password);
    $newuser = new stdClass();
    $newuser->firstname = $data->firstname;
    $newuser->lastname = $data->lastname;
    $newuser->email = $data->email;
    $newuser->mobile_number = $data->mobile_number;
    $newuser->password = $temp_password_insert; // Set a default password, or generate one
  //  $newuser->suspended = 1;
    $newuser->confirmed = 1; // Confirm the user account
    $newuser->mnethostid = $CFG->mnet_localhost_id; 
    $newuser->username = generate_code_from_name($newuser->firstname , $newuser->lastname, 1);
    $newuser->timecreated = time();

    try {
        $userid = user_create_user($newuser, false, false);

        if($userid) {
            $student = new stdClass();
            $student->userid = $userid;    
            $student->contact_number = $data->mobile_number;
            $student->schoolid = $schoolid;
            $student->gradeid = $gradeid;
            $student->courseid = $courseid;
            $student->status = 2;
            $student->section = $data->section;
            $student->createdby = "self";
            $year = date('y');
            $student_id_prefix = $year.'POCSTU';
            $lastNumber = $DB->get_field_sql('SELECT MAX(id) FROM {student}', null);
            $newLastNumber = $lastNumber + 1;
            $student_id = $student_id_prefix . str_pad($newLastNumber, 3, '0', STR_PAD_LEFT);
            $student->student_id = $student_id;


         $insert= $DB->insert_record('student', $student);
          $studentdata = $DB->get_record('user', array('id' => $userid));
        //   $email_subject = "Registered Notification";
        //   $email_body = "Dear $student->firstname $student->lastname,\n\nYou have registered successfully!.\n\nRegards,\nAdmin ,\n your userid=$studentdata->username";
        //   $email_to = $studentdata;
            if($insert){
                $result = \local_emailtemplates\email_sender::send_email("welcome", $userid, "welcome",0);
            }
// Retrieve the support user to use as the sender
// $studentdata = core_user::get_support_user();
// email_to_user($email_to, $supportuser, $email_subject, $email_body);

        }
    } catch (Exception $e) {
        $errors[] = ["username" => $newuser->username, "error" => "Error creating user: " . $e->getMessage()];
    }

    // Redirect to a success page
    redirect("$CFG->wwwroot/login/register_success.php", get_string('registrationsuccess', 'local_students'));
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
