<?php

defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\core\event\user_created',
        'callback'  => '\local_hierarchy\observers::user_created'
    ),
	
	array(
        'eventname' => '\core\event\user_updated',
        'callback'  => '\local_hierarchy\observers::user_updated'
    ),
	
	array(
        'eventname' => '\core\event\user_deleted',
        'callback'  => '\local_hierarchy\observers::user_deleted'
    ),
	
	array(
        'eventname' => '\core\event\enrol_instance_deleted',
        'callback'  => '\local_hierarchy\observers::enrol_instance_deleted'
    ),
	
	array(
        'eventname' => '\core\event\role_deleted',
        'callback'  => '\local_hierarchy\observers::role_deleted'
    ),
    
    array(
        'eventname' => '\local_hierarchy\event\dept_deleted',
        'callback'  => '\local_hierarchy\observers::dept_deleted'
    ),
	
	array(
        'eventname' => '\core\event\course_category_deleted',
        'callback'  => '\local_hierarchy\observers::category_deleted'
    ),
    
    array(
        'eventname' => '\core\event\enrol_instance_created',
        'callback'  => '\local_hierarchy\observers::enrol_instance_created'
    ),  

    array(
        'eventname' => '\core\event\course_updated',
        'callback'  => '\local_hierarchy\observers::course_updated'
    )
			
);
?>