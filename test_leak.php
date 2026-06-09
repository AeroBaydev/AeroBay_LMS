<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');

global $DB;

$output = "";

$students = $DB->get_records('student');
foreach ($students as $student) {
    $school_number = $student->schoolid;
    
    // Simulate mydashboard/index.php logic EXACTLY
    $student_Course = $DB->get_record('poc_copy_course', ['gradeid' => $student->gradeid, 'schoolid' => $school_number]);
    $courseid = $student_Course ? $student_Course->courseid : 0;
    
    $query1_traineruserid = $DB->get_field('trainer', 'userid', ['schoolid' => $school_number], IGNORE_MULTIPLE);
    
    $query2_traineruserid = false;
    if (!$query1_traineruserid && !empty($courseid) && $DB->get_manager()->table_exists('trainer_course_mapping')) {
        $sql = "SELECT t.userid 
                  FROM {trainer_course_mapping} tcm 
                  JOIN {trainer} t ON t.userid = tcm.traineruserid 
                 WHERE tcm.courseid = :courseid
                   AND tcm.status = 1";
        $query2_traineruserid = $DB->get_field_sql($sql, ['courseid' => $courseid], IGNORE_MULTIPLE);
    }
    
    $traineruserid = $query1_traineruserid ?: $query2_traineruserid;
    
    if ($traineruserid) {
        $trainer = $DB->get_record('trainer', ['userid' => $traineruserid]);
        if ($trainer && $trainer->schoolid != $school_number) {
            $output .= "FOUND LEAK!\n";
            $output .= "Student UserID: {$student->userid}\n";
            $output .= "Student SchoolID: $school_number\n";
            $output .= "Student GradeID: {$student->gradeid}\n";
            $output .= "Resolved Trainer UserID: $traineruserid\n";
            $output .= "Resolved Trainer SchoolID: {$trainer->schoolid}\n";
            $output .= "Query 1 Result: " . ($query1_traineruserid ?: 'false') . "\n";
            $output .= "Query 2 Result: " . ($query2_traineruserid ?: 'false') . "\n";
            break;
        }
    }
}

if (empty($output)) {
    $output = "No students are currently seeing the wrong trainer based on the simulation.\n";
}

file_put_contents('test_leak.txt', $output);
