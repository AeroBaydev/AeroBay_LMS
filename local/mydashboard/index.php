<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/enrol/manual/lib.php');
require_once($CFG->dirroot . '/local/dashboard/lib.php');
require_once($CFG->dirroot . '/local/mydashboard/lib.php');

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
} else if ($studentrec = $DB->get_record('student', ['userid' => $USER->id])) {
    $somdata = array_merge($somdata, local_mydashboard_get_student_progress_context($studentrec));
    if (!isset($somdata['schoolname']) && !empty($studentrec->schoolid)) {
        $schoolcat = $DB->get_record('course_categories', ['id' => $studentrec->schoolid], 'name');
        if ($schoolcat) {
            $somdata['schoolname'] = format_string($schoolcat->name);
        }
    }
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
    // Course name: valid poc_copy_course mapping → course.fullname.
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
                    t.schoolid, t.gradeid,
                    cc.name AS gradename
               FROM {timetable} t
          LEFT JOIN {course_categories} cc ON cc.id = t.gradeid
              WHERE t.schoolid = :schoolid
           ORDER BY CAST(t.period AS UNSIGNED)',
            ['schoolid' => $timetable_schoolid]
        );

        $validcoursebygrade = [];
        foreach ($all_rows as $row) {
            $mappingkey = ((int) $row->schoolid) . ':' . ((int) $row->gradeid);
            if (array_key_exists($mappingkey, $validcoursebygrade)) {
                continue;
            }

            $mapping = local_mydashboard_get_learning_path_course_mapping((int) $row->schoolid, (int) $row->gradeid);
            $validcoursebygrade[$mappingkey] = $mapping ? [
                'courseid' => (int) $mapping->courseid,
                'coursename' => format_string($mapping->coursename),
            ] : null;
        }

        $courseids = [];
        foreach ($all_rows as $row) {
            $mappingkey = ((int) $row->schoolid) . ':' . ((int) $row->gradeid);
            $courseid = (int) ($validcoursebygrade[$mappingkey]['courseid'] ?? 0);
            if ($courseid > 0) {
                $courseids[$courseid] = $courseid;
            }
        }

        $sectionsbycourse = [];
        $sectionidsbycourse = [];
        $modulecountsbycourse = [];
        if (!empty($courseids)) {
            list($coursesql, $courseparams) = $DB->get_in_or_equal(array_values($courseids), SQL_PARAMS_NAMED, 'ttcourse');
            $sectionrows = $DB->get_records_sql(
                "SELECT id, course, section, name, visible, sequence
                   FROM {course_sections}
                  WHERE course {$coursesql}
                    AND section > 0
               ORDER BY course, section",
                $courseparams
            );

            foreach ($sectionrows as $section) {
                $sectioncourseid = (int) $section->course;
                if (!isset($sectionsbycourse[$sectioncourseid])) {
                    $sectionsbycourse[$sectioncourseid] = [];
                    $sectionidsbycourse[$sectioncourseid] = [];
                }

                $sectionnumber = (int) $section->section;
                $sectionidsbycourse[$sectioncourseid][] = (int) $section->id;
                $sectionsbycourse[$sectioncourseid][] = [
                    'sectionid' => (int) $section->id,
                    'sectionnumber' => $sectionnumber,
                    'sectionname' => !empty($section->name) ? format_string($section->name) : 'Session ' . $sectionnumber,
                    'visible' => (int) $section->visible,
                ];
            }

            $modulerows = $DB->get_records_sql(
                "SELECT cm.course, COUNT(cm.id) AS modulecount
                   FROM {course_modules} cm
                   JOIN {course_sections} cs ON cs.id = cm.section
                  WHERE cm.course {$coursesql}
                    AND cs.course = cm.course
                    AND cs.section > 0
                    AND FIND_IN_SET(cm.id, cs.sequence)
               GROUP BY cm.course",
                $courseparams
            );
            foreach ($modulerows as $modulerow) {
                $modulecountsbycourse[(int) $modulerow->course] = (int) $modulerow->modulecount;
            }
        }

        $progressbysection = [];
        if (!empty($courseids) && $DB->get_manager()->table_exists('local_session_progress')) {
            list($progresscoursesql, $progresscourseparams) = $DB->get_in_or_equal(array_values($courseids), SQL_PARAMS_NAMED, 'ttprogresscourse');
            $progressrows = $DB->get_records_sql(
                "SELECT id, schoolid, gradeid, courseid, sectionid, trainerid, status, completeddays, timecompleted
                   FROM {local_session_progress}
                  WHERE schoolid = :progressschoolid
                    AND courseid {$progresscoursesql}",
                ['progressschoolid' => $timetable_schoolid] + $progresscourseparams
            );

            foreach ($progressrows as $progress) {
                $progresskey = implode(':', [
                    (int) $progress->schoolid,
                    (int) $progress->gradeid,
                    (int) $progress->courseid,
                    (int) $progress->sectionid,
                ]);
                $progressbysection[$progresskey] = [
                    'trainerid' => (int) $progress->trainerid,
                    'status' => !empty($progress->status) ? $progress->status : 'pending',
                    'completeddays' => (int) $progress->completeddays,
                    'timecompleted' => (int) $progress->timecompleted,
                ];
            }
        }

        // Organise by day
        foreach ($week_days as $wd) {
            $timetable_all[$wd] = [];
        }
        $missingmappinglogged = [];
        $sessiondebuglogged = [];
        foreach ($all_rows as $row) {
            $day_key = $row->day;
            if (!isset($timetable_all[$day_key])) {
                $timetable_all[$day_key] = [];
            }
            $period_num = trim((string) $row->period);
            $mappingkey = ((int) $row->schoolid) . ':' . ((int) $row->gradeid);
            $coursemapping = $validcoursebygrade[$mappingkey] ?? null;
            $courseid = (int) ($coursemapping['courseid'] ?? 0);
            $coursename = !empty($coursemapping['coursename']) ? $coursemapping['coursename'] : 'No course mapped';
            $sessions = [];
            if ($courseid > 0) {
                foreach ($sectionsbycourse[$courseid] ?? [] as $section) {
                    $progresskey = implode(':', [
                        (int) $row->schoolid,
                        (int) $row->gradeid,
                        $courseid,
                        (int) $section['sectionid'],
                    ]);
                    $progress = $progressbysection[$progresskey] ?? null;
                    $sessions[] = $section + [
                        'status' => $progress['status'] ?? 'pending',
                        'completeddays' => $progress['completeddays'] ?? 0,
                        'timecompleted' => $progress['timecompleted'] ?? 0,
                        'trainerid' => $progress['trainerid'] ?? 0,
                    ];
                }
                if (!isset($sessiondebuglogged[$mappingkey])) {
                    $sessiondebuglogged[$mappingkey] = true;
                    error_log('local_mydashboard session extraction debug');
                    error_log('mappingkey=' . $mappingkey);
                    error_log('schoolid=' . (int) $row->schoolid);
                    error_log('gradeid=' . (int) $row->gradeid);
                    error_log('resolved courseid=' . $courseid);
                    error_log('section count=' . count($sectionsbycourse[$courseid] ?? []));
                    error_log('module count=' . (int) ($modulecountsbycourse[$courseid] ?? 0));
                    error_log('section ids loaded=' . implode(',', $sectionidsbycourse[$courseid] ?? []));
                    error_log('final sessions array count=' . count($sessions));
                }
            } else if (!isset($missingmappinglogged[$mappingkey])) {
                $missingmappinglogged[$mappingkey] = true;
                $schoolid = (int) $row->schoolid;
                $gradeid = (int) $row->gradeid;
                error_log('local_mydashboard timetable course mapping missing');
                error_log('schoolid=' . $schoolid);
                error_log('gradeid=' . $gradeid);
                error_log('mappingkey=' . $mappingkey);
                error_log('resolved courseid=' . $courseid);
                error_log('sessions count=' . count($sessions));
            }

            $timetable_all[$day_key][] = [
                'period'     => isset($period_roman[$period_num])
                                    ? 'Period ' . $period_roman[$period_num]
                                    : 'Period ' . $period_num,
                'gradename'  => !empty($row->gradename)  ? $row->gradename  : '—',
                'coursename' => $coursename,
                'courseid'   => $courseid,
                'sessions'   => $sessions,
                'mappingdebug' => [
                    'courseid' => $courseid,
                    'courseexists' => $courseid > 0,
                    'sectioncount' => count($sessions),
                ],
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
