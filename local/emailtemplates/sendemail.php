<?php
require_once('../../config.php');

// $template_id = optional_param('templateid', 0, PARAM_INT);
// $user_id = optional_param('userid', 0, PARAM_INT);

require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/emailtemplates/send_email.php', ['templateid' => $template_id, 'userid' => $user_id]));
$PAGE->set_pagelayout('admin');

$template = $DB->get_record('local_emailtemplates', ['id' => 1], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => 2], '*', MUST_EXIST);

if (!$template || !$user) {
    print_error('Invalid template or user ID');
}

$subject = $template->subject;
$body = str_replace(
    ['[USER_ID]', '[PASSWORD]'],
    [$user->username, $user->email],
    $template->body
);

$email_to = $user->email;
$email_from = 'admin@example.com';

$result = email_to_user($user, core_user::get_noreply_user(), $subject, $body);

if ($result) {
   // echo "Email sent successfully!";
} else {
  //  echo "Failed to send email.";
}
