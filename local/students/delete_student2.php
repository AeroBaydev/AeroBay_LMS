<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
$id = required_param('id', PARAM_INT);


global $DB;




if ($user = $DB->get_record('user', array('id' => $id))) {
    $deleted = $DB->delete_records('student', array('userid' => $id));
    $deleted1 = user_delete_user($user);
    echo json_encode(['status' => 'success']);
    }
