<?php
require('../../config.php');
require_login();

$data = optional_param_array('status', [], PARAM_ALPHA);
$remarks = optional_param_array('remark', [], PARAM_TEXT);
$badgecardid = required_param('badgecardid', PARAM_INT);


// print_r($data);
// die;

foreach ($data as $studentid => $status) {
    $remark = $remarks[$studentid] ?? '';

    // Check if the record already exists
    $existing_record = $DB->get_record('timetable', ['timetableid' => $badgecardid, ]);

    if ($existing_record) {
        // Update existing record
        $existing_record->status = $status;
        $existing_record->remark = $remark;
        $existing_record->timecreated = time();
        $DB->update_record('timetable', $existing_record);
    } else {
        // Insert new record
        print_r();
        // $record = new stdClass();
        // $record->studentid = $studentid;
        // $record->status = $status;
        // $record->remark = $remark;
        // $record->badgecardid = $badgecardid;
        // $record->timecreated = time();
        $DB->insert_record('timetable', $record);
    }
}


redirect(new moodle_url('/local/timetable/index.php'), 'timetable saved successfully');
