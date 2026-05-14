<?php
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');

function get_students($schoolid, $gradeid) {
    global $DB;
    $where = "s.schoolid = :schoolid AND s.gradeid = :gradeid";
    $params = ['schoolid' => $schoolid, 'gradeid' => $gradeid];
    local_pocschool_apply_trainer_student_filter($where, $params, 's');

    return $DB->get_records_sql(
        "SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname, u.email
           FROM {user} u
           JOIN {student} s ON s.userid = u.id
          WHERE {$where}",
        $params
    );
}
