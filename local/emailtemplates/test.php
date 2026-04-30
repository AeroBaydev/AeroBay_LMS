<?php

require_once('../../config.php');
require_once($CFG->dirroot.'/local/emailtemplates/email_sender.php');
global $DB,$OUTPUT;
//echo $logo_url = $OUTPUT->get_compact_logo_url();
// $result = \local_emailtemplates\email_sender::send_email("student", 90, "Admin@123");


// $context = context_system::instance();
// $contextid = $context->id;

// // Get the role ID where the archetype is 'manager'.
// $role = $DB->get_record_sql("SELECT id FROM {role} WHERE archetype = 'manager'");

// if ($role) {
//     try {
//         // Assign the role.
//         role_assign($role->id, 104, $contextid);

//         echo "Role assigned successfully.";
//     } catch (Exception $e) {
//         echo "Failed to assign role: " . $e->getMessage();
//     }
// } else {
//     echo "Role not found.";
// }

global $DB;

// Define the email address to search for
$email = 'sv419459@gmail.com';

// Fetch the user record based on the email address
$user = $DB->get_record('user', ['email' => $email]);

if ($user) {
    // Prepare email details
    $subject = "Your Updated Moodle Password";
    $message = "Dear {$user->firstname},\n\nYour new password is: [newpassword]\n\nPlease change this password after your first login.";
    
    // Send email
    if (email_to_user($user, $USER, $subject, $message)) {
        mtrace("Email sent successfully to {$user->email}");
    } else {
        mtrace("Failed to send email to {$user->email}. Check email configuration.");
    }
} else {
    mtrace("User with email {$email} not found.");
}
