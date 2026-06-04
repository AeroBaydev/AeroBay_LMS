<?php
define('CLI_SCRIPT', true);
require('config.php');
$url = new moodle_url('delete_attendance.php', ['id' => 157, 'confirm' => 1]);
echo $url->out(false) . "\n";
