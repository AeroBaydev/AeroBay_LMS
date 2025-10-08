<?php
define('CLI_SCRIPT', true);

// This line loads all of Moodle.
require_once(__DIR__ . '/../../../config.php');

// --- Your Manual User ID ---
// <-- IMPORTANT: Change this to the User ID you want to test.
$userid = 208; 
// -------------------------

echo "=====================================================\n";
echo "--> Manually triggering 'user_approved' event for User ID: {$userid}\n";

// This is the line that triggers the event.
\local_studentapproval\event\user_approved::create([
    'context'  => context_system::instance(),
    'objectid' => $userid,
])->trigger();

echo "--> Event has been triggered for User ID: {$userid}.\n";
echo "=====================================================\n";