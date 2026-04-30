<?php
require_once('../../config.php');
require_login();

$roleId = required_param('roleId', PARAM_INT);

$existing_users = $DB->get_records_sql("
    SELECT aa.armid AS id, arp.username 
    FROM {assigned_arm} aa 
    JOIN {regionalpoc} arp ON aa.armid = arp.id 
    WHERE arp.status = 1 AND aa.rmid = ?
", array($roleId));

header('Content-Type: application/json');
echo json_encode(array_values($existing_users));
