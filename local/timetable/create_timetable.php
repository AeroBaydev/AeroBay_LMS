<?php
require_once("../../config.php");
require_once($CFG->libdir . "/tablelib.php");
require_once("classes/table/timetable_table.php");
require_once("lib.php");

global $DB, $OUTPUT, $PAGE, $USER;
require_login();

$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);
$gradeid = optional_param('catid', '', PARAM_INT);
$schoolid = optional_param('schoolid', '', PARAM_INT);
// $gradeid = optional_param('gradeid', '', PARAM_INT); // Added gradeid
$course_categories_records = $DB->get_record('course_categories', ['id' => $gradeid]);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/timetable/view.php', ['catid' => $catid, 'schoolid' => $schoolid, 'gradeid' => $gradeid]));
$PAGE->navbar->add('School List', "$CFG->wwwroot/local/timetable/");
$PAGE->navbar->add('Grade List', "$CFG->wwwroot/local/timetable/view_grade.php?id=$schoolid"); //grade list
$PAGE->navbar->add('Time Table List', "$CFG->wwwroot/local/timetable/view_grade.php/index.php?id=$catId");
$PAGE->set_title("Timetable Management");
$PAGE->set_heading("Timetable ($course_categories_records->name)");
$PAGE->set_pagelayout('standard');

$PAGE->requires->js('/local/timetable/js/custom.js');

$PAGE->requires->css(new moodle_url('/local/timetable/customedit.css'));

// Fetch existing timetable data based on schoolid and gradeid
$timetable_records = $DB->get_records('timetable', ['schoolid' => $schoolid, 'gradeid' => $gradeid]);
$catId = $gradeid; // Example course ID

$courseid = $DB->get_record_sql("SELECT * FROM {course} where visible=1  and 
id in( select courseid from {poc_copy_course} where gradeid=$catId and status=1)");
$coursename = get_course_name($courseid->id);
// Convert records into an array for pre-checking checkboxes
$checked_timetable = [];
foreach ($timetable_records as $record) {
    $checked_timetable[$record->day][$record->period] = true;
}

// Render Page Header
echo $OUTPUT->header();

?>

<h2>Timetable</h2>
<form id="timetableForm">
    <table>
        <tr>
            <th>D</th>
            <th>I</th>
            <th>II</th>
            <th>III</th>
            <th>IV</th>
            <th>V</th>
            <th>VI</th>
            <th>VII</th>
            <th>VII</th>
            <th>IX</th>
        </tr>
        <tbody>
            <?php
            $days = ['M' => 'Monday', 'T' => 'Tuesday', 'W' => 'Wednesday', 'Th' => 'Thursday', 'F' => 'Friday', 'S' => 'Saturday'];
            $periods = ['1', '2', '3',  '4', '5','6','7','8','9'];

            foreach ($days as $short => $dayname) {
                echo "<tr>";
                echo "<td class='day-cell day-{$short}'>{$short}</td>";
                foreach ($periods as $period) {
                    if ($period === 'BREAK') {
                        echo "<td class='break-cell'>BREAK</td>";
                    } else {
                        $checked = isset($checked_timetable[$dayname][$period]) ? 'checked' : '';
                        echo "<td>
                                <label>
                                    <input type='checkbox' name='timetable[{$dayname}][{$period}]' {$checked}> {$coursename}
                                </label>
                              </td>";
                    }
                }
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
    <input type="hidden" name="schoolid" value="<?php echo $schoolid; ?>">
    <input type="hidden" name="gradeid" value="<?php echo $gradeid; ?>"> <!-- Add gradeid -->
    <button type="button" class="submit-btn" onclick="submitTimetable()" 
    style="background-color: #007bff; color: white; border: none; padding: 12px 24px; 
           font-size: 16px; border-radius: 50px; cursor: pointer; 
           transition: background 0.3s, transform 0.2s;"
    onmouseover="this.style.backgroundColor='#0056b3'; this.style.transform='scale(1.05)';"
    onmouseout="this.style.backgroundColor='#007bff'; this.style.transform='scale(1)';"
    onmousedown="this.style.backgroundColor='#004494'; this.style.transform='scale(0.98)';"
    onmouseup="this.style.backgroundColor='#0056b3'; this.style.transform='scale(1.05)';">
    Save Timetable
</button>

</form>


<pre id="output"></pre>

<?php

echo $OUTPUT->footer();

?>


<script>
function submitTimetable() {
    const form = document.getElementById('timetableForm');
    const formData = new FormData(form);

    let timetableData = {
        schoolid: formData.get('schoolid'),
        gradeid: formData.get('gradeid'),
        timetable: {}
    };

    // Convert FormData into a structured JSON object
    formData.forEach((value, key) => {
        if (typeof key !== "string") return; // Ensure key is a string

        let match = key.match(/^timetable\[(.*?)\]\[(.*?)\]$/);
        if (match) {
            let day = match[1];
            let period = match[2];

            if (!timetableData.timetable[day]) {
                timetableData.timetable[day] = {};
            }
            timetableData.timetable[day][period] = value; // Store the "on" value
        }
    });

    console.log("Formatted Data:", timetableData); // Debugging

    // Send data via AJAX request
    fetch('<?php echo new moodle_url('/local/timetable/save_timetable.php'); ?>', {
        method: 'POST',
        body: JSON.stringify(timetableData),
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Timetable saved successfully!");
        } else {
            alert("Error saving timetable: " + data.message);
        }
    })
   
}


</script>
