<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_login();


global $CFG, $DB;
    
$id = optional_param('id', 0, PARAM_INT);
// $userid = optional_param('userid', 0, PARAM_INT);
$confirm=optional_param('confirm', 0, PARAM_INT);
$usertype = optional_param('usertype', '', PARAM_TEXT);


if ($confirm) {
    if ($user = $DB->get_record('user', array('id' => $id))) {
        $deleted1 = user_delete_user($user);
        $deleted = $DB->delete_records('regionalpoc', array('userid' => $id));
        }


    if ($deleted && $deleted1 !== false) {
        redirect("$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?usertype=$usertype", get_string('regionalpocdelete', 'local_regionalpoc'), 2);
    } else {
        print_error('deletion_failed', 'local_regionalpoc', "$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php");
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('deleteconfirm', 'local_regionalpoc'), 
                         new moodle_url("$CFG->wwwroot/local/regionalpoc/delete_regionalpoc.php?confirm=1&id=$id?usertype=$usertype"), 
                         new moodle_url("$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?usertype=$usertype"));
    echo $OUTPUT->footer();
}
