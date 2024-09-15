<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_login();


global $CFG, $DB;
    
$id = optional_param('id', 0, PARAM_INT);
$data = $DB->get_record_sql("SELECT COUNT(sa.userid) as schoolcount FROM {schoolassign} sa  join {course_categories} cc on  sa.schoolid=cc.id WHERE sa.userid = ?", [$id]);
if($data->schoolcount!=0)
{
echo $OUTPUT->header();
        echo $OUTPUT->notification('please remove school', 'notifyproblem');
        $continueurl1 = new moodle_url("$CFG->wwwroot/local/poc/poc_management.php");
    echo $OUTPUT->single_button($continueurl1, get_string('continue'),'get');
    echo $OUTPUT->footer();
    die;
}
if (optional_param('confirm', 0, PARAM_INT)) {

    if ($user = $DB->get_record('user', array('id' => $id))) {
    $deleted1 = user_delete_user($user);
    $deleted = $DB->delete_records('poc', array('userid' => $id));
    }
    
    if ($deleted && $deleted1 !== false) {
        redirect("$CFG->wwwroot/local/poc/poc_management.php", get_string('pocdelete', 'local_poc'), 2);
    } else {
        print_error('deletion_failed', 'local_poc', "$CFG->wwwroot/local/poc/poc_management.php");
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('deleteconfirm', 'local_poc'), 
                         new moodle_url("$CFG->wwwroot/local/poc/delete_poc.php?confirm=1&id=$id"), 
                         new moodle_url("$CFG->wwwroot/local/poc/poc_management.php"));
    echo $OUTPUT->footer();
}
