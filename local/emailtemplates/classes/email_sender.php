<?php
namespace local_emailtemplates;

defined('MOODLE_INTERNAL') || die();

class email_sender {

    /**
     * Sends an email using a specified template.
     *
     * @param string $templateid The name of the email template.
     * @param int $userid The ID of the user to whom the email will be sent.
     * @param string $password The user's password or a keyword like 'reject', 'update', 'welcome'.
     * @param string $approvedby The name of the approver or a rejection reason.
     * @return bool True if the email was sent successfully, false otherwise.
     */
    public static function send_email($templateid, $userid, $password, $approvedby) {
        global $DB, $OUTPUT, $CFG;

        // Fetch the template and user from the database.
        $template = $DB->get_record('local_emailtemplates', ['name' => $templateid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        if (!$template || !$user) {
            return false;
        }

        $subject = $template->subject;
        $body = $template->body; // Start with the original template body.
        $logo_url = $OUTPUT->get_compact_logo_url();

        // This structure ensures only ONE block of logic runs.
        if ($password == "reject") {
            // Logic for a rejection email.
            $userid_encoded = base64_encode($userid);
            $update_url = $CFG->wwwroot . "/login/update-student.php?token=" . $userid_encoded;
            $body = str_replace(
                ['[REJECTION_REASON]', '[FULLNAME]', '[LOGO_URL]', '[URL_UPDATE]'],
                [$approvedby, fullname($user), $logo_url, $update_url],
                $body
            );

        } else if ($password == "update") {
            // Logic for an update confirmation email.
            $body = str_replace(
                ['[FULLNAME]', '[LOGO_URL]'],
                [fullname($user), $logo_url],
                $body
            );

        } else if ($password == "welcome") {
            // Logic for a simple welcome email.
             $body = str_replace(
                ['[USER_ID]', '[FULLNAME]', '[LOGO_URL]'],
                [$user->username, fullname($user), $logo_url],
                $body
            );

        } else if ($approvedby) {
            // Logic for an approval email by an admin/POC.
            $body = str_replace(
                ['[USER_ID]', '[FULLNAME]', '[LOGO_URL]', '[ADMIN_OR_POC_NAME]'],
                [$user->username, fullname($user), $logo_url, $approvedby],
                $body
            );

        } else {
            // This is the default case for sending a new password.
            $body = str_replace(
                ['[USER_ID]', '[PASSWORD]', '[FULLNAME]', '[LOGO_URL]'],
                [$user->username, $password, fullname($user), $logo_url],
                $body
            );
        }

        // Send the final, prepared email using Moodle's core function.
        return email_to_user($user, \core_user::get_noreply_user(), $subject, $body);
    }
}