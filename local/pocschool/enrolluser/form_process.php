<?php
require_once('../../../config.php');
global $DB;

// This 'id' parameter IS the Moodle Course ID.
$courseid = required_param('id', PARAM_INT);
$sesskey = required_param('sesskey', PARAM_ALPHANUM);

require_login();
require_sesskey($sesskey);

// Security Check: This is important.
$context = context_course::instance($courseid);
// require_capability('local/pocstudnetenroll:manage', $context);

// Get gradeid and pocid from your custom poc_copy_course table.
$poc_course_data = $DB->get_record('poc_copy_course', ['courseid' => $courseid, 'status' => 1]);
if (!$poc_course_data) {
    throw new moodle_exception('invalidcourse', 'error');
}
$gradeid = $poc_course_data->gradeid;
$pocid = $poc_course_data->pocid;

// Handle adding users (This part is unchanged).
if (isset($_POST['add'])) {
    $userstoadd = optional_param_array('potential_select', [], PARAM_INT);
    foreach ($userstoadd as $userid) {
        // To avoid duplicates, first check if an enroll record already exists and delete it.
        $DB->delete_records('pocstudnetenroll_queue', ['userid' => $userid, 'gradeid' => $gradeid, 'action' => 'enroll']);

        $queueitem = new \stdClass();
        $queueitem->userid = $userid;
        $queueitem->courseid = $courseid;
        $queueitem->gradeid = $gradeid;
        $queueitem->pocid = $pocid;
        $queueitem->action = 'enroll';
        $queueitem->status = 'pending';
        $queueitem->timecreated = time();
        $queueitem->timemodified = $queueitem->timecreated;
        $queueid = $DB->insert_record('pocstudnetenroll_queue', $queueitem);

        \local_pocstudnetenroll\event\student_enroll_action_triggered::create([
            'context'  => $context,
            'objectid' => $queueid,
            'other'    => ['userid' => $userid, 'action' => 'enroll']
        ])->trigger();
    }
}

// --- NEW LOGIC for Handling removing users ---
if (isset($_POST['remove'])) {
    $userstoremove = optional_param_array('existing_select', [], PARAM_INT);
    foreach ($userstoremove as $userid) {
        // Action 1: Delete the existing 'enroll' record from the queue.
        // This makes the user disappear from the left list on the UI.
        $DB->delete_records('pocstudnetenroll_queue', [
            'userid'   => $userid,
            'gradeid'  => $gradeid,
            'action'   => 'enroll'
        ]);
        
        // Update student table using userid field (not id field)
        $DB->set_field('student', 'email_status', 0, ['userid' => $userid]);
        $DB->set_field('student', 'status', 2, ['userid' => $userid]);
        // Action 2: Create a new 'unenroll' record and trigger the event.
        // This tells the observer to unenroll the user from the Moodle course.
        $queueitem = new \stdClass();
        $queueitem->userid = $userid;
        $queueitem->courseid = $courseid;
        $queueitem->gradeid = $gradeid;
        $queueitem->pocid = $pocid;
        $queueitem->action = 'unenroll';
        $queueitem->status = 'pending';
        $queueitem->timecreated = time();
        $queueitem->timemodified = $queueitem->timecreated;
        $queueid = $DB->insert_record('pocstudnetenroll_queue', $queueitem);
        
        \local_pocstudnetenroll\event\student_enroll_action_triggered::create([
            'context'  => $context,
            'objectid' => $queueid,
            'other'    => ['userid' => $userid, 'action' => 'unenroll']
        ])->trigger();
    }
}

// Redirect back to the student list page, using the gradeid for the URL.
redirect(new moodle_url('/local/pocschool/enrolluser/studentlist.php', ['id' => $gradeid]));