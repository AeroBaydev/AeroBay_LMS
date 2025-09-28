<?php

require_once "../../config.php";
require_once($CFG->dirroot . '/lib/enrollib.php'); // Enrolment library is needed

global $DB, $USER;

// Set header to return JSON
header('Content-Type: application/json');

// --- 1. Get and Validate Input Parameters ---
$categoryid = required_param('CatId', PARAM_INT);    // Grade id
$schoolid = required_param('schoolid', PARAM_INT); // Parent categoryid
$courseid = required_param('option', PARAM_INT);   // The course ID to enroll into

$response = [];

// --- 2. Identify the User Performing the Action ---
if (isset($_SESSION['userIdPoc'])) {
    $userid = $_SESSION['userIdPoc'];
} else {
    $userid = $USER->id;
}

// Ensure the user and course are valid before proceeding
$user_to_enrol = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
$course_to_enrol_in = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

// --- 3. Check for Existing Entries in the Current Session ---
$poc_session_date = $DB->get_record('poc_session_date', ['pocid' => $userid, 'status' => 1]);

if (empty($poc_session_date)) {
    // If there is no active session, we cannot proceed.
    $response = [
        'status' => 'error',
        'message' => 'No active session was found for you.'
    ];
    echo json_encode($response);
    exit();
}

$already_exists = $DB->record_exists('poc_copy_course', [
    'pocid'     => $userid,
    'status'    => 1,
    'gradeid'   => $categoryid,
    'courseid'  => $courseid, // Check against the same courseid
    'sessionid' => $poc_session_date->id
]);

if ($already_exists) {
    // This action has already been performed for this user in this session.
    $response = [
        'status'  => 'error',
        'message' => 'You have already performed this action for this course in the current session.'
    ];
    echo json_encode($response);
    exit();
}

// --- 4. Perform the Actions (No Copying) ---
try {
    // --- Step 1: Log the action in your custom table ---
    $record = new stdClass();
    $record->schoolid  = $schoolid;
    $record->gradeid   = $categoryid;
    $record->courseid  = $courseid; // Log the course ID
    $record->sessionid = $poc_session_date->id;
    $record->pocid     = $userid;
    $record->status    = 1;
    $DB->insert_record('poc_copy_course', $record);

    // --- Step 2: Enrol the User as Teacher and POC ---
    // Get role objects for teacher and your custom 'poc' role
    $teacher_role = $DB->get_record('role', ['shortname' => 'editingteacher']);
    $poc_role     = $DB->get_record('role', ['shortname' => 'poc']); // Make sure this shortname is correct

    // if (!$teacher_role || !$poc_role) {
    //     throw new Exception("The Teacher or POC role does not exist in the system.");
    // }

    // Find the manual enrolment instance in the specified course.
    $enrol_instance = $DB->get_record('enrol', [
        'courseid' => $courseid,
        'enrol'    => 'manual',
        'status'   => 0 // 0 means the enrolment method is enabled
    ]);

    if ($enrol_instance) {
        $enrol_plugin = enrol_get_plugin('manual');

        // Enrol user with Teacher role
        $enrol_plugin->enrol_user($enrol_instance, $userid, $teacher_role->id);

        // Enrol user with POC role
        $enrol_plugin->enrol_user($enrol_instance, $userid, $poc_role->id);
    } else {
        // Optional: Handle case where manual enrolment is not enabled on the course
        throw new Exception("Manual enrolment is not enabled for this course.");
    }

    // --- Step 3: Send Final Success Response ---
    $response = [
        'status'  => 'success',
        'message' => 'Action recorded and you have been mapping  in this course successfully.'
    ];
    echo json_encode($response);
    exit();

} catch (Exception $e) {
    // Catch any errors during the process.
    $response = [
        'status'  => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ];
    echo json_encode($response);
    exit();
}