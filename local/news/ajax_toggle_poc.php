<?php
require_once("../../config.php");
require_login(); // Uncomment if the user must be logged in

header('Content-Type: application/json');

global $DB;

// Get request parameters
$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);

// Validate input
if ($id <= 0 || !in_array($action, ['Hide', 'Activate'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Fetch record from the 'news' table
    $record = $DB->get_record('news', ['id' => $id], '*', MUST_EXIST);
    $record->id =$id;
    // Set suspension status based on action
    if ($action === 'Hide') {
        $record->status = 0;
    } else if ($action === 'Activate') {
        $record->status = 1;
    }
//     echo $action;
// print_r($record);
// die;
    // Update record
    $DB->update_record('news', $record);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
