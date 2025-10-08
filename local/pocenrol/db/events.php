<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\local_pocenrol\event\poc_course_selected',
        'callback' => '\local_pocenrol\observer::course_selected',
    ],
];