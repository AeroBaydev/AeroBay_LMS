<?php
// lib.php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');

function local_pocschool_extend_navigation(global_navigation $navigation) {
    global $CFG, $USER, $PAGE;

    $context = context_system::instance();

    if (local_pocschool_is_trainer_user()) {
        $CFG->custommenuitems = "Student Management | /local/students/student_manage.php
                        Attendance Management | /local/attendance_new/index.php
                        Time Table Management | /local/timetable/index.php";
        return;
    }

    if (has_capability('local/pocschool:view', $context, $USER)) {
        
        if(!is_siteadmin()){
        $CFG->custommenuitems = "Alloted School | /local/pocschool
                        Trainer Management | /local/trainer/trainer_manage.php
                        RM / ARM Management | /local/regionalpoc/rm_arm_manage.php
                        Student Management | /local/students/student_manage.php
                        Attendance Management | /local/attendance_new/index.php
                        Time Table Management | /local/timetable/index.php";
        }
    }
    if (has_capability('local/pocschool:trainerrm', $context, $USER)) {
        if(!is_siteadmin()){
        $CFG->custommenuitems = "Trainer Management | /local/trainer/trainer_manage.php
                                Attendance Management | /local/attendance_new/index.php
                                Time Table Management | /local/timetable/index.php";
        }
    }

    if (has_capability('local/pocschool:studentrm', $context, $USER)) {
        if(!is_siteadmin()){
        $CFG->custommenuitems = "Student Management | /local/students/student_manage.php
                                Attendance Management | /local/attendance_new/index.php
                                Time Table Management | /local/timetable/index.php";
        }
    }

    if (has_capability('local/pocschool:studentrm', $context, $USER) && has_capability('local/pocschool:trainerrm', $context, $USER)) {
        if(!is_siteadmin()){
        $CFG->custommenuitems = "Trainer Management | /local/trainer/trainer_manage.php
                                Student Management | /local/students/student_manage.php
                                Attendance Management | /local/attendance_new/index.php
                                Time Table Management | /local/timetable/index.php";
        }

    }
}

function local_pocschool_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $DB;

    $schoolids = [];

    $assignedschoolids = $DB->get_fieldset_select('schoolassign', 'schoolid', 'userid = ?', [$user->id]);
    $schoolids = array_merge($schoolids, array_map('intval', $assignedschoolids));

    if ($student = $DB->get_record('student', ['userid' => $user->id], 'schoolid', IGNORE_MULTIPLE)) {
        if (!empty($student->schoolid)) {
            $schoolids[] = (int)$student->schoolid;
        }
    }

    if ($trainer = $DB->get_record('trainer', ['userid' => $user->id], 'schoolid', IGNORE_MULTIPLE)) {
        if (!empty($trainer->schoolid)) {
            $schoolids[] = (int)$trainer->schoolid;
        }
    }

    $schoolids = array_values(array_unique(array_filter($schoolids)));
    if (empty($schoolids)) {
        return;
    }

    list($insql, $params) = $DB->get_in_or_equal($schoolids, SQL_PARAMS_NAMED, 'profileschool');
    $schools = $DB->get_records_sql(
        "SELECT cc.id, COALESCE(s.school_name, cc.name) AS schoolname
           FROM {course_categories} cc
      LEFT JOIN {school} s ON s.course_cat_id = cc.id
          WHERE cc.id {$insql}
       ORDER BY schoolname",
        $params
    );

    if (empty($schools)) {
        return;
    }

    $schoolnames = [];
    foreach ($schools as $school) {
        $schoolnames[] = format_string($school->schoolname);
    }

    $node = new core_user\output\myprofile\node(
        'contact',
        'schoolname',
        'School',
        'email',
        null,
        implode(', ', $schoolnames)
    );

    $categories = $tree->categories;
    if (isset($categories['contact'])) {
        $contactnodes = $categories['contact']->nodes;
        if (!isset($contactnodes['schoolname'])) {
            $categories['contact']->add_node($node);
        }
    } else {
        $tree->add_node($node);
    }
}
