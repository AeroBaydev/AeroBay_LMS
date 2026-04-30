<?php
// lib.php
defined('MOODLE_INTERNAL') || die();

function local_pocschool_extend_navigation(global_navigation $navigation) {
    global $CFG, $USER, $PAGE;

    $context = context_system::instance();

    if (has_capability('local/pocschool:view', $context, $USER)) {
        
        if(!is_siteadmin()){
        $CFG->custommenuitems = "Alloted School | /local/pocschool
                        Trainer Management | /local/trainer/trainer_manage.php
                        RM / ARM Management | /local/regionalpoc/rm_arm_manage.php
                        Student Management | /local/students/student_manage.php";
        }
    }
    if (has_capability('local/pocschool:trainerrm', $context, $USER)) {
        if(!is_siteadmin()){
        $CFG->custommenuitems = "Trainer Management | /local/trainer/trainer_manage.php";
        }
    }

    if (has_capability('local/pocschool:studentrm', $context, $USER)) {
        if(!is_siteadmin()){
        $CFG->custommenuitems = "Student Management | /local/students/student_manage.php";
        }
    }

    if (has_capability('local/pocschool:studentrm', $context, $USER) && has_capability('local/pocschool:trainerrm', $context, $USER)) {
        if(!is_siteadmin()){
        $CFG->custommenuitems = "Trainer Management | /local/trainer/trainer_manage.php
                                Student Management | /local/students/student_manage.php";
        }

    }
}
