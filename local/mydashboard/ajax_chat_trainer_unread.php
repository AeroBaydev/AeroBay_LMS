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

$unread = 0;
$trainerchats = $DB->get_records('local_mydashboard_chat', [
    'trainerid' => (int) $USER->id,
    'status' => 'active',
], '', 'id');

foreach ($trainerchats as $trainerchat) {
    $unread += local_mydashboard_get_unread_count((int) $trainerchat->id, (int) $USER->id);
}

$response([
    'success' => true,
    'unread' => $unread,
]);
