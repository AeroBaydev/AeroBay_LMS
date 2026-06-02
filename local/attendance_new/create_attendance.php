<?php
require_once("../../config.php");
require_once($CFG->libdir . "/tablelib.php");
require_once("classes/table/attendace_table.php");
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');
// require_once("lib.php");

global $DB, $OUTPUT, $PAGE, $USER;
require_login();

$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);
$gradeid = optional_param('catid', '', PARAM_INT);
$schoolid = optional_param('schoolid', '', PARAM_INT);
local_pocschool_require_grade_access($schoolid, $gradeid);
//  $gradeid = optional_param('gradeid', '', PARAM_INT); // Added gradeid
$course_categories_records = $DB->get_record('course_categories', ['id' => $gradeid]);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/attendance_new/create_attendance.php', ['catid' => $gradeid, 'schoolid' => $schoolid, 'gradeid' => $gradeid]));
$PAGE->navbar->add('School List', "$CFG->wwwroot/local/attendance_new/index.php");
$PAGE->navbar->add('Grade List', "$CFG->wwwroot/local/attendance_new/view_grade.php?id=$schoolid"); //grade list
$PAGE->navbar->add('Attendance List', "$CFG->wwwroot/local/attendance_new/view_grade.php/index.php?id=$gradeid");
$PAGE->set_title("Attendance Management");
$PAGE->set_heading("Attendance ($course_categories_records->name)");
$PAGE->set_pagelayout('standard');

$table = new attendace_class_table('uniqueid');
$PAGE->requires->js('/local/attendance_new/js/custom.js');
if (!$table->is_downloading()) {
    $PAGE->set_pagelayout('course');
    $PAGE->set_title('attendance');
    // $PAGE->set_heading('assessmentcard Table');
    echo $OUTPUT->header();
    echo '<div class="action-button d-flex justify-content-between">';
    echo html_writer::start_div('action-button-container');
    echo html_writer::link(new moodle_url("/local/attendance_new/add.php?catid=$gradeid&schoolid=$schoolid"), 'Add New Attendance', array('class' => 'btn btn-primary'));
    echo html_writer::end_div();

    echo "<form method='post' class='d-flex' action='$CFG->wwwroot/local/assessmentcard/index.php' style='display:none !important;'>";
    echo "<input type='search' class='ml-auto form-control rounded mr-2' name='search' placeholder='Search...' value='$search'>";
    echo '<input type="submit" value="Search" class="btn btn-primary mr-2">';
    echo '<a href="' . $CFG->wwwroot . '/local/card/index.php" class="btn btn-secondary mr-2">Clear</a>';
    echo '</form>';
    echo '</div>';
}

$fields = "at.id as attendanceid, at.description as description, at.date as date, at.schoolid as schoolid, at.gradeid as gradeid";
$from = "{attendance} at";
$where = "1=1";
$params = [];

if (!empty($schoolid) && !empty($gradeid)) {
    $where .= " AND at.schoolid = :schoolid";
    $params['schoolid'] = $schoolid;
    $where .= " AND at.gradeid = :gradeid";
    $params['gradeid'] = $gradeid;
}


// Corrected ORDER BY clause
$where .= " ORDER BY at.id DESC";

$perpage = 10;
$DB->execute('SET @row_number := ' . (($perpage * $page)), []);

$table->set_sql($fields, $from, $where, $params);

$table->define_baseurl("$CFG->wwwroot/local/attendance_new/create_attendance.php?catid=$gradeid&schoolid=$schoolid&page=$page");

if ($table->is_downloading()) {
    $table->out($perpage, true);
    exit;
} else {
    $table->out($perpage, true);
    echo $OUTPUT->footer();
}

