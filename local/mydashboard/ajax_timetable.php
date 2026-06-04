<?php
define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_login();
require_sesskey();

$action = required_param('action', PARAM_ALPHANUMEXT);
$ttid   = required_param('ttid', PARAM_INT);
$schoolid = required_param('schoolid', PARAM_INT);

global $DB, $USER;
$today_midnight = usergetmidnight(time());

if ($action === 'toggle_complete') {
    $dedupe = 'tt_done_' . $ttid . '_' . $today_midnight;
    $existing = $DB->get_record('local_dashboard_activity_logs', ['activitytype' => 'tt_completed', 'dedupekey' => $dedupe]);
    
    if ($existing) {
        $DB->delete_records('local_dashboard_activity_logs', ['id' => $existing->id]);
        echo json_encode(['status' => 'success', 'completed' => false]);
    } else {
        $log = new stdClass();
        $log->activitytype = 'tt_completed';
        $log->title = 'Timetable Completed';
        $log->schoolid = $schoolid;
        $log->actorid = $USER->id;
        $log->dedupekey = $dedupe;
        $log->timecreated = time();
        $DB->insert_record('local_dashboard_activity_logs', $log);
        echo json_encode(['status' => 'success', 'completed' => true]);
    }
    die();
}
