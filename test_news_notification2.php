<?php
define('CLI_SCRIPT', true);
require('config.php');
require_once($CFG->dirroot . '/local/news/lib.php');

global $DB;

$news = new stdClass();
$news->schoolid = '250'; 
$news->gradeid = '287'; 

$schoolids = array_filter(array_map('intval', explode(',', $news->schoolid ?? '')));
$gradeids = array_filter(array_map('intval', explode(',', $news->gradeid ?? '')));

list($schoolsql, $schoolparams) = $DB->get_in_or_equal($schoolids, SQL_PARAMS_NAMED, 'sch');
list($gradesql, $gradeparams) = $DB->get_in_or_equal($gradeids, SQL_PARAMS_NAMED, 'grd');

$params = array_merge($schoolparams, $gradeparams);

$sql = "SELECT s.userid 
          FROM {student} s
          JOIN {user} u ON u.id = s.userid
         WHERE s.schoolid $schoolsql 
           AND s.gradeid $gradesql
           AND u.deleted = 0
           AND u.suspended = 0";

$studentids = $DB->get_fieldset_sql($sql, $params);
print_r($studentids);

// also let's send a direct notice to an array of $studentids
if (!empty($studentids)) {
    foreach($studentids as $userid) {
        $eventdata = new \core\message\message();
        $eventdata->courseid          = SITEID;
        $eventdata->component         = 'moodle';
        $eventdata->name              = 'notices';
        $eventdata->userfrom          = core_user::get_noreply_user();
        $eventdata->userto            = $userid;
        $eventdata->subject           = 'New Announcement Test';
        $eventdata->fullmessage       = 'Test body';
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml   = 'Test body';
        $eventdata->smallmessage      = 'Test body';
        $eventdata->notification      = 1;
        $eventdata->contexturl        = $CFG->wwwroot . '/local/mydashboard/';
        $eventdata->contexturlname    = 'View Dashboard';
        
        $msgid = message_send($eventdata);
        echo "Sent to $userid, msgid: " . var_export($msgid, true) . "\n";
    }
}
