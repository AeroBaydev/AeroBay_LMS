<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');
require_login();
$id = required_param('id', PARAM_INT);


global $DB;

if (local_pocschool_is_trainer_user()) {
    throw new required_capability_exception(context_system::instance(), 'local/pocschool:view', 'nopermissions', '');
}



if ($user = $DB->get_record('user', array('id' => $id))) {
    $deleted = $DB->delete_records('student', array('userid' => $id));
    $deleted1 = user_delete_user($user);
    echo json_encode(['status' => 'success']);
    }
