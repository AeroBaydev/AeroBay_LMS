<?php

namespace local_emailtemplates;
// require_once('../../config.php');
defined('MOODLE_INTERNAL') || die();

class email_sender {

    /**
     * Sends an email using a specified template.
     *
     * @param int|string $templateid The ID or name of the email template.
     * @param int $userid The ID of the user to whom the email will be sent.
     * @param string $password The user's password (or any other placeholder value).
     * @return bool True if the email was sent successfully, false otherwise.
     */
    public static function send_email($templateid, $userid, $password,$approvedby) {
        global $DB,$OUTPUT,$CFG;

        // Fetch the email template and user record from the database
        // Use 'name' or 'id' depending on what $templateid represents
        $template = $DB->get_record('local_emailtemplates', ['name' => $templateid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        // If either the template or the user doesn't exist, return false
        if (!$template || !$user) {
            return false;
        }

        // Replace placeholders in the template body with actual user data
        $subject = $template->subject;
        if ($approvedby) {
            $logo_url =$OUTPUT->get_compact_logo_url();
            $body = str_replace(
                ['[USER_ID]', '[FULLNAME]', '[LOGO_URL]', '[ADMIN_OR_POC_NAME]'],
                [$user->username, $user->firstname . " " . $user->lastname, $logo_url, $approvedby],
                $template->body
            );
        }
        
        else{
            $logo_url =$OUTPUT->get_compact_logo_url();
            $body = str_replace(
                ['[USER_ID]', '[PASSWORD]', '[FULLNAME]','[LOGO_URL]'],
                [$user->username, $password, $user->firstname . " " . $user->lastname,$logo_url],
                $template->body
            );
    


        }

        
        if($password=="reject"){
            $_SESSION['token']="";
            $userid=base64_encode($userid);
            $update = $CFG->wwwroot."/login/update-student.php?token=$userid";
            //  $update_url=  $OUTPUT->single_button($update, get_string('continue'));
            $logo_url =$OUTPUT->get_compact_logo_url();
            $body = str_replace(
                ['[REJECTION_REASON]', '[FULLNAME]','[LOGO_URL]','[URL_UPDATE]'],
                [$approvedby, $user->firstname . " " . $user->lastname,$logo_url,$update],
                $template->body
            );
    

        }


        if($password=="update"){
            $logo_url =$OUTPUT->get_compact_logo_url();
            $body = str_replace(
                [ '[FULLNAME]','[LOGO_URL]'],
                [ $user->firstname . " " . $user->lastname,$logo_url],
                $template->body
            );
        }
            if($password=="welcome"){
                $logo_url =$OUTPUT->get_compact_logo_url();
                $body = str_replace(
                    [ '[USER_ID]','[FULLNAME]','[LOGO_URL]'],
                    [$user->username, $user->firstname . " " . $user->lastname,$logo_url],
                    $template->body
                );


        }




        // Send the email
        $result = email_to_user($user, \core_user::get_noreply_user(), $subject, $body);

        return $result;
    }
}
