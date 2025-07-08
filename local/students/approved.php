<?php
require_once('../../config.php');  // Include Moodle configuration

// Check if the user has the appropriate capability
require_login();
//require_capability('local/studentadmin:approve', context_system::instance());

// Get the incoming data from the AJAX request
$ids = required_param('ids', PARAM_RAW);

// Decode the JSON data
$student_ids = json_decode($ids, true);

// Check if the student_ids array is not empty
if (empty($student_ids)) {
    echo json_encode(array('status' => 'error', 'message' => 'No students selected.'));
    exit;
}

global $DB;

// Prepare SQL to update the status for the selected students
$sql = "UPDATE {student} SET status = 1 WHERE userid IN (" . implode(',', array_map('intval', $student_ids)) . ")";

// Execute the SQL
$DB->execute($sql);

// Return a success message
echo json_encode(array('status' => 'success', 'message' => 'Selected students approved successfully.'));
exit;
?>
