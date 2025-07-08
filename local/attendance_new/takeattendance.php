<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/attendance_new/lib.php');

// Required parameters
$schoolid = required_param('schoolid', PARAM_INT);
$gradeid = required_param('catid', PARAM_INT);
$attendanceid = required_param('attendanceid', PARAM_INT);

// Fetch the list of students based on school and grade
$students = get_students($schoolid, $gradeid);
// print_r($students);
// die;
// Output the page header

// Fetch existing attendance records
$existing_badgecard = $DB->get_records('attendance_student', ['attendanceid' => $attendanceid], '', 'studentid, status, remark');

$badgecard_data = [];
foreach ($existing_badgecard as $record) {
    $badgecard_data[$record->studentid] = [
        'status' => $record->status,
        'remark' => $record->remark
    ];
}

echo $OUTPUT->header();
?>

<form method="get" action="saveattendance.php">
    <input type="hidden" name="schoolid" value="<?php echo $schoolid; ?>">
    <input type="hidden" name="catid" value="<?php echo $gradeid; ?>">
    <input type="hidden" name="attendanceid" value="<?php echo $attendanceid; ?>">
    
    <table class="generaltable" style="width: 100%; text-align: left;">
        <thead>
            <tr>
                <th>First name / Last name</th>
                <th>Email address</th>
                <th>P</th>
                <th>A</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($students as $student): ?>
    <tr>
        <td>
            <div class="user-info">
                <div class="user-initials" style="display: inline-block; background: #ccc; border-radius: 50%; width: 30px; height: 30px; text-align: center; line-height: 30px; font-weight: bold;">
                    <?php echo strtoupper(substr($student->fullname, 0, 1)) . strtoupper(substr($student->fullname, 0, 1)); ?>
                </div>
                <span style="margin-left: 10px;"><?php echo $student->fullname; ?></span>
            </div>
        </td>
        <td><?php echo $student->email; ?></td>
        <?php
            $existing_status = $badgecard_data[$student->id]['status'] ?? '';
            $existing_remark = $badgecard_data[$student->id]['remark'] ?? '';
        ?>
        <td><input type="radio" name="status[<?php echo $student->id; ?>]" value="P" <?php echo ($existing_status === 'P') ? 'checked' : ''; ?>></td>
        <td><input type="radio" name="status[<?php echo $student->id; ?>]" value="A" <?php echo ($existing_status === 'A') ? 'checked' : ''; ?>></td>
        <td><input type="text" name="remark[<?php echo $student->id; ?>]" value="<?php echo htmlspecialchars($existing_remark); ?>" style="width: 100%;"></td>
    </tr>
<?php endforeach; ?>

        </tbody>
    </table>

    <div style="margin-top: 20px; text-align: center;">
        <button type="submit" class="btn btn-primary">Save and show next page</button>
    </div>
</form>

<?php
// Output the page footer
echo $OUTPUT->footer();
?>
