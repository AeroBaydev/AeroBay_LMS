<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/local/regionalpoc/lib.php');
require_login();
local_regionalpoc_require_regional_manager();


global $CFG, $DB, $OUTPUT, $PAGE, $USER;
    
$id = optional_param('id', 0, PARAM_INT);
// $userid = optional_param('userid', 0, PARAM_INT);
$confirm=optional_param('confirm', 0, PARAM_INT);
$usertype = 'arm';

$conditions = ['userid' => $id, 'usertype' => 'asstmanager'];
if (!is_siteadmin()) {
    $conditions['pocid'] = $USER->id;
}
$armrecord = $DB->get_record('regionalpoc', $conditions);
if (!$armrecord) {
    throw new required_capability_exception(context_system::instance(), 'local/pocschool:view', 'nopermissions', '');
}

if ($confirm) {
    $deleted1 = true;
    $user = $DB->get_record('user', ['id' => $id], '*', IGNORE_MISSING);
    if ($user && empty($user->deleted)) {
        $deleted1 = user_delete_user($user);
    }
    $deleted = $DB->delete_records('regionalpoc', array('userid' => $id));
    $DB->delete_records('schoolassign', ['userid' => $id]);
    if ($DB->get_manager()->table_exists('regionalpoc_arm_school')) {
        $DB->delete_records('regionalpoc_arm_school', ['userid' => $id]);
    }

    if ($deleted && $deleted1 !== false) {
        redirect("$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?usertype=$usertype", get_string('regionalpocdelete', 'local_regionalpoc'), 2);
    } else {
        print_error('deletion_failed', 'local_regionalpoc', "$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php");
    }
} else {
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url(new moodle_url('/local/regionalpoc/delete_regionalpoc.php', ['id' => $id, 'usertype' => $usertype]));
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('deleteconfirm', 'local_regionalpoc'), 
                         new moodle_url('/local/regionalpoc/delete_regionalpoc.php', ['confirm' => 1, 'id' => $id, 'usertype' => $usertype]), 
                         new moodle_url("$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?usertype=$usertype"));
    echo $OUTPUT->footer();
}
