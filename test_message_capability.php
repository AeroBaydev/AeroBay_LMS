<?php
define('CLI_SCRIPT', true);
require('config.php');

$userid = 185;
$userto = core_user::get_user($userid);
$sender = core_user::get_noreply_user();

$eventdata = new \core\message\message();
$eventdata->courseid          = SITEID;
$eventdata->component         = 'moodle';
$eventdata->name              = 'notices';
$eventdata->userfrom          = $sender;
$eventdata->userto            = $userto;
$eventdata->subject           = 'Test';
$eventdata->fullmessage       = 'Test body';
$eventdata->fullmessageformat = FORMAT_HTML;
$eventdata->fullmessagehtml   = 'Test body';
$eventdata->smallmessage      = 'Test body';
$eventdata->notification      = 1;
$eventdata->contexturl        = $CFG->wwwroot . '/local/mydashboard/';
$eventdata->contexturlname    = 'View Dashboard';

echo "Sending notices to student (no capability): " . var_export(message_send($eventdata), true) . "\n";

$eventdata->name = 'newlogin'; // id 1, capability = empty
echo "Sending newlogin to student: " . var_export(message_send($eventdata), true) . "\n";
