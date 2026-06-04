<?php
require('../../config.php');
require_login();
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');

$id = required_param('id', PARAM_INT); // Get badge ID from URL


global $DB, $USER, $CFG;

// Fetch badge details from DB
$badge = $DB->get_record('timetable', ['id' => $id]);

if ($badge) {
    local_pocschool_require_grade_access($badge->schoolid, $badge->gradeid);

    if ($DB->record_exists('attendance', ['schoolid' => $badge->schoolid, 'gradeid' => $badge->gradeid])) {
        redirect(
            "$CFG->wwwroot/local/timetable/",
            'Attendance records exist for this school and grade. Delete attendance records first to avoid orphan attendance data.',
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
    

    // Delete the badge record from the database
    $DB->delete_records('timetable', ['id' => $id]);

    // Redirect with success message
    redirect("$CFG->wwwroot/local/timetable/", 'Badge deleted successfully!', null, \core\output\notification::NOTIFY_SUCCESS);
} else {
    redirect("$CFG->wwwroot/local/timetable/", 'Badge not found!', null, \core\output\notification::NOTIFY_ERROR);
}
