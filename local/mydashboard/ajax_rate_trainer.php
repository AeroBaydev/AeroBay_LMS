<?php
define('AJAX_SCRIPT', true);
require_once('../../config.php');

require_login();
require_sesskey();

$trainerid = required_param('trainerid', PARAM_INT);
$schoolid = required_param('schoolid', PARAM_INT);
$gradeid = required_param('gradeid', PARAM_INT);
$rating = required_param('rating', PARAM_INT);
$feedback = optional_param('feedback', '', PARAM_TEXT);

if ($rating < 1 || $rating > 5) {
    echo json_encode(['error' => 'Invalid rating value']);
    die;
}

global $DB, $USER;
$studentid = $USER->id;

// Check if rating already exists
$existing = $DB->get_record('local_trainer_rating', ['studentid' => $studentid, 'trainerid' => $trainerid]);

$record = new stdClass();
$record->studentid = $studentid;
$record->trainerid = $trainerid;
$record->schoolid = $schoolid;
$record->gradeid = $gradeid;
$record->rating = $rating;
$record->feedback = $feedback;
$record->timemodified = time();

if ($existing) {
    $record->id = $existing->id;
    $DB->update_record('local_trainer_rating', $record);
} else {
    $record->timecreated = time();
    $DB->insert_record('local_trainer_rating', $record);
}

// Calculate new average
$sql = "SELECT AVG(rating) AS avgrating, COUNT(rating) AS countrating FROM {local_trainer_rating} WHERE trainerid = :trainerid";
$stats = $DB->get_record_sql($sql, ['trainerid' => $trainerid]);

echo json_encode([
    'success' => true,
    'avgrating' => round($stats->avgrating, 1),
    'countrating' => $stats->countrating
]);
