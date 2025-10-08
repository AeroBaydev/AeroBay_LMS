<?php
namespace local_myunenrollhandler;
defined('MOODLE_INTERNAL') || die();

// Include POC List Student functions
require_once(__DIR__ . '/../pocliststudent.php');

class observer {

    public static function user_unenrolled(\core\event\user_enrolment_deleted $event) {
        global $DB;
        
        $userid = $event->relateduserid;
        $courseid = $event->courseid;

        // Get POC students
      //  $students_result = pocliststudent::get_poc_students($userid);
        
      
    }
}