<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot.'/course/classes/category.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Delete School');
$PAGE->set_heading('Delete School');
require_login();
require_admin();


global $CFG, $DB;

$schoolid = optional_param('id', 0, PARAM_INT);
$sortname = optional_param('school_sortname', '', PARAM_TEXT);

if (optional_param('confirm', 0, PARAM_INT)) {
    $course_categories = $DB->get_record('course_categories', array('idnumber' => $sortname), 'id, visible', IGNORE_MISSING);

    if ($course_categories) {
        $coursecat = \core_course_category::get((int)$course_categories->id, IGNORE_MISSING, true);
        if ($coursecat) {
            $coursecat->delete_full(false);
        }
    }

    $deleted = $DB->delete_records('school', array('id' => $schoolid));

    if ($deleted !== false) {
       
        redirect("$CFG->wwwroot/local/school/index.php", get_string('deletesuccess', 'local_school'), 2);
    } else {
        print_error('deletion_failed', 'local_school', "$CFG->wwwroot/my/");
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('deleteconfirm', 'local_school'), 
                         new moodle_url("$CFG->wwwroot/local/school/delete_school.php?confirm=1&id=$schoolid&school_sortname=$sortname"), 
                         new moodle_url("$CFG->wwwroot/local/school/index.php"));
    echo $OUTPUT->footer();
}
