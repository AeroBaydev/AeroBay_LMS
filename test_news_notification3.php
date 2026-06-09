<?php
define('CLI_SCRIPT', true);
require('config.php');
require_once($CFG->dirroot . '/local/news/lib.php');

global $DB;

$eventdata = new \core\message\message();
$eventdata->courseid          = SITEID;
$eventdata->component         = 'moodle';
$eventdata->name              = 'notices';
$eventdata->userfrom          = core_user::get_noreply_user();

$userto = new stdClass();
$userto->id = 2; // admin
$eventdata->userto            = $userto;
$eventdata->subject           = 'New Announcement Test';
$eventdata->fullmessage       = 'Test body';
$eventdata->fullmessageformat = FORMAT_HTML;
$eventdata->fullmessagehtml   = 'Test body';
$eventdata->smallmessage      = 'Test body';
$eventdata->notification      = 1;
$eventdata->contexturl        = $CFG->wwwroot . '/local/mydashboard/';
$eventdata->contexturlname    = 'View Dashboard';

$msgid = message_send($eventdata);
echo "Sent with stdClass(id=2), msgid: " . var_export($msgid, true) . "\n";

$eventdata->userto = core_user::get_user(2);
$msgid = message_send($eventdata);
echo "Sent with get_user(2), msgid: " . var_export($msgid, true) . "\n";
