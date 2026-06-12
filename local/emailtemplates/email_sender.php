<?php
namespace local_emailtemplates;

defined('MOODLE_INTERNAL') || die();

class email_sender {
    /**
     * Sends an email using a specified template.
     * ... (comments from your original file)
     */
    public static function send_email($templateid, $userid, $password, $approvedby) {
        global $DB, $OUTPUT, $CFG;

        $template = $DB->get_record('local_emailtemplates', ['name' => $templateid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        if (!$template || !$user) {
            return false;
        }
       
        $subject = $template->subject;
        $body = $template->body; // Start with the original template body
        $messagehtml = '';
        
        $logo_url = "";
       
        // --- FIXED LOGIC: Use a clear structure to prevent overwriting the email body ---

        if ($password == "reject") {
            // Logic for a rejection email
            $userid_encoded = base64_encode($userid);
            $update_url = $CFG->wwwroot . "/login/update-student.php?token=" . $userid_encoded;
            $body = str_replace(
                ['[REJECTION_REASON]', '[FULLNAME]', '[LOGO_URL]', '[URL_UPDATE]'],
                [$approvedby, fullname($user), $logo_url, $update_url],
                $body
            );

        } else if ($password == "update") {
            // Logic for an update confirmation email
            $body = str_replace(
                ['[FULLNAME]', '[LOGO_URL]'],
                [fullname($user), $logo_url],
                $body
            );

        } else if ($password == "welcome") {
            // Logic for a simple welcome email
             $body = str_replace(
                ['[USER_ID]', '[FULLNAME]', '[LOGO_URL]'],
                [$user->username, fullname($user), $logo_url],
                $body
            );

        } else if ($password == "trainer_assigned") {
            $subject = str_replace('[SCHOOL_NAME]', $approvedby, $subject);
            $body = str_replace(
                ['[FULLNAME]', '[SCHOOL_NAME]'],
                [fullname($user), $approvedby],
                $body
            );
            $messagehtml = $body;

        } else if ($approvedby) {
            // Logic for an approval email by an admin/POC
            $body = str_replace(
                ['[USER_ID]', '[FULLNAME]', '[LOGO_URL]', '[ADMIN_OR_POC_NAME]'],
                [$user->username, fullname($user), $logo_url, $approvedby],
                $body
            );

        } else {
           
            // This is the default case for sending a new password
            $body = str_replace(
                ['[USER_ID]', '[PASSWORD]', '[FULLNAME]', '[LOGO_URL]', '[LOGIN_URL]'],
                [$user->username, $password, fullname($user), $logo_url, $CFG->wwwroot . '/login/index.php'],
                $body
            );
        }

        // Send the final, prepared email
        return email_to_user(
            $user,
            \core_user::get_noreply_user(),
            $subject,
            $messagehtml ? html_to_text($body) : $body,
            $messagehtml
        );
    }
}
