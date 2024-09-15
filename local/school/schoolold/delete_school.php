<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Delete School');
$PAGE->set_heading('Delete School');
require_login();


global $CFG, $DB;

$schoolid = optional_param('id', 0, PARAM_INT);
$sortname = optional_param('school_sortname', '', PARAM_TEXT);

if (optional_param('confirm', 0, PARAM_INT)) {
    $deleted = $DB->delete_records('school', array('id' => $schoolid));
               $DB->delete_records('cohort', array('name'=>$sortname));
               $DB->delete_records('course_categories', array('name'=>$sortname));

    if ($deleted !== false) {
       
        redirect("$CFG->wwwroot/local/school/school_manage.php", get_string('deletesuccess', 'local_school'), 2);
    } else {
        print_error('deletion_failed', 'local_school', "$CFG->wwwroot/my/");
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('deleteconfirm', 'local_school'), 
                         new moodle_url("$CFG->wwwroot/local/school/delete_school.php?confirm=1&id=$schoolid&school_sortname=$sortname"), 
                         new moodle_url("$CFG->wwwroot/local/school/school_manage.php"));
    echo $OUTPUT->footer();
}
