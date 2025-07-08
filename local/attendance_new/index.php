<?php
require_once "../../config.php";
require_once $CFG->libdir . "/tablelib.php";
require_once "classes/table/school_table.php";

global $DB, $OUTPUT, $PAGE;
require_login();
$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);

$context = context_system::instance();
$PAGE->set_context($context);

$table = new school_class_table('uniqueid');

 $table->is_downloading($download, 'timetable_data', 'timetable_data');
 $PAGE->requires->css(new moodle_url('/local/timetable/style/customupdate.css'));
if (!$table->is_downloading()) {
    $PAGE->set_pagelayout('course');
    $PAGE->set_title('Attendance');
    
    echo $OUTPUT->header();
    $heading_text = "School List";
    echo html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-new-timetable'));
    // echo '<div class="action-button d-flex justify-content-between">';
    // echo html_writer::start_div('action-button-container');
    // echo html_writer::link(new moodle_url('/local/timetable/addtimetable.php'), 'Add New timetable', array('class' => 'btn btn-primary'));
    // echo html_writer::end_div();

    // echo "<form method='post' class='d-flex' action='$CFG->wwwroot/local/timetable/index.php'>";
    // echo "<input type='search' class='ml-auto form-control rounded mr-2' name='search' placeholder='Search...' value='$search'>";
    // echo '<input type="submit" value="Search" class="btn btn-primary mr-2">';
    // echo '<a href="' . $CFG->wwwroot . '/local/timetable/index.php" class="btn btn-secondary mr-2">Clear</a>';
    // echo '</form>';
    // echo '</div>';
}
// $category_listing = $DB->get_records_sql("
//     SELECT cc.*
//     FROM {course_categories} cc
//     JOIN {school} s ON cc.id = s.course_cat_id
//     WHERE cc.parent = 0
//       AND cc.visible = 1
// ");
$fields = "cc.*";
$from = "{course_categories} cc
         JOIN {school} s ON cc.id = s.course_cat_id";
$where = "cc.parent = 0 AND cc.visible = 1";
$params = [];


if ($search) {
   
}
$where .= ' ORDER BY cc.id ';
$perpage = 10;
$DB->execute('SET @row_number := ' . (($perpage * $page)), []);

$table->set_sql($fields, $from, $where, $params);
$table->define_baseurl("$CFG->wwwroot/local/timetable/index.php?page=$page");

if ($table->is_downloading()) {
    $table->out($perpage, true);
    exit;
} else {
    $table->out($perpage, true);
    echo $OUTPUT->footer();
}
