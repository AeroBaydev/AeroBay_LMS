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
    $gradeid = $student->gradeid;

    $student_Course = $DB->get_record('poc_copy_course', ['gradeid' => $gradeid, 'schoolid' => $schoolid]);
    $courseid = $student_Course ? $student_Course->courseid : 0;

    $query1_traineruserid = $DB->get_field('trainer', 'userid', ['schoolid' => $schoolid], IGNORE_MULTIPLE);
    
    $query2_traineruserid = false;
    if (!$query1_traineruserid && !empty($courseid) && $DB->get_manager()->table_exists('trainer_course_mapping')) {
        $sql = "SELECT t.userid 
                  FROM {trainer_course_mapping} tcm 
                  JOIN {trainer} t ON t.userid = tcm.traineruserid 
                 WHERE tcm.courseid = :courseid
                   AND tcm.status = 1";
        $query2_traineruserid = $DB->get_field_sql($sql, ['courseid' => $courseid], IGNORE_MULTIPLE);
    }
    
    if (!$query1_traineruserid && $query2_traineruserid) {
        $trainer_record = $DB->get_record('trainer', ['userid' => $query2_traineruserid]);
        $trainer_schoolid = $trainer_record ? $trainer_record->schoolid : null;
        
        $output .= "----------------------------------------\n";
        $output .= "STUDENT userid: $userid\n";
        $output .= "STUDENT schoolid: $schoolid\n";
        $output .= "STUDENT gradeid: $gradeid\n";
        $output .= "STUDENT courseid: $courseid\n";
        $output .= "QUERY 1 (Trainer table) result: NULL\n";
        $output .= "QUERY 2 (Mapping table) result: $query2_traineruserid\n";
        $output .= "RESOLVED TRAINER userid: $query2_traineruserid\n";
        $output .= "RESOLVED TRAINER schoolid: $trainer_schoolid\n";
        $output .= "----------------------------------------\n";
        break;
    }
}
file_put_contents('test_output.txt', $output);
