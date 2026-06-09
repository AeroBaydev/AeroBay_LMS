<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');

global $DB;

$students = $DB->get_records('student');
$output = "Total students: " . count($students) . "\n";

foreach ($students as $student) {
    $userid = $student->userid;
    $schoolid = $student->schoolid;
    
    $query1_traineruserid = $DB->get_field('trainer', 'userid', ['schoolid' => $schoolid], IGNORE_MULTIPLE);
    
    if (!$query1_traineruserid) {
        $output .= "Found student without school trainer. UserID: $userid, SchoolID: $schoolid\n";
        
        $gradeid = $student->gradeid;
        $student_Course = $DB->get_record('poc_copy_course', ['gradeid' => $gradeid, 'schoolid' => $schoolid]);
        $courseid = $student_Course ? $student_Course->courseid : 0;
        
        $output .= "CourseID: $courseid\n";
        
        if (!empty($courseid)) {
            $sql = "SELECT tcm.* FROM {trainer_course_mapping} tcm WHERE tcm.courseid = :courseid";
            $mappings = $DB->get_records_sql($sql, ['courseid' => $courseid]);
            $output .= "Mappings for course $courseid: " . count($mappings) . "\n";
            if (count($mappings) > 0) {
                $output .= print_r($mappings, true) . "\n";
                break;
            }
        }
    }
}
file_put_contents('test_output.txt', $output);
