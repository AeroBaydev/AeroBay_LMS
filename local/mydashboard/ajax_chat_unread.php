<?php

define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once($CFG->dirroot . '/local/mydashboard/lib.php');

require_login();
require_sesskey();

global $DB, $USER;

$response = function(array $payload): void {
    header('Content-Type: application/json');
    echo json_encode($payload);
    die();
};

if (!$DB->get_manager()->table_exists('local_mydashboard_chat')) {
    $response(['success' => true, 'unread' => 0]);
}

$trainer = local_mydashboard_get_student_assigned_trainer((int) $USER->id);
if (!$trainer) {
    $response(['success' => true, 'unread' => 0]);
}

$chat = $DB->get_record('local_mydashboard_chat', [
    'studentid' => (int) $USER->id,
    'trainerid' => (int) $trainer->userid,
    'status' => 'active',
], 'id', IGNORE_MISSING);

$unread = $chat ? local_mydashboard_get_unread_count((int) $chat->id, (int) $USER->id) : 0;

$response([
    'success' => true,
    'unread' => $unread,
]);
