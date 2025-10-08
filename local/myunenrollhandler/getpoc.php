<?php
require_once(__DIR__ . '/../../config.php');



  function getpocusers($poc_id) {    
    global $DB;
     $poc_school_grade = $DB->get_record('poc_copy_course', ['pocid' => $poc_id]);
      

    $matching_students = $DB->get_records('student', [
        'schoolid' => $poc_school_grade->schoolid,
        'gradeid' => $poc_school_grade->gradeid
    ], '', 'userid, schoolid, gradeid');


} 

getpocusers(151);