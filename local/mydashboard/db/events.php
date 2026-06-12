<?php

defined('MOODLE_INTERNAL') || die();

$observers = array(
   
    array(
        'eventname' => '\core\event\dashboard_viewed',
        'callback'  => '\local_mydashboard\observers::f1'
    ),
    array(
        'eventname' => '\core\event\user_loggedin',
        'callback'  => '\local_mydashboard\observers::user_loggedin'
    )
);
