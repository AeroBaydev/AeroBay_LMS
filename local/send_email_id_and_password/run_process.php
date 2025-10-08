<?php
define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/send_email_id_and_password/lib.php');

echo "--> Manually running the student processing function from lib.php...\n";
echo "==================================================================\n";

// This directly calls the main function from your lib.php file.
local_send_email_id_and_password_run_process();

echo "==================================================================\n";
echo "--> Test run finished.\n";