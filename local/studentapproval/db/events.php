<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\local_studentapproval\event\user_approved',
        'callback' => '\local_studentapproval\observer::handle_user_approved',
    ],
];