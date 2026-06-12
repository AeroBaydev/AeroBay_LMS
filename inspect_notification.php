<?php
define('CLI_SCRIPT', true);
require('config.php');
global $DB;

$admin = core_user::get_user(2); // Assuming ID 2 is admin
if (!$admin) {
    echo "Admin user not found.\n";
    exit;
}

$eventdata = new \core\message\message();
$eventdata->courseid          = SITEID;
$eventdata->component         = 'moodle';
$eventdata->name              = 'notices'; // Changed to notices
$eventdata->userfrom          = core_user::get_noreply_user();
$eventdata->userto            = $admin;
$eventdata->subject           = 'Test Announcement';
$eventdata->fullmessage       = 'Test Announcement Message';
$eventdata->fullmessageformat = FORMAT_HTML;
$eventdata->fullmessagehtml   = 'Test Announcement Message';
$eventdata->smallmessage      = 'Test Announcement Message';
$eventdata->notification      = 1;
$eventdata->contexturl        = $CFG->wwwroot . '/local/mydashboard/';
$eventdata->contexturlname    = 'View Dashboard';

echo "Sending message...\n";
$msgid = message_send($eventdata);
echo "Message ID returned: " . var_export($msgid, true) . "\n";

if ($msgid) {
    echo "Checking notifications table:\n";
    print_r($DB->get_record('notifications', ['id' => $msgid]));
}
