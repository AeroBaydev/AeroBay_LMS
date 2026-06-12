<?php
define('CLI_SCRIPT', true);
require('config.php');
require_once($CFG->dirroot . '/local/news/lib.php');

$userid = 185;
$userto = core_user::get_user($userid);
$sender = core_user::get_noreply_user();

$message_html = '<strong>New Announcement Test</strong>';

$eventdata = new \core\message\message();
$eventdata->courseid          = SITEID;
$eventdata->component         = 'moodle';
$eventdata->name              = 'notices';
$eventdata->userfrom          = $sender;
$eventdata->userto            = $userto;
$eventdata->subject           = 'New Announcement';
$eventdata->fullmessage       = html_to_text($message_html);
$eventdata->fullmessageformat = FORMAT_HTML;
$eventdata->fullmessagehtml   = $message_html;
$eventdata->smallmessage      = 'Test preview';
$eventdata->notification      = 1;
    $eventdata->contexturl        = $CFG->wwwroot . '/mydashboard/index.php';
$eventdata->contexturlname    = 'View Dashboard';

$msgid = message_send($eventdata);
echo "Msg ID returned: " . var_export($msgid, true) . "\n";
