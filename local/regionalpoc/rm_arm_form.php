<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once('classes/form/regionalpoc_form.php');
require_once($CFG->dirroot . '/local/regionalpoc/lib.php');
require_once('../../lib/moodlelib.php');


global $PAGE, $CFG,$DB,$USER;

require_login();
local_regionalpoc_require_regional_manager();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title('Assistant Regional Manager Registration');
$PAGE->navbar->add('Assistant Regional Manager Management', "$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?usertype=arm");
$PAGE->navbar->add('Add Assistant Regional Manager', "$CFG->wwwroot/local/regionalpoc/rm_arm_form.php");
// $PAGE->set_heading('Regionalpoc Registration Form');

$schooloptions = local_regionalpoc_get_assignable_school_options((int) $USER->id);
$mform = new regionalpoc_form(null, ['schooloptions' => $schooloptions]);

if ($mform->is_cancelled()) {
    redirect("$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?usertype=arm");
} elseif ($data = $mform->get_data()) {
    local_regionalpoc_arm_email_debug_log('form_submitted', [
        'username' => $data->username ?? '',
        'recipient_email' => $data->email ?? '',
        'password_present' => !empty($data->password),
        'password_length' => isset($data->password) ? strlen($data->password) : 0,
        'selected_school_count' => is_array($data->schoolids ?? null) ? count($data->schoolids) : 0,
    ]);

    $regionalpoc = new stdClass();
    $regionalpoc->username = $data->username;
    $regionalpoc->firstname = $data->firstname;
    $regionalpoc->lastname = $data->lastname;
    $regionalpoc->password = $data->password;
    $regionalpoc->dob = $data->dob;
    $regionalpoc->mnethostid = 1;
    $regionalpoc->confirmed = 1;
    $regionalpoc->blood_group = $data->blood_group;
    $regionalpoc->email = $data->email;
    $regionalpoc->contact_number = $data->contact_number;
    $regionalpoc->permanent_address = $data->permanent_address;
    $regionalpoc->current_address = $data->current_address;
    $regionalpoc->alternative_address = $data->alternative_address;
    $regionalpoc->experience = $data->experience;
    $regionalpoc->ctc = $data->ctc;
    // $regionalpoc->roleid = $data->role;
    $regionalpoc->date_of_joining = $data->date_of_joining;
    $regionalpoc->designation = $data->designation;
    $regionalpoc->pocid = $USER->id;
    $regionalpoc->usertype = 'asstmanager';
        
  
    // var_dump($regionalpoc);die;

     $user_id = user_create_user($regionalpoc);
    if ($user_id !== false) {
        local_regionalpoc_arm_email_debug_log('user_created', [
            'userid' => (int) $user_id,
            'username' => $regionalpoc->username,
            'recipient_email' => $regionalpoc->email,
            'email_function_called' => false,
            'note' => 'Email is sent later after role and school assignments complete.',
        ]);

        $context = context_system::instance();
        $role = $DB->get_record('role', ['shortname' => 'arm']);
        if (!$role) {
            print_error('missingrole', 'error', '', 'arm');
        }

        local_regionalpoc_sync_arm_role_capabilities();
        role_assign($role->id, $user_id, $context->id);

        $regionalpoc->userid = $user_id;
        $regionalpoc->roleid = $role->id;
        $regionalpoc->contextid = $context->id;
        $DB->insert_record('regionalpoc', $regionalpoc);
        local_regionalpoc_save_arm_school_assignments($user_id, $data->schoolids, (int) $USER->id);

        $template = $DB->get_record('local_emailtemplates', ['name' => 'Arm'], 'id,name,subject');
        local_regionalpoc_arm_email_debug_log('email_template_lookup', [
            'userid' => (int) $user_id,
            'recipient_email' => $regionalpoc->email,
            'template_name' => 'Arm',
            'template_resolved' => !empty($template),
            'template_id' => $template ? (int) $template->id : 0,
            'template_subject' => $template ? $template->subject : '',
        ]);

        $emailsent = false;
        $emailerror = '';
        local_regionalpoc_arm_email_debug_log('email_send_attempt', [
            'userid' => (int) $user_id,
            'username' => $regionalpoc->username,
            'recipient_email' => $regionalpoc->email,
            'template_name' => 'Arm',
            'login_url' => $CFG->wwwroot . '/login/index.php',
            'smtp_host_configured' => !empty($CFG->smtphosts),
            'smtp_secure' => $CFG->smtpsecure ?? '',
            'smtp_auth_type' => $CFG->smtpauthtype ?? '',
            'smtp_user_configured' => !empty($CFG->smtpuser),
            'noreplyaddress' => $CFG->noreplyaddress ?? '',
        ]);

        try {
            if ($template) {
                $emailsent = \local_emailtemplates\email_sender::send_email('Arm', $user_id, $data->password, 0);
            } else {
                $emailerror = 'Arm email template not found.';
            }
        } catch (Throwable $exception) {
            $emailerror = $exception->getMessage();
            local_regionalpoc_arm_email_debug_log('email_send_exception', [
                'userid' => (int) $user_id,
                'recipient_email' => $regionalpoc->email,
                'exception_class' => get_class($exception),
                'exception_message' => $emailerror,
            ]);
        }

        local_regionalpoc_arm_email_debug_log('email_send_result', [
            'userid' => (int) $user_id,
            'recipient_email' => $regionalpoc->email,
            'template_name' => 'Arm',
            'email_send_attempted' => !empty($template),
            'email_send_success' => !empty($emailsent),
            'smtp_error_or_exception' => $emailerror,
            'smtp_result_note' => $emailsent ? 'email_to_user returned true.' :
                'email_to_user returned false or was not called; check Moodle/PHP SMTP logs for PHPMailer transport details.',
        ]);

        local_regionalpoc_arm_email_debug_log('post_create_complete', [
            'userid' => (int) $user_id,
            'recipient_email' => $regionalpoc->email,
            'role_assigned' => 'arm',
            'school_assignments_saved' => is_array($data->schoolids ?? null) ? count($data->schoolids) : 0,
            'email_send_attempted' => !empty($template),
            'email_send_success' => !empty($emailsent),
        ]);

        $usertype = 'arm';
        redirect("$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?usertype=$usertype", get_string('regionalpocsuccess', 'local_regionalpoc'), 2);
    } else {
        local_regionalpoc_arm_email_debug_log('user_create_failed', [
            'username' => $regionalpoc->username,
            'recipient_email' => $regionalpoc->email,
            'email_send_attempted' => false,
        ]);
        print_error('usercreationerror', 'local_regionalpoc');
    }
} else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
