<?php
require_once("../../config.php");
// require_login(); // Ensure the user is logged in
header('Content-Type: application/json');
// Get parameters from the request
$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);

// Validate parameters
if ($id <= 0 || !in_array($action, ['suspend', 'activate'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Example logic for handling the action
try {
    $poc = $DB->get_record('poc', ['userid' => $id], '*', MUST_EXIST);
    $pocSchoolid = $DB->get_record('poc_copy_course', ['pocid' => $id]);
   
    $conditions = [
        'schoolid' => $pocSchoolid->schoolid,
       
    ];
    
    // Fetch records with specific conditions
    $studentRecords = $DB->get_records('student', $conditions);
   //  print_r($Studentids);
    // die;
   // print_r($studentRecords);
    if ($studentRecords) {
      //  die("asd");
       // print_r($studentRecords);
        foreach ($studentRecords as $studentRecord) {
            $userId = $studentRecord->userid; // Assuming 'userid' is the column containing user IDs
    
            $user = $DB->get_record('user', ['id' => $userId]);
    
            if ($action == 'suspend') {
                $user->suspended = 1;
            } else if ($action == 'activate') {
                $user->suspended = 0;
            }
    
            $DB->update_record('user', $user);
        }
    }

    $poc->id;
    if ($action == 'suspend') {
        $poc->suspended = 1;
    } else if ($action == 'activate') {
        $poc->suspended = 0;
    }
    
    $DB->update_record('poc', $poc);

    echo json_encode(['success' => true]);
} catch (Exception $e) {

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
