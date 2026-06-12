<?php
define('CLI_SCRIPT', true);
require('config.php');

$userid = 155; // Student Puneet Sharma
$userto = core_user::get_user($userid);
$sender = core_user::get_noreply_user();

$eventdata = new \core\message\message();
$eventdata->courseid          = SITEID;
$eventdata->component         = 'moodle';
$eventdata->name              = 'coursecontentupdated';
$eventdata->userfrom          = $sender;
$eventdata->userto            = $userto;
$eventdata->subject           = 'New Announcement Test';
$eventdata->fullmessage       = 'Test body';
$eventdata->fullmessageformat = FORMAT_HTML;
$eventdata->fullmessagehtml   = 'Test body';
$eventdata->smallmessage      = 'Test body preview';
$eventdata->notification      = 1;
$eventdata->contexturl        = $CFG->wwwroot . '/mydashboard/index.php';
$eventdata->contexturlname    = 'View Dashboard';

$msgid = message_send($eventdata);
echo "Msg ID returned: " . var_export($msgid, true) . "\n";

if ($msgid) {
    global $DB;
    print_r($DB->get_record('notifications', ['id' => $msgid]));
}
