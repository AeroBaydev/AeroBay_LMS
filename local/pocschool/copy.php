<?php

require_once "../../config.php";

global $DB, $USER;

header('Content-Type: application/json');

// 1. Get and validate required parameters.
$categoryid = required_param('CatId', PARAM_INT);
$schoolid   = required_param('schoolid', PARAM_INT);
$courseid   = required_param('option', PARAM_INT);

// 2. Identify the user.
$userid = $USER->id;
if (isset($_SESSION['userIdPoc'])) {
    $userid = $_SESSION['userIdPoc'];
}

// 3. Check for an active session.
$poc_session_date = $DB->get_record('poc_session_date', ['pocid' => $userid, 'status' => 1]);
if (empty($poc_session_date)) {
    echo json_encode(['status' => 'error', 'message' => 'No active session was found for you.']);
    exit();
}

// 4. Prevent duplicate entries.
$conditions = [
    'pocid'     => $userid,
    'status'    => 1,
    'gradeid'   => $categoryid,
    'courseid'  => $courseid,
    'sessionid' => $poc_session_date->id
];
if ($DB->record_exists('poc_copy_course', $conditions)) {
    echo json_encode(['status' => 'error', 'message' => 'You have already selected this course in the current session.']);
    exit();
}

// 5. Try to log, assign role, and trigger the event.
try {
    // Insert the record into your custom table.
    $record = new stdClass();
    $record->schoolid   = $schoolid;
    $record->gradeid    = $categoryid;
    $record->courseid   = $courseid;
    $record->sessionid  = $poc_session_date->id;
    $record->pocid      = $userid;
    $record->status     = 1;
    $record->timecreated = time();
    $insertedid = $DB->insert_record('poc_copy_course', $record);
    
    // --- YAHAN PAR SYSTEM ROLE ASSIGN KAREIN ---
    $pocschool_role = $DB->get_record('role', ['shortname' => 'pocschool']);
    
    // FIX: Yeh line missing thi (This line was missing)
    $systemcontext = \context_system::instance(); 
    
    if (!empty($userid) && !empty($pocschool_role->id)) {
        role_assign($pocschool_role->id, $userid, $systemcontext->id);
    }
    // --- ROLE ASSIGNMENT KHATAM ---

    // Prepare and trigger the event (ab yeh sirf course me enrol karega).
    $eventdata = [
        'courseid'      => $courseid,
        'relateduserid' => $userid,
        'objectid'      => $insertedid,
        'context'       => \context_course::instance($courseid)
    ];
    $event = \local_pocenrol\event\poc_course_selected::create($eventdata);
    $event->trigger();

    // Send a final success response.
    $response = [
        'status'  => 'success',
        'message' => 'Your Mapping has been successfully Completed.'
    ];
    echo json_encode($response);
    exit();

} catch (Exception $e) {
    $response = [
        'status'  => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ];
    echo json_encode($response);
    exit();
}