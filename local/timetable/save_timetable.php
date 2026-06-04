<?php
require_once("../../config.php");
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');
require_once($CFG->dirroot . '/local/dashboard/lib.php');
global $DB, $USER;

header('Content-Type: application/json'); // Ensure response is JSON
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON data"]);
    exit();
}

if (empty($data['schoolid']) || empty($data['gradeid']) || !isset($data['timetable'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit();
}

$schoolid = intval($data['schoolid']);
$gradeid = intval($data['gradeid']);
$timetable = $data['timetable'];
local_pocschool_require_grade_access($schoolid, $gradeid);

// Get existing timetable entries for the school & grade
$existingRecords = $DB->get_records('timetable', [
    'schoolid' => $schoolid,
    'gradeid' => $gradeid
]);

// Convert existing records into a quick lookup format (day => period)
$existingEntries = [];
foreach ($existingRecords as $record) {
    $existingEntries[$record->day][$record->period] = $record->id;
}

$hasremovals = false;
foreach ($existingEntries as $day => $periods) {
    foreach ($periods as $period => $recordId) {
        if (!isset($timetable[$day][$period])) {
            $hasremovals = true;
            break 2;
        }
    }
}

if ($hasremovals && $DB->record_exists('attendance', ['schoolid' => $schoolid, 'gradeid' => $gradeid])) {
    echo json_encode([
        "success" => false,
        "message" => "Attendance records exist for this school and grade. Delete attendance records first to avoid orphan attendance data."
    ]);
    exit();
}

// Process the submitted timetable
foreach ($timetable as $day => $periods) {
    foreach ($periods as $period => $value) {
        if ($value === "on") {
            // If checked, update or insert the record
            if (isset($existingEntries[$day][$period])) {
                // Update existing record
                // $record = $DB->get_record('timetable', ['id' => $existingEntries[$day][$period]]);
                // $record->timecreated = time();
                // $record->createdby = $USER->id;
                // $DB->update_record('timetable', $record);
            } else {
                // Insert new record
                $record = new stdClass();
                $record->schoolid = $schoolid;
                $record->gradeid = $gradeid;
                $record->period = $period;
                $record->day = $day;
                $record->timecreated = time();
                $record->createdby = $USER->id;
                $DB->insert_record('timetable', $record);
            }
        }
    }
}

// Delete records that are missing from the submitted timetable (unchecked checkboxes)
foreach ($existingEntries as $day => $periods) {
    foreach ($periods as $period => $recordId) {
        if (!isset($timetable[$day][$period])) {
            $DB->delete_records('timetable', ['id' => $recordId]);
        }
    }
}

$gradename = local_dashboard_get_grade_name((int) $gradeid);
local_dashboard_log_activity(
    'timetable_updated',
    'Timetable updated',
    $gradename ? 'Weekly timetable updated for ' . $gradename : 'Weekly timetable updated',
    (int) $schoolid,
    [
        'metadata' => [
            'gradeid' => (int) $gradeid,
        ],
    ]
);

// Return success response
echo json_encode(["success" => true, "message" => "Timetable updated successfully"]);
exit();
?>
