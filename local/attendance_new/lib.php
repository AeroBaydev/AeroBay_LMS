<?php
function get_students($schoolid, $gradeid) {
    global $DB;
    return $DB->get_records_sql(
        "SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname, u.email
           FROM {user} u
           JOIN {student} s ON s.userid = u.id
          WHERE s.schoolid = ? AND s.gradeid = ?",
        [$schoolid, $gradeid]
    );
}