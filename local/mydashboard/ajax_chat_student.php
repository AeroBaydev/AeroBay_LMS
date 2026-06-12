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
    $response(['success' => false, 'error' => 'Chat is not available yet.']);
}

$trainer = local_mydashboard_get_student_assigned_trainer((int) $USER->id);
if (!$trainer) {
    $response(['success' => false, 'error' => 'No trainer is currently assigned to you.']);
}

$chat = local_mydashboard_get_or_create_chat(
    (int) $USER->id,
    (int) $trainer->userid,
    (int) $trainer->schoolid
);
$isonline = !empty($trainer->lastaccess) && time() - (int) $trainer->lastaccess <= 3600;

$response([
    'success' => true,
    'chat' => [
        'id' => (int) $chat->id,
        'trainername' => fullname($trainer),
        'isonline' => $isonline,
        'status' => $isonline ? 'Online' : 'Offline',
    ],
]);
