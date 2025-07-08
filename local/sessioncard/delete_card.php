<?php
require('../../config.php');
require_login();

$id = required_param('id', PARAM_INT); // Get badge ID from URL


global $DB, $USER, $CFG;

// Fetch badge details from DB
$badge = $DB->get_record('sessioncard', ['id' => $id]);

if ($badge) {
    
    

    // Delete the badge record from the database
    $DB->delete_records('sessioncard', ['id' => $id]);

    // Redirect with success message
    redirect("$CFG->wwwroot/local/sessioncard/", 'Badge deleted successfully!', null, \core\output\notification::NOTIFY_SUCCESS);
} else {
    redirect("$CFG->wwwroot/local/sessioncard/", 'Badge not found!', null, \core\output\notification::NOTIFY_ERROR);
}
