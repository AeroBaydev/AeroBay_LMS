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

// FIRST: Update the database status for the selected students
$sql = "UPDATE {student} SET status = 1 WHERE userid IN (" . implode(',', array_map('intval', $student_ids)) . ")";

// Execute the SQL
$DB->execute($sql);

// THEN: Trigger an event for each student AFTER database update
foreach ($student_ids as $userid) {
    $userid = (int)$userid;
    if ($userid > 0) {
        // This is the most important part. It creates and triggers the event.
        \local_studentapproval\event\user_approved::create([
            'context'  => context_system::instance(),
            'objectid' => $userid, // The ID of the user being approved.
        ])->trigger();
    }
}

// Return a success message
echo json_encode(array('status' => 'success', 'message' => 'Selected students approved successfully.'));
exit;
?>
