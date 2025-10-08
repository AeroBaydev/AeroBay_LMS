<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\user_enrolment_deleted',
        'callback'    => 'local_myunenrollhandler\observer::user_unenrolled',
        'priority'    => 0,
        'internal'    => true,
    ],
];