<?php
defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname'   => '\local_pocstudnetenroll\event\student_enroll_action_triggered',
        'callback'    => '\local_pocstudnetenroll\observer::process_enroll_action',
    ),
);