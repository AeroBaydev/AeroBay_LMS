<?php
require_once "../../config.php";
require_once $CFG->libdir . "/tablelib.php";
require_once "classes/table/grade_table.php";
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');

global $DB, $OUTPUT, $PAGE;
require_login();

$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);
$catId = optional_param('id', 0, PARAM_INT);
if ($catId) {
    local_pocschool_require_school_access($catId);
}

$context = context_system::instance();
$PAGE->set_context($context);

$table = new grade_class_table('uniqueid');
$table->is_downloading($download, 'timetable_data', 'timetable_data');


if (!$table->is_downloading()) {
  
    $PAGE->set_pagelayout('course');
    $PAGE->set_title('Timetable');
    $PAGE->navbar->add('School List', "$CFG->wwwroot/local/timetable/index.php");
    $PAGE->navbar->add('Grade List', "$CFG->wwwrootlocal/timetable/view_grade.php/index.php?id=$catId");
    $PAGE->requires->js('/local/timetable/js/custom.js');
    echo $OUTPUT->header();
    echo html_writer::tag('h2', 'School Grade List', ['class' => 'custom-heading add-new-timetable']);
}

// Pagination setup
$perpage = 10; // Adjust as needed
$limitfrom = $page * $perpage;

// Correct SQL Query
$fields = "cc.*";
$from = "{course_categories} cc";
$where = "cc.visible = 1";
$params = [];

if ($catId) {
    $where .= " AND cc.parent = :catid";
    $params['catid'] = $catId;
    local_pocschool_apply_trainer_grade_filter($where, $params, 'cc', 'id');
} elseif (local_pocschool_is_poc_user()) {
    local_pocschool_apply_school_filter($from, $where, $params, 'cc', 'parent');
} else {
    local_pocschool_apply_trainer_grade_filter($where, $params, 'cc', 'id');
}

$where .= " ORDER BY cc.id";

// Set query with proper pagination
$table->set_sql($fields, $from, $where, $params);
$table->define_baseurl(new moodle_url('/local/timetable/view_grade.php/index.php', ['page' => $page, 'id' => $catId]));

// Output table
if ($table->is_downloading()) {
    $table->out($perpage, true);
    exit;
} else {
    $table->out($perpage, true);
    echo $OUTPUT->footer();

}
