<?php
namespace local_myunenrollhandler;
defined('MOODLE_INTERNAL') || die();

class pocliststudent {
    
    public static function get_poc_students($poc_id) {
        global $DB;
        
        // Step 1: Get school ID and grade ID from poc_copy_course table
        $poc_school_grade = $DB->get_record('poc_copy_course', ['pocid' => $poc_id]);
        
        if (!$poc_school_grade) {
            return [
                'success' => false,
                'message' => 'POC record not found in poc_copy_course table',
                'poc_id' => $poc_id
            ];
        }
        
        $schoolid = $poc_school_grade->schoolid;
        $gradeid = $poc_school_grade->gradeid;
        
        // Step 2: Get all students with same school and grade from student table
        $matching_students = $DB->get_records('student', [
            'schoolid' => $schoolid,
            'gradeid' => $gradeid
        ], '', 'userid, schoolid, gradeid');
        
        // Log the result
        $result_log = new \stdClass();
        $result_log->eventname = '\local_myunenrollhandler\event\poc_students_found';
        $result_log->component = 'local_myunenrollhandler';
        $result_log->action = 'poc_students_retrieved';
        $result_log->target = 'user';
        $result_log->objecttable = 'user';
        $result_log->objectid = $poc_id;
        $result_log->crud = 'r';
        $result_log->edulevel = 0;
        $result_log->contextid = 1;
        $result_log->contextlevel = 10;
        $result_log->contextinstanceid = 0;
        $result_log->userid = $poc_id;
        $result_log->courseid = 0;
        $result_log->relateduserid = $poc_id;
        $result_log->anonymous = 0;
        $result_log->other = 'POC Students found - POC ID: ' . $poc_id . 
                            ', School ID: ' . $schoolid . 
                            ', Grade ID: ' . $gradeid . 
                            ', Total Students: ' . count($matching_students);
        $result_log->timecreated = time();
        $result_log->origin = 'web';
        $result_log->ip = '127.0.0.1';
        $result_log->realuserid = null;
        
        $DB->insert_record('logstore_standard_log', $result_log);
        
        return [
            'success' => true,
            'poc_id' => $poc_id,
            'school_id' => $schoolid,
            'grade_id' => $gradeid,
            'students' => $matching_students,
            'total_students' => count($matching_students)
        ];
    }
}