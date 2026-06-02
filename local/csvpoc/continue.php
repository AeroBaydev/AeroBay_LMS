<?php

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/moodlelib.php'); // Required for email_to_user function
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot . '/local/dashboard/lib.php');

global $DB, $OUTPUT, $PAGE;

require_login(); // Ensure the user is logged in
$context = context_system::instance();
// require_capability('moodle/site:config', $context); // Ensure the user has the correct capability

// Decode the list of users from the request
$users = json_decode(required_param('users', PARAM_RAW));

$schoolid = $_POST['schoolid'];
$gradeid  = $_POST['gradeid'];
$courseid = $_POST['courseid'];

$new_users = [];
$errors = [];
$existing_users = [];
$createdcount = 0;

// Loop through users and create them
foreach ($users as $user) {
    if ($user->exists) {
        $existing_users[] = $user;
    } else {
        $newuser = new stdClass();
        $newuser->username = $user->username;
        $newuser->firstname = $user->firstname;
        $newuser->lastname = $user->lastname;
        $newuser->email = $user->email;
        $newuser->password = 'ChangeMe!123'; // Set a default password, or generate one
        $newuser->confirmed = 1; // Confirm the user account
        $newuser->mnethostid = $CFG->mnet_localhost_id; // Set to the local Moodle host ID

        // Attempt to create the user
        try {
            $userid = user_create_user($newuser, false, false);

            if($userid){
                if (isset($_SESSION['userIdPoc'])) {
                    $userIdPoc=$_SESSION['userIdPoc'];
                  
                 }
            $student = new stdClass();
            $student->userid = $userid;    
            $student->schoolid = $schoolid;
            // $student->courseid = $courseid;
            $student->section = $user->section;
            $student->gradeid = $gradeid;
            $student->status = 2;
            $student->createdby =$userIdPoc;
            $year = date('y');
            $student_id_prefix = $year.'POCSTU';
            $lastNumber = $DB->get_field_sql('SELECT MAX(id) FROM {student}', null);
            $newLastNumber = $lastNumber + 1;
            $student_id = $student_id_prefix . str_pad($newLastNumber, 3, '0', STR_PAD_LEFT);
            $student->student_id = $student_id;
            $DB->insert_record('student', $student);
            $createdcount++;
        }

            if ($userid) {
                $new_users[] = $newuser;

                // Send a welcome email
                $subject = get_string('welcomeemailsubject', 'local_csvpoc');
                $message = get_string('welcomeemailmessage', 'local_csvpoc', $newuser);
               // email_to_user($newuser, core_user::get_support_user(), $subject, $message);
            } else {
                $errors[] = ["username" => $user->username, "error" => "Failed to create user"];
            }
        } catch (Exception $e) {
            $errors[] = ["username" => $user->username, "error" => "Error creating user: " . $e->getMessage()];
        }
    }
}

if ($createdcount > 0) {
    local_dashboard_log_activity(
        'bulk_student_import',
        'Bulk student import',
        $createdcount . ' student' . ($createdcount === 1 ? '' : 's') . ' added',
        (int) $schoolid,
        [
            'countvalue' => $createdcount,
            'metadata' => [
                'gradeid' => (int) $gradeid,
                'courseid' => (int) $courseid,
                'importtime' => time(),
            ],
            'dedupekey' => sha1('bulk_student_import|' . $schoolid . '|' . $gradeid . '|' . time()),
        ]
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('userscreated', 'local_csvpoc'));

// Display existing users in a table
if (!empty($existing_users)) {
    echo html_writer::start_tag('h3', array('class' => 'info'));
    echo get_string('usersalreadyexists', 'local_csvpoc');
    echo html_writer::end_tag('h3');
    
    echo html_writer::start_tag('table', array('class' => 'generaltable'));
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('status'));
    echo html_writer::tag('th', get_string('username'));
    echo html_writer::tag('th', get_string('firstname'));
    echo html_writer::tag('th', get_string('lastname'));
    echo html_writer::tag('th', get_string('email'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    
    foreach ($existing_users as $user) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', "User not added - already registered");
      //  echo html_writer::tag('td', "<span style='color:red;'>user already exists</span>");
        echo html_writer::tag('td', $user->username);
        echo html_writer::tag('td', $user->firstname);
        echo html_writer::tag('td', $user->lastname);
        echo html_writer::tag('td', $user->email);
        echo html_writer::end_tag('tr');
    }
    
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
}

// Display created users in a table
if (!empty($new_users)) {
    echo html_writer::start_tag('h3', array('class' => 'success'));
    echo get_string('userscreatedsuccess', 'local_csvpoc');
    echo html_writer::end_tag('h3');
    
    echo html_writer::start_tag('table', array('class' => 'generaltable'));
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('username'));
    echo html_writer::tag('th', get_string('firstname'));
    echo html_writer::tag('th', get_string('lastname'));
    echo html_writer::tag('th', get_string('email'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    
    foreach ($new_users as $user) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $user->username);
        echo html_writer::tag('td', $user->firstname);
        echo html_writer::tag('td', $user->lastname);
        echo html_writer::tag('td', $user->email);
        echo html_writer::end_tag('tr');
    }
    
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
} else {
    echo $OUTPUT->notification(get_string('nouserscreated', 'local_csvpoc'), 'notifymessage');
}

// Display errors in a table
if (!empty($errors)) {
    echo html_writer::start_tag('h3', array('class' => 'error'));
    echo get_string('errors', 'local_csvpoc');
    echo html_writer::end_tag('h3');
    
    echo html_writer::start_tag('table', array('class' => 'generaltable'));
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('username'));
    echo html_writer::tag('th', get_string('error'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    
    foreach ($errors as $error) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $error['username']);
        echo html_writer::tag('td', $error['error']);
        echo html_writer::end_tag('tr');
    }
    
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
}
//redirect(new moodle_url("/local/pocschool/index.php?parent=$schoolid"));
echo $OUTPUT->continue_button(new moodle_url("/local/pocschool/index.php?parent=$schoolid"));
echo $OUTPUT->footer();
?>
