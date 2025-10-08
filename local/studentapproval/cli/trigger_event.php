<?php
define('CLI_SCRIPT', true);

// This line MUST be at the top to load all Moodle functions.
require_once(__DIR__ . '/../../../config.php');

// Now that config.php is loaded, we can use Moodle functions.
list($options, $unrecognized) = cli_get_params(
    ['userid' => false, 'help' => false],
    ['u' => 'userid', 'h' => 'help']
);

if ($unrecognized || $options['help'] || empty($options['userid'])) {
    $help = "Command line script to trigger the 'user_approved' event for a single user.

    Options:
    -u, --userid      Required. The Moodle user ID of the student to approve.
    -h, --help        Print this help.

    Example:
    \$ php local/studentapproval/cli/trigger_event.php --userid=123
    ";
    echo $help;
    die;
}

$userid = (int)$options['userid'];

echo "--> Triggering 'user_approved' event for User ID: {$userid}\n";

// This creates and triggers the event.
\local_studentapproval\event\user_approved::create([
    'context'  => context_system::instance(),
    'objectid' => $userid,
])->trigger();

echo "--> Event has been triggered.\n";
echo "--> Now, check your Moodle cron output to see if the ad-hoc task runs.\n";