<?php
namespace local_send_email_id_and_password\task;

require_once(__DIR__ . '/../../../../config.php'); // Adjust the path to config.php as needed.
require_once($CFG->dirroot . '/local/emailtemplates/email_sender.php');
require_once($CFG->libdir . '/moodlelib.php');

use context_course;
class send_email_task extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('sendemailtask', 'local_send_email_id_and_password');
    }

    public function execute() {
        global $DB;
        mtrace("Executing send_email_task...");

        // Example user IDs to send emails to
        //$userids = [124]; // Replace with your own logic to fetch user IDs
       // Fetch records where email_status is not 0
       $sql = "SELECT * FROM {student} WHERE status = :status AND email_status =:status1";

       // Define parameters with unique keys for each placeholder
       $params = ['status' => 1, 'status1' => 0];
       
       // Execute the SQL query
       $userids = $DB->get_records_sql($sql, $params);
//  print_r($userids);
//  die;
        foreach ($userids as $userid) {
            // Fetch user details
            $user = $DB->get_record('user', ['id' => $userid->userid,'deleted'=>0]);

            if ($user) {
                // Generate a random password
                $newpassword = $this->generate_random_password();
                // $subject = "Your Updated Moodle Password";
              
                // Update the user's password
                if (update_internal_user_password($user, $newpassword)) {
                    // Prepare and send the email
                    $subject = "Your Updated Moodle Password";
                    $message = "Dear {$user->firstname},\n\nYour new password is: {$newpassword}\n\nYour username is: {$user->username}\n\nPlease change this password after your first login.";
                    
                    // Send email
                  //  email_to_user($user, \core_user::get_noreply_user(), $subject, $message);
                 $result= \local_emailtemplates\email_sender::send_email("student",$userid->userid, $newpassword,0);
                  
                 if($result){
                    $DB->set_field('student', 'email_status', 1, array('userid' => $userid->userid));

                    $student = $DB->get_record('student', array('userid' => $userid->userid));
                   
                    $poc_Course = $DB->get_record('poc_copy_course', array('schoolid' => $student->schoolid,'gradeid' => $student->gradeid,'status'=>1));
                   
                    $courseid = (int)$poc_Course->courseid; 
                    $context = context_course::instance($courseid);
                    // print_r($context);
                    $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
                    if (!is_enrolled($context,$userid->userid)) {
                        // Not already enrolled so try enrolling them.
                        if (!enrol_try_internal_enrol($courseid, $userid->userid, $studentroleid, time())) {
                            // There's a problem.
                            throw new moodle_exception('unabletoenrolerrormessage', 'langsourcefile');
                        }
                        
                    }


                 }
                 else{
                    $DB->set_field('student', 'email_status', 2, array('userid' => $userid->userid));
                 }
                }
            }
        }
    }

    /**
     * Generates a random password.
     * @return string
     */
    public function generate_random_password() {
        // Define a pool of characters to use for the password
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        return substr(str_shuffle($chars), 0, 10); // Generate a 10-character password
    }
}
