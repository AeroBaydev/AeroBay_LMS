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
} else if ($trainerrec = $DB->get_record('trainer', ['userid' => $USER->id])) {
    // Trainer dashboard — isolated, UI demo only, no DB queries beyond role check.
    $somdata['config'] = ['wwwroot' => $CFG->wwwroot];
    $somdata['loggedinuserfullname'] = fullname($USER);
    
    $initials = '';
    if (!empty($USER->firstname)) { $initials .= mb_substr(trim($USER->firstname), 0, 1); }
    if (!empty($USER->lastname)) { $initials .= mb_substr(trim($USER->lastname), 0, 1); }
    $somdata['loggedinuserinitials'] = !empty($initials) ? mb_strtoupper($initials) : 'TR';
    
    $rolename = 'Trainer';
    if ($roles = $DB->get_records_sql("SELECT r.name, r.shortname FROM {role_assignments} ra JOIN {role} r ON ra.roleid = r.id WHERE ra.userid = :userid ORDER BY r.sortorder ASC", ['userid' => $USER->id], 0, 1)) {
        $role = reset($roles);
        $rolename = !empty($role->name) ? $role->name : ucfirst($role->shortname);
        if (stripos($rolename, 'teacher') !== false) {
            $rolename = 'Trainer';
        }
    }
    $somdata['loggedinuserrole'] = strtoupper($rolename);

    $somdata['hastrainerschool'] = false;
    
    $trainerschoolid = !empty($trainerrec->schoolid) ? $trainerrec->schoolid : $DB->get_field('schoolassign', 'schoolid', ['userid' => $USER->id]);
    if (!empty($trainerschoolid)) {
        $schoolcat = $DB->get_record('course_categories', ['id' => $trainerschoolid], 'id, name');
        if ($schoolcat) {
            $somdata['trainerschoolname'] = format_string($schoolcat->name);
            $somdata['hastrainerschool'] = true;
        }
    }
    
    $somdata['trainerstudentcount'] = 0;
    if (!empty($trainerschoolid)) {
        $somdata['trainerstudentcount'] = $DB->count_records('student', ['schoolid' => $trainerschoolid]);
    }

    // ── Today's Classes card + Schedule Modal — real timetable data ──────────────
    // Verified schema: timetable(id, schoolid, gradeid, period, day)
    // Period stored as varchar integer '1'–'9' → display as Roman numeral.
    // Grade name: course_categories.name via gradeid.
    // Course name: poc_copy_course (varchar schoolid/gradeid) → course.fullname.
    // No status column → completed always 0.
    $period_roman = ['1'=>'I','2'=>'II','3'=>'III','4'=>'IV','5'=>'V',
                     '6'=>'VI','7'=>'VII','8'=>'VIII','9'=>'IX','10'=>'X'];
    $week_days    = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    $today_day    = date('l');   // e.g. 'Wednesday'
    $timetable_schoolid = !empty($trainerschoolid) ? (int) $trainerschoolid : 0;

    $somdata['todayclassescount']  = 0;
    $somdata['todaycompletedcount'] = 0;
    $somdata['timetablejson']       = '[]';

    if ($timetable_schoolid > 0 && $DB->get_manager()->table_exists('timetable')) {
        // Build full-week schedule (all days) for the modal navigation
        $timetable_all = [];
        $all_rows = $DB->get_records_sql(
            'SELECT t.id, t.day, t.period,
                    cc.name AS gradename, c.fullname AS coursename
               FROM {timetable} t
          LEFT JOIN {course_categories} cc ON cc.id = t.gradeid
          LEFT JOIN {poc_copy_course} pcc
                 ON CAST(pcc.schoolid AS UNSIGNED) = t.schoolid
                AND CAST(pcc.gradeid  AS UNSIGNED) = t.gradeid
                AND pcc.status = 1
          LEFT JOIN {course} c ON c.id = pcc.courseid
              WHERE t.schoolid = :schoolid
           ORDER BY CAST(t.period AS UNSIGNED)',
            ['schoolid' => $timetable_schoolid]
        );

        // Organise by day
        foreach ($week_days as $wd) {
            $timetable_all[$wd] = [];
        }
        foreach ($all_rows as $row) {
            $day_key = $row->day;
            if (!isset($timetable_all[$day_key])) {
                $timetable_all[$day_key] = [];
            }
            $period_num = trim((string) $row->period);
            $timetable_all[$day_key][] = [
                'period'     => isset($period_roman[$period_num])
                                    ? 'Period ' . $period_roman[$period_num]
                                    : 'Period ' . $period_num,
                'gradename'  => !empty($row->gradename)  ? $row->gradename  : '—',
                'coursename' => !empty($row->coursename) ? $row->coursename : '—',
            ];
        }

        // Today's count
        $somdata['todayclassescount'] = count($timetable_all[$today_day] ?? []);
        // No status field → completed = 0
        $somdata['todaycompletedcount'] = 0;

        // JSON for modal (safe for JS embed)
        $somdata['timetablejson'] = json_encode($timetable_all, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
    
    // ── Today's Attendance ────────────────────────────────────────────────────────
    $somdata['todayattendancepercent'] = 0;
    $somdata['todaypresentcount']      = 0;
    $somdata['todaytotalcount']        = 0;
    
    if ($timetable_schoolid > 0 && $DB->get_manager()->table_exists('attendance') && $DB->get_manager()->table_exists('attendance_student')) {
        $today_midnight = usergetmidnight(time());
        $next_midnight  = $today_midnight + 86400;
        
        $sql = "SELECT
                    COUNT(ast.id) AS total,
                    SUM(CASE WHEN ast.status = 'P' THEN 1 ELSE 0 END) AS present
                FROM {attendance} att
                JOIN {attendance_student} ast ON ast.attendanceid = att.id
                WHERE att.schoolid = :schoolid
                  AND att.date >= :midnight
                  AND att.date < :nextmidnight";
                  
        $params = [
            'schoolid' => $timetable_schoolid,
            'midnight' => $today_midnight,
            'nextmidnight' => $next_midnight
        ];
        
        $att_record = $DB->get_record_sql($sql, $params);
        if ($att_record && $att_record->total > 0) {
            $total   = (int) $att_record->total;
            $present = (int) $att_record->present;
            
            $somdata['todaytotalcount'] = $total;
            $somdata['todaypresentcount'] = $present;
            $somdata['todayattendancepercent'] = round(($present / $total) * 100);
        }
    }
    // ── End timetable block ───────────────────────────────────────────────────────
    echo $OUTPUT->render_from_template('local_mydashboard/trainerdashboard', $somdata);
} else {
    echo $OUTPUT->render_from_template('local_mydashboard/mydashboard', $somdata);
}
echo $OUTPUT->footer();
