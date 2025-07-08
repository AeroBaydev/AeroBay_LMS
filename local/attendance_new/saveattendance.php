<?php
require('../../config.php');
require_login();

// Retrieve submitted data
$data = optional_param_array('status', [], PARAM_ALPHA);
$remarks = optional_param_array('remark', [], PARAM_TEXT);
$schoolid = required_param('schoolid', PARAM_INT);
$gradeid = required_param('catid', PARAM_INT);
$attendanceid = required_param('attendanceid', PARAM_INT);


if (!empty($data)) {
    foreach ($data as $studentid => $status) {
        $record = new stdClass();
        $record->schoolid = $schoolid;
        $record->gradeid = $gradeid;
        $record->studentid = $studentid;
        $record->status = $status;
        $record->remark = $remarks[$studentid] ?? '';
        $record->attendanceid=$attendanceid;
       
        // Insert or update attendance record
        $existing = $DB->get_record('attendance_student', [
            'studentid' => $studentid,
            'attendanceid'=>$attendanceid
     
        ]);

        if ($existing) {
            $record->id = $existing->id;
          
           
          
            $DB->update_record('attendance_student', $record);
        } else {
            // print_r($studentid);
            // echo "asas<br>";
            // print_r($attendanceid);
            // die;
            $DB->insert_record('attendance_student', $record);
        }
    }
}

// Redirect to the index page with success message
 // Redirect with parameters
 redirect(new moodle_url('/local/attendance_new/create_attendance.php', [
    'catid' => $gradeid, 
    'schoolid' => $schoolid
]), get_string('attendancecreated', 'local_attendance'), null, \core\output\notification::NOTIFY_SUCCESS);
