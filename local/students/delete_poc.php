<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/local/emailtemplates/email_sender.php');
$id = required_param('id', PARAM_INT);
$reason = required_param('reason', PARAM_TEXT);

global $DB;

// header('Content-Type: application/json');

if ($DB->set_field('student', 'status', 0, array('userid' =>$id ))) {
    // Optionally log the rejection reason or perform other actions.
    \local_emailtemplates\email_sender::send_email("reject", $id, "reject",$reason);
    
    // Send a success response
    echo json_encode(['status' => 'success']);
} else {
    // Send an error response
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete reject']);
}
