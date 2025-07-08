<?php
require('../../config.php');
require_login();

$id = required_param('id', PARAM_INT); // Get badge ID from URL
$parent = required_param('parent', PARAM_INT); // Get badge ID from URL

global $DB, $USER, $CFG;

// Fetch badge details from DB
$badge = $DB->get_record('assessmentcard', ['id' => $id]);

if ($badge) {
    
    

    // Delete the badge record from the database
    $DB->delete_records('assessmentcard', ['id' => $id]);

    // Redirect with success message
    redirect("$CFG->wwwroot/local/assessmentcard/subcardindex.php?id=$parent", 'Badge deleted successfully!', null, \core\output\notification::NOTIFY_SUCCESS);
} else {
    redirect("$CFG->wwwroot/local/assessmentcard/subcardindex.php?id=$parent", 'Badge not found!', null, \core\output\notification::NOTIFY_ERROR);
}
