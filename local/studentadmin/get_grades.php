<?php
require_once('../../config.php');

// Ensure the user is logged in and has the necessary permissions
// require_login();
// $context = context_system::instance();
// require_capability('moodle/site:config', $context);

// Get the 'schoolid' parameter from the request
$schoolid = required_param('schoolid', PARAM_INT);

// Initialize response array
$response = array();

// Check if a valid school ID is provided
if ($schoolid) {
    global $DB;
    
    // Query to fetch grades associated with the selected school
    $grades = $DB->get_records_sql_menu(
        "SELECT cc.id, cc.name
         FROM {course_categories} cc
         WHERE cc.parent = :schoolid",
        array('schoolid' => $schoolid)
    );

    // Prepare the response
    foreach ($grades as $id => $name) {
        $response[] = array('id' => $id, 'name' => $name);
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
