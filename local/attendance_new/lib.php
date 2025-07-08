<?php
function get_students($schoolid, $gradeid) {
    global $DB;
    return $DB->get_records_sql("SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname FROM {user} u
    join {student} s on s.userid=u.id
     WHERE schoolid = ? AND gradeid = ?", [$schoolid, $gradeid]);
}