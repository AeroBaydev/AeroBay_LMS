<?php

define('AJAX_SCRIPT', true);
require_once('../../config.php');

require_login();
require_sesskey();

global $DB, $USER;

$trainerid = required_param('trainerid', PARAM_INT);
$subject = optional_param('subject', '', PARAM_TEXT);
$question = required_param('question', PARAM_TEXT);

$response = function(array $payload): void {
    header('Content-Type: application/json');
    echo json_encode($payload);
    die();
};

$question = trim($question);
$subject = trim($subject);

if ($question === '') {
    $response(['success' => false, 'error' => 'Please enter your doubt or question.']);
}
if (core_text::strlen($question) > 1000) {
    $response(['success' => false, 'error' => 'Doubt text cannot exceed 1000 characters.']);
}

if (!$DB->get_manager()->table_exists('local_mydashboard_doubt')) {
    $response(['success' => false, 'error' => 'Doubt submission is not available yet.']);
}

$student = $DB->get_record('student', ['userid' => $USER->id], '*', IGNORE_MISSING);
if (!$student) {
    $response(['success' => false, 'error' => 'Only students can submit doubts from this dashboard.']);
}

$courseid = (int) ($student->courseid ?? 0);
$coursecontext = $courseid > 0 ? context_course::instance($courseid, IGNORE_MISSING) : false;
$hasdashboardaccess = $DB->record_exists('student', ['userid' => $USER->id]);
if ($coursecontext) {
    $hasdashboardaccess = has_capability('local/mydashboard:submitdoubt', $coursecontext) ||
        is_enrolled($coursecontext, $USER, '', true) ||
        $hasdashboardaccess;
}
if (!$hasdashboardaccess) {
    $response(['success' => false, 'error' => 'You do not have permission to submit doubts.']);
}

$trainer = $DB->get_record('trainer', ['userid' => $trainerid], 'id, userid, schoolid', IGNORE_MISSING);
if (!$trainer) {
    $response(['success' => false, 'error' => 'Trainer was not found.']);
}

$schoolid = (int) ($student->schoolid ?? 0);
$gradeid = (int) ($student->gradeid ?? 0);
$trainerallowed = $schoolid > 0 && (int) $trainer->schoolid === $schoolid;
if (!$trainerallowed) {
    $response(['success' => false, 'error' => 'Selected trainer is not assigned to your class.']);
}

$now = time();
$record = (object) [
    'studentid' => (int) $USER->id,
    'trainerid' => $trainerid,
    'subject' => $subject,
    'question' => $question,
    'status' => 'open',
    'timecreated' => $now,
    'timemodified' => $now,
];

$id = $DB->insert_record('local_mydashboard_doubt', $record);
$response([
    'success' => true,
    'id' => (int) $id,
    'message' => 'Your doubt has been sent to your trainer.',
]);
