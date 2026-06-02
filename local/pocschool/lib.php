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

    if (has_capability('local/pocschool:view', $context, $USER) ||
            local_regionalpoc_is_regional_manager_user((int) $USER->id)) {
        
        if(!is_siteadmin()){
        $CFG->custommenuitems = "Alloted School | /local/pocschool
                        School Management | /local/school/index.php
                        Trainer Management | /local/trainer/trainer_manage.php
                        ARM Management | /local/regionalpoc/rm_arm_manage.php
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

    $trainer = $DB->get_record('trainer', ['userid' => $user->id], 'schoolid', IGNORE_MULTIPLE);
    if ($trainer) {
        $schoolname = 'Not Assigned';
        if (!empty($trainer->schoolid)) {
            $categoryname = $DB->get_field('course_categories', 'name', ['id' => $trainer->schoolid]);
            if (!empty($categoryname)) {
                $schoolname = format_string($categoryname);
            }
        }

        $node = new core_user\output\myprofile\node(
            'contact',
            'schoolname',
            'Assigned School',
            'email',
            null,
            $schoolname
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
        return;
    }

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

    $schoolnames = [];
    if (!empty($schoolids)) {
        list($insql, $params) = $DB->get_in_or_equal($schoolids, SQL_PARAMS_NAMED, 'profileschool');
        $schools = $DB->get_records_sql(
            "SELECT cc.id, COALESCE(s.school_name, cc.name) AS schoolname
               FROM {course_categories} cc
          LEFT JOIN {school} s ON s.course_cat_id = cc.id
              WHERE cc.id {$insql}
           ORDER BY schoolname",
            $params
        );

        foreach ($schools as $school) {
            $schoolnames[] = format_string($school->schoolname);
        }
    }

    $schoolcontent = 'Not Assigned';
    if (!empty($schoolnames)) {
        $items = '';
        foreach ($schoolnames as $schoolname) {
            $items .= html_writer::tag('li', $schoolname);
        }
        $schoolcontent = html_writer::tag('ol', $items);
    }

    $node = new core_user\output\myprofile\node(
        'contact',
        'schoolname',
        'School',
        'email',
        null,
        $schoolcontent
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
