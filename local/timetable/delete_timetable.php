<?php
require('../../config.php');
require_login();

$id = required_param('id', PARAM_INT); // Get badge ID from URL


global $DB, $USER, $CFG;

// Fetch badge details from DB
$badge = $DB->get_record('timetable', ['id' => $id]);

if ($badge) {
    
    

    // Delete the badge record from the database
    $DB->delete_records('timetable', ['id' => $id]);

    // Redirect with success message
    redirect("$CFG->wwwroot/local/timetable/", 'Badge deleted successfully!', null, \core\output\notification::NOTIFY_SUCCESS);
} else {
    redirect("$CFG->wwwroot/local/timetable/", 'Badge not found!', null, \core\output\notification::NOTIFY_ERROR);
}
