<?php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\local_studentapproval\task\process_approval',
        'blocking'  => 0,
        // All schedule information ('minute', 'hour', etc.) has been removed.
    ],
];