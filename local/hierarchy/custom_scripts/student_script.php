<?php

require_once(__DIR__ . '/../../../config.php');
global $CFG, $DB, $USER;

$prefixes = $DB->get_records_sql_menu("SELECT id, tableprefix FROM mdl_custom_tenants WHERE status!=0"); //get undeleted tenant prefixes

array_push($prefixes, "mdl_"); //add mdl_ also

foreach($prefixes as $prefix) { //for each tenant

    $sql = "SELECT id, userid, enrolid FROM " . $prefix . "user_enrolments";

    $user_enrolments = $DB->get_records_sql($sql);


    foreach($user_enrolments as $user_enrolment) { //for each user enrolled in a course

        $enrol_record = $DB->get_record_sql("SELECT * FROM " . $prefix . "enrol WHERE id=$user_enrolment->enrolid"); //get enrolment record

        $context_record = $DB->get_record_sql("SELECT * FROM " . $prefix . "context WHERE contextlevel=50 AND instanceid=$enrol_record->courseid"); //get context of course

        $role_assignment_result = $DB->get_records_sql("SELECT * FROM " . $prefix . "role_assignments WHERE contextid=$context_record->id AND userid=$user_enrolment->userid"); //get all roles of the user in that course (course's context)


        if( count($role_assignment_result)==0 ) { //if user has no role in that course

            $role_record = $DB->get_record_sql("SELECT * FROM " . $prefix . "role WHERE shortname='student'");
            $time = time();

            $DB->execute("INSERT INTO " . $prefix . "role_assignments(roleid, contextid, userid, timemodified, modifierid, component, itemid, sortorder) VALUES ($role_record->id, $context_record->id, $user_enrolment->userid, $time, 2, '', 0, 0)");

        } //if


    } //user_enrolment loop


} //prefix loop
