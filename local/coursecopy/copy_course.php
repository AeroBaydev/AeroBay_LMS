<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
global $CFG, $DB;
require_login();
require_capability('moodle/course:create', context_system::instance());

$courseid = optional_param('courseid', 0, PARAM_INT);
$destCategoryId = optional_param('destSubcategory', 0, PARAM_INT);  // Get the destination subcategory

if ($courseid && $destCategoryId) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $category = $DB->get_record('course_categories', ['id' => $destCategoryId], '*', MUST_EXIST);

    $copydata = (object) [
        'courseid' => $course->id,
        'fullname' => $course->fullname,
        'shortname' => $course->shortname,
        'category' => $category->id,
        'visible' => $course->visible,
        'startdate' => $course->startdate,
        'enddate' => $course->enddate,
        'idnumber' => $course->idnumber,
        'userdata' => '0',
        'keptroles' => []
    ];

    \copy_helper::create_copy($copydata);

    echo "Course copied successfully!";
} else {
    echo "Please select a course and destination category.";
}
?>
