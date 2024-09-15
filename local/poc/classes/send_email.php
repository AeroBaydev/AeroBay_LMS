<?php
namespace local_poc;

use core\event\base;
use core\event\user_created;

class send_email extends \core\event\user_created {

    public static function user_created(user_created $event) {
        global $CFG;

        $user = $event->get_record_snapshot('user', $event->objectid);
        session_start();
        $password = $_SESSION['password'];
        $subject = 'Welcome to our Aerospace site!';
        $message = "Hello {$user->firstname},\n\nWelcome to our Aerospace site!\n\n Your email-id {$user->email} and your password is {$password}\n\nBest regards,\nAerospace Team";

        email_to_user($user, get_admin(), $subject, $message);
    }
}
?>
