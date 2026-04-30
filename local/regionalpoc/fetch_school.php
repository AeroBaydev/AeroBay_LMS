<?php
require_once('../../config.php');
require_login();

$roleId = required_param('roleId', PARAM_INT);

// $existing_users = $DB->get_records_sql("
//     SELECT aa.armid AS id, arp.username 
//     FROM {assigned_arm} aa 
//     JOIN {regionalpoc} arp ON aa.armid = arp.id 
//     WHERE arp.status = 1 AND aa.rmid = ?
// ", array($roleId));

$existing_users = $DB->get_records_sql("SELECT ass.id, ass.school_name as username FROM {school} as ass join {schoolassign} as asa on ass.course_cat_id=asa.schoolid WHERE asa.schoolassignee = 228 and asa.status = 1");

header('Content-Type: application/json');
echo json_encode(array_values($existing_users));