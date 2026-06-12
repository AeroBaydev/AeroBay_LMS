<?php
define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_login();
require_sesskey();

header('Content-Type: application/json');

$action = required_param('action', PARAM_ALPHANUMEXT);

global $DB, $USER;
$today_midnight = usergetmidnight(time());

if ($action === 'save_progress') {
    if (!$DB->get_manager()->table_exists('local_session_progress')) {
        echo json_encode(['status' => 'error', 'message' => 'Session progress table is unavailable.']);
        die();
    }

    $ttid = optional_param('ttid', 0, PARAM_INT);
    $schoolid = optional_param('schoolid', 0, PARAM_INT);
    $gradeid = optional_param('gradeid', 0, PARAM_INT);
    $courseid = optional_param('courseid', 0, PARAM_INT);
    $sectionid = required_param('sectionid', PARAM_INT);

    if ($ttid > 0 && ($timetablerec = $DB->get_record('timetable', ['id' => $ttid], 'id, schoolid, gradeid'))) {
        $schoolid = (int) $timetablerec->schoolid;
        $gradeid = (int) $timetablerec->gradeid;
    }

    $sectionrec = $DB->get_record('course_sections', ['id' => $sectionid], 'id, course');
    if (!$sectionrec) {
        echo json_encode(['status' => 'error', 'message' => 'Selected session was not found.']);
        die();
    }

    $sectioncourseid = (int) $sectionrec->course;
    if ($courseid > 0 && $courseid !== $sectioncourseid) {
        echo json_encode(['status' => 'error', 'message' => 'Selected session does not belong to the timetable course.']);
        die();
    }
    $courseid = $sectioncourseid;

    if ($schoolid <= 0 || $gradeid <= 0 || $courseid <= 0 || $sectionid <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Missing timetable session context.']);
        die();
    }

    $lookup = [
        'schoolid' => $schoolid,
        'gradeid' => $gradeid,
        'courseid' => $courseid,
        'sectionid' => $sectionid,
    ];
    $now = time();
    $record = $DB->get_record('local_session_progress', $lookup);

    if ($record) {
        $record->completeddays = (int) $record->completeddays + 1;
        $record->timemodified = $now;
        if ($record->status === 'pending') {
            $record->status = 'inprogress';
        }
        $DB->update_record('local_session_progress', $record);
        echo json_encode([
            'status' => 'success',
            'message' => 'Progress updated.',
            'id' => (int) $record->id,
            'completeddays' => (int) $record->completeddays,
        ]);
        die();
    }

    $record = (object) $lookup;
    $record->trainerid = (int) $USER->id;
    $record->status = 'inprogress';
    $record->completeddays = 1;
    $record->timecompleted = 0;
    $record->timecreated = $now;
    $record->timemodified = $now;
    $id = $DB->insert_record('local_session_progress', $record);

    echo json_encode([
        'status' => 'success',
        'message' => 'Progress saved.',
        'id' => (int) $id,
        'completeddays' => 1,
    ]);
    die();
}

if ($action === 'mark_complete') {
    if (!$DB->get_manager()->table_exists('local_session_progress')) {
        echo json_encode(['status' => 'error', 'message' => 'Session progress table is unavailable.']);
        die();
    }

    $ttid = optional_param('ttid', 0, PARAM_INT);
    $schoolid = optional_param('schoolid', 0, PARAM_INT);
    $gradeid = optional_param('gradeid', 0, PARAM_INT);
    $courseid = optional_param('courseid', 0, PARAM_INT);
    $sectionid = required_param('sectionid', PARAM_INT);

    if ($ttid > 0 && ($timetablerec = $DB->get_record('timetable', ['id' => $ttid], 'id, schoolid, gradeid'))) {
        $schoolid = (int) $timetablerec->schoolid;
        $gradeid = (int) $timetablerec->gradeid;
    }

    $sectionrec = $DB->get_record('course_sections', ['id' => $sectionid], 'id, course');
    if (!$sectionrec) {
        echo json_encode(['status' => 'error', 'message' => 'Selected session was not found.']);
        die();
    }

    $sectioncourseid = (int) $sectionrec->course;
    if ($courseid > 0 && $courseid !== $sectioncourseid) {
        echo json_encode(['status' => 'error', 'message' => 'Selected session does not belong to the timetable course.']);
        die();
    }
    $courseid = $sectioncourseid;

    if ($schoolid <= 0 || $gradeid <= 0 || $courseid <= 0 || $sectionid <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Missing timetable session context.']);
        die();
    }

    $lookup = [
        'schoolid' => $schoolid,
        'gradeid' => $gradeid,
        'courseid' => $courseid,
        'sectionid' => $sectionid,
    ];
    $now = time();
    $record = $DB->get_record('local_session_progress', $lookup);

    if ($record) {
        $record->trainerid = (int) $USER->id;
        $record->status = 'completed';
        $record->timecompleted = $now;
        $record->timemodified = $now;
        $DB->update_record('local_session_progress', $record);
        echo json_encode([
            'status' => 'success',
            'message' => 'Session marked complete.',
            'id' => (int) $record->id,
            'completed' => true,
        ]);
        die();
    }

    $record = (object) $lookup;
    $record->trainerid = (int) $USER->id;
    $record->status = 'completed';
    $record->completeddays = 1;
    $record->timecompleted = $now;
    $record->timecreated = $now;
    $record->timemodified = $now;
    $id = $DB->insert_record('local_session_progress', $record);

    echo json_encode([
        'status' => 'success',
        'message' => 'Session marked complete.',
        'id' => (int) $id,
        'completed' => true,
    ]);
    die();
}

if ($action === 'toggle_complete') {
    $ttid = required_param('ttid', PARAM_INT);
    $schoolid = required_param('schoolid', PARAM_INT);
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

echo json_encode(['status' => 'error', 'message' => 'Unknown timetable action.']);
