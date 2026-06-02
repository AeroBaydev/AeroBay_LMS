<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/enrol/manual/lib.php');
require_once($CFG->dirroot . '/local/dashboard/lib.php');

global $DB, $USER, $CFG;
require_login();


$PAGE->set_pagelayout('course');
$somdata = [];
$somdata['config'] = ['wwwroot' => $CFG->wwwroot];

function local_mydashboard_enrol_trainer_course($courseid, $userid) {
    global $DB;

    $coursecontext = context_course::instance($courseid, IGNORE_MISSING);
    if (!$coursecontext || is_enrolled($coursecontext, $userid)) {
        return;
    }

    $manualplugin = enrol_get_plugin('manual');
    if (!$manualplugin) {
        return;
    }

    $manualinstance = $DB->get_record('enrol', [
        'courseid' => $courseid,
        'enrol' => 'manual',
        'status' => ENROL_INSTANCE_ENABLED
    ]);
    if (!$manualinstance) {
        return;
    }

    $role = $DB->get_record('role', ['shortname' => 'trainer']);
    if (!$role) {
        $role = $DB->get_record('role', ['shortname' => 'teacher']);
    }
    if (!$role) {
        $role = $DB->get_record('role', ['shortname' => 'editingteacher']);
    }
    if ($role) {
        $manualplugin->enrol_user($manualinstance, $userid, $role->id, time());
    }
}

$trainer = $DB->get_record('trainer', ['userid' => $USER->id]);
if ($trainer) {
    $courses = [];

    if ($DB->get_manager()->table_exists('trainer_course_mapping')) {
        $courses = $DB->get_records_sql(
            "SELECT c.id,
                    c.fullname,
                    cc.name AS gradename,
                    schoolcat.name AS schoolname
               FROM {trainer_course_mapping} tcm
               JOIN {course} c ON c.id = tcm.courseid
          LEFT JOIN {course_categories} cc ON cc.id = tcm.gradeid
          LEFT JOIN {course_categories} schoolcat ON schoolcat.id = tcm.schoolid
              WHERE tcm.traineruserid = :userid
                AND tcm.status = 1
                AND c.visible = 1
           ORDER BY schoolcat.name, cc.sortorder, cc.name, c.fullname",
            ['userid' => $USER->id]
        );
    }

    $trainerschoolid = !empty($trainer->schoolid) ? $trainer->schoolid : $DB->get_field('schoolassign', 'schoolid', ['userid' => $USER->id]);
    if (empty($courses) && !empty($trainerschoolid)) {
        $courses = $DB->get_records_sql(
            "SELECT c.id,
                    c.fullname,
                    grade.name AS gradename,
                    schoolcat.name AS schoolname
               FROM {poc_copy_course} pcc
               JOIN {course} c ON c.id = pcc.courseid
          LEFT JOIN {course_categories} grade ON grade.id = pcc.gradeid
          LEFT JOIN {course_categories} schoolcat ON schoolcat.id = pcc.schoolid
              WHERE pcc.pocid = :pocid
                AND pcc.schoolid = :schoolid
                AND pcc.status = 1
                AND c.visible = 1
           ORDER BY schoolcat.name, grade.sortorder, grade.name, c.fullname",
            ['pocid' => $trainer->createdby, 'schoolid' => $trainerschoolid]
        );
    }

    foreach ($courses as $course) {
        local_mydashboard_enrol_trainer_course($course->id, $USER->id);
        $somdata['trainercourses'][] = [
            'coursename' => format_string($course->fullname),
            'gradename' => !empty($course->gradename) ? format_string($course->gradename) : '',
            'schoolname' => !empty($course->schoolname) ? format_string($course->schoolname) : '',
            'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
        ];
    }

    $somdata['istrainer'] = true;
    $somdata['hastrainercourses'] = !empty($somdata['trainercourses']);
}
 
echo'';



echo $OUTPUT->header();
if (is_siteadmin()) {
    echo $OUTPUT->render_from_template('local_mydashboard/admindashboard', array_merge($somdata, local_dashboard_get_admin_stats_context()));
} else if (local_dashboard_is_pocschool_user((int) $USER->id)) {
    $scope = local_dashboard_get_pocschool_scope((int) $USER->id);
    echo $OUTPUT->render_from_template('local_mydashboard/admindashboard', array_merge($somdata, local_dashboard_get_admin_stats_context($scope)));
} else if ($DB->record_exists('student', ['userid' => $USER->id])) {
    echo $OUTPUT->render_from_template('local_mydashboard/studentdashboard', $somdata);
} else {
    echo $OUTPUT->render_from_template('local_mydashboard/mydashboard', $somdata);
}
echo $OUTPUT->footer();
