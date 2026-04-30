<?php
namespace local_students;

use core\event\base;
use core\event\user_created;

class send_email {

    public static function user_created(user_created $event) {
        global $CFG, $DB;
        
        // Get user data from the event object
        $user = $event->get_record_snapshot('user', $event->objectid);
        
        // Start the session to retrieve the password
        session_start();
        $password = $_SESSION['password'];

        // Email to the user
        $subject_user = 'Welcome to our Aerospace site!';
        $message_user = "Hello {$user->firstname},\n\nWelcome to our Aerospace site!\n\nYour email-id is {$user->email} and your password is {$password}\n\nBest regards,\nAerospace Team";
        email_to_user($user, get_admin(), $subject_user, $message_user);
        
        // Email to the admin
        $admin = get_admin();
        $subject_admin = 'New User Created';
        $message_admin = "A new user has been created:\n\nName: {$user->firstname} {$user->lastname}\nEmail: {$user->email}\n\nBest regards,\nAerospace Team";
        email_to_user($admin, $admin, $subject_admin, $message_admin);
    }
}
?>
