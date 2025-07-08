<?php
require('../../config.php');
require_login();

$data = optional_param_array('status', [], PARAM_ALPHA);
$remarks = optional_param_array('remark', [], PARAM_TEXT);
$assessmentcardid = required_param('assessmentcardid', PARAM_INT);


// print_r($data);
// die;

foreach ($data as $studentid => $status) {
    $remark = $remarks[$studentid] ?? '';

    // Check if the record already exists
    $existing_record = $DB->get_record('assessmentcard_student', ['assessmentcardid' => $assessmentcardid, 'studentid' => $studentid]);

    if ($existing_record) {
        // Update existing record
        $existing_record->status = $status;
        $existing_record->remark = $remark;
        $existing_record->timecreated = time();
        $DB->update_record('assessmentcard_student', $existing_record);
    } else {
        // Insert new record
        $record = new stdClass();
        $record->studentid = $studentid;
        $record->status = $status;
        $record->remark = $remark;
        $record->assessmentcardid = $assessmentcardid;
        $record->timecreated = time();
        $DB->insert_record('assessmentcard_student', $record);
    }
}


redirect(new moodle_url('/local/assessmentcard/index.php'), 'assessmentcard saved successfully');
