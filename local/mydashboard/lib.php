<?php

defined('MOODLE_INTERNAL') || die();

function local_mydashboard_format_kpi_percent(float $value): string {
    return (string) round($value) . '%';
}

function local_mydashboard_format_streak_login_date(int $logindate): string {
    if ($logindate <= 0) {
        return 'Never';
    }

    $date = DateTimeImmutable::createFromFormat('!Ymd', (string) $logindate);
    if (!$date) {
        return 'Never';
    }

    return $date->format('M j, Y');
}

function local_mydashboard_get_weekly_login_flags(int $userid): array {
    global $DB, $USER;

    $flags = [
        'mon_logged' => false,
        'tue_logged' => false,
        'wed_logged' => false,
        'thu_logged' => false,
        'fri_logged' => false,
        'sat_logged' => false,
        'sun_logged' => false,
    ];

    if ($userid <= 0 || !$DB->get_manager()->table_exists('local_mydashboard_streak_log')) {
        return $flags;
    }

    $user = ((int) $USER->id === $userid)
        ? $USER
        : core_user::get_user($userid, 'id, timezone', IGNORE_MISSING);
    if (!$user) {
        return $flags;
    }

    $today = (new DateTimeImmutable('now', core_date::get_user_timezone_object($user)))->setTime(0, 0);
    $weekstart = $today->modify('-' . ((int) $today->format('N') - 1) . ' days');
    $daykeys = ['mon_logged', 'tue_logged', 'wed_logged', 'thu_logged', 'fri_logged', 'sat_logged', 'sun_logged'];
    $dates = [];

    foreach ($daykeys as $offset => $key) {
        $dates[(int) $weekstart->modify('+' . $offset . ' days')->format('Ymd')] = $key;
    }

    $weekend = (int) $weekstart->modify('+6 days')->format('Ymd');
    $logindates = $DB->get_fieldset_sql(
        "SELECT DISTINCT logindate
           FROM {local_mydashboard_streak_log}
          WHERE userid = :userid
            AND logindate >= :weekstart
            AND logindate <= :weekend",
        [
            'userid' => $userid,
            'weekstart' => (int) $weekstart->format('Ymd'),
            'weekend' => $weekend,
        ]
    );

    foreach ($logindates as $logindate) {
        $logindate = (int) $logindate;
        if (isset($dates[$logindate])) {
            $flags[$dates[$logindate]] = true;
        }
    }

    return $flags;
}

function local_mydashboard_get_student_timetable_context(stdClass $student): array {
    global $DB;

    $days = [
        'Monday' => 'Mon',
        'Tuesday' => 'Tue',
        'Wednesday' => 'Wed',
        'Thursday' => 'Thu',
        'Friday' => 'Fri',
        'Saturday' => 'Sat',
    ];
    $periods = range(1, 9);
    $daylookup = [];
    $rows = [];
    $hasitems = false;

    foreach ($days as $day => $shortday) {
        $daylookup[strtolower($day)] = $day;
        $row = [
            'day' => $day,
            'shortday' => $shortday,
            'periods' => [],
        ];
        foreach ($periods as $period) {
            $row['periods'][$period] = [
                'periodnumber' => $period,
                'coursename' => '—',
                'hasclass' => false,
            ];
        }
        $rows[$day] = $row;
    }

    $schoolid = (int) ($student->schoolid ?? 0);
    $gradeid = (int) ($student->gradeid ?? 0);
    if ($schoolid > 0 && $gradeid > 0 && $DB->get_manager()->table_exists('timetable')) {
        list($periodsql, $periodparams) = $DB->get_in_or_equal(array_map('strval', $periods), SQL_PARAMS_NAMED, 'timetableperiod');
        $records = $DB->get_recordset_sql(
            "SELECT day, period
               FROM {timetable}
              WHERE schoolid = :schoolid
                AND gradeid = :gradeid
                AND period IS NOT NULL
                AND period <> ''
                AND period {$periodsql}
           GROUP BY day, period
           ORDER BY day, period",
            [
                'schoolid' => $schoolid,
                'gradeid' => $gradeid,
            ] + $periodparams
        );

        foreach ($records as $record) {
            $day = $daylookup[strtolower(trim((string) $record->day))] ?? null;
            $period = (int) trim((string) $record->period);
            if ($day === null || $period < 1 || $period > 9) {
                continue;
            }

            $rows[$day]['periods'][$period]['coursename'] = 'Scheduled';
            $rows[$day]['periods'][$period]['hasclass'] = true;
            $hasitems = true;
        }
        $records->close();
    }

    foreach ($rows as &$row) {
        $row['periods'] = array_values($row['periods']);
    }
    unset($row);

    return [
        'timetable' => [
            'hasitems' => $hasitems,
            'days' => array_values($rows),
        ],
    ];
}

function local_mydashboard_get_learning_path_course_mapping(int $schoolid, int $gradeid): ?stdClass {
    global $DB;

    if ($schoolid <= 0 || $gradeid <= 0 || !$DB->get_manager()->table_exists('poc_copy_course')) {
        return null;
    }

    $mapping = $DB->get_record_sql(
        'SELECT pcc.id AS mappingid,
                CAST(pcc.schoolid AS UNSIGNED) AS schoolid,
                CAST(pcc.gradeid AS UNSIGNED) AS gradeid,
                c.id AS courseid,
                c.fullname AS coursename
           FROM {poc_copy_course} pcc
           JOIN {course} c ON c.id = CAST(pcc.courseid AS UNSIGNED)
          WHERE CAST(pcc.schoolid AS UNSIGNED) = :schoolid
            AND CAST(pcc.gradeid AS UNSIGNED) = :gradeid
       ORDER BY CAST(pcc.status AS UNSIGNED) DESC, pcc.id DESC',
        [
            'schoolid' => $schoolid,
            'gradeid' => $gradeid,
        ],
        IGNORE_MULTIPLE
    );

    return $mapping ?: null;
}

function local_mydashboard_get_student_learning_path_context(stdClass $student): array {
    global $DB;

    $schoolid = (int) ($student->schoolid ?? 0);
    $gradeid = (int) ($student->gradeid ?? 0);
    $courseid = 0;
    $sections = [];
    $progressbysection = [];

    $mapping = local_mydashboard_get_learning_path_course_mapping($schoolid, $gradeid);
    if ($mapping) {
        $courseid = (int) $mapping->courseid;
    }

    if ($courseid > 0 && $DB->get_manager()->table_exists('course_sections')) {
        $sectionrecords = $DB->get_records_sql(
            "SELECT id, section, name, visible
               FROM {course_sections}
              WHERE course = :courseid
                AND section > 0
           ORDER BY section",
            ['courseid' => $courseid]
        );

        foreach ($sectionrecords as $section) {
            $sectionnumber = (int) $section->section;
            $sections[] = [
                'sectionid' => (int) $section->id,
                'sectionnumber' => $sectionnumber,
                'sectionname' => !empty($section->name) ? format_string($section->name) : 'Session ' . $sectionnumber,
                'visible' => (int) $section->visible,
            ];
        }
    }

    if ($courseid > 0 && $DB->get_manager()->table_exists('local_session_progress')) {
        $progressrecords = $DB->get_records_sql(
            "SELECT sectionid, status, completeddays, timecompleted
               FROM {local_session_progress}
              WHERE schoolid = :schoolid
                AND gradeid = :gradeid
                AND courseid = :courseid",
            [
                'schoolid' => $schoolid,
                'gradeid' => $gradeid,
                'courseid' => $courseid,
            ]
        );

        foreach ($progressrecords as $progress) {
            $progressbysection[(int) $progress->sectionid] = [
                'status' => !empty($progress->status) ? strtolower((string) $progress->status) : 'pending',
                'completeddays' => (int) $progress->completeddays,
                'timecompleted' => (int) $progress->timecompleted,
            ];
        }
    }

    $latestcompletedindex = -1;
    $hasinprogress = false;
    foreach ($sections as $index => $section) {
        $progress = $progressbysection[$section['sectionid']] ?? null;
        $status = $progress['status'] ?? 'pending';
        if ($status === 'completed') {
            $latestcompletedindex = $index;
        } else if ($status === 'inprogress') {
            $hasinprogress = true;
        }
    }

    $fallbackactiveindex = -1;
    if (!$hasinprogress) {
        foreach ($sections as $index => $section) {
            if ($index <= $latestcompletedindex) {
                continue;
            }
            $progress = $progressbysection[$section['sectionid']] ?? null;
            if (($progress['status'] ?? 'pending') !== 'completed') {
                $fallbackactiveindex = $index;
                break;
            }
        }
    }

    $nodes = [];
    $completedcount = 0;
    $activecount = 0;
    $lockedcount = 0;
    $activesession = '—';
    $previousstate = '';

    foreach ($sections as $index => $section) {
        $progress = $progressbysection[$section['sectionid']] ?? null;
        $storedstatus = $progress['status'] ?? 'pending';
        $state = 'lock';
        $statustext = 'LOCKED';
        $icon = 'fa-lock';

        if ($storedstatus === 'completed') {
            $state = 'done';
            $statustext = 'DONE';
            $icon = 'fa-check';
            $completedcount++;
        } else if ($storedstatus === 'inprogress' || $index === $fallbackactiveindex) {
            $state = 'act';
            $statustext = 'ACTIVE';
            $icon = 'fa-flask';
            $activecount++;
            if ($activesession === '—') {
                $activesession = 'Session ' . $section['sectionnumber'];
            }
        } else {
            $lockedcount++;
        }

        $nodes[] = [
            'hasconnector' => $index > 0,
            'connectorclass' => $previousstate !== '' ? $previousstate : $state,
            'nodeclass' => $state,
            'statusclass' => $state,
            'iconclass' => $icon,
            'sessionnumber' => $section['sectionnumber'],
            'sectionname' => $section['sectionname'],
            'statustext' => $statustext,
        ];
        $previousstate = $state;
    }

    return [
        'learningpath' => [
            'gradeid' => $gradeid,
            'courseid' => $courseid,
            'hasitems' => !empty($nodes),
            'total_sections' => count($sections),
            'completed_count' => $completedcount,
            'active_count' => $activecount,
            'active_session' => $activesession,
            'locked_count' => $lockedcount,
            'nodes' => $nodes,
        ],
    ];
}

function local_mydashboard_get_student_progress_context(stdClass $student): array {
    global $DB, $USER;

    $schoolid = (int) ($student->schoolid ?? 0);
    $gradeid = (int) ($student->gradeid ?? 0);
    $studentuserid = (int) ($student->userid ?? $USER->id);

    $attendancepresent = 0;
    $attendancetotal = 0;
    if (!empty($schoolid) && !empty($gradeid) &&
            $DB->get_manager()->table_exists('attendance') &&
            $DB->get_manager()->table_exists('attendance_student')) {
        $monthstart = mktime(0, 0, 0, (int) date('n'), 1, (int) date('Y'));
        $nextmonthstart = mktime(0, 0, 0, (int) date('n') + 1, 1, (int) date('Y'));

        $attendance = $DB->get_record_sql(
            "SELECT COUNT(ast.id) AS totalcount,
                    SUM(CASE WHEN UPPER(ast.status) = 'P' THEN 1 ELSE 0 END) AS presentcount
               FROM {attendance_student} ast
               JOIN {attendance} att ON att.id = ast.attendanceid
              WHERE ast.studentid = :userid
                AND att.schoolid = :schoolid
                AND att.gradeid = :gradeid
                AND att.date >= :monthstart
                AND att.date < :nextmonthstart
                AND UPPER(ast.status) IN ('P', 'A')",
            [
                'userid' => $studentuserid,
                'schoolid' => $schoolid,
                'gradeid' => $gradeid,
                'monthstart' => $monthstart,
                'nextmonthstart' => $nextmonthstart,
            ]
        );
        if ($attendance) {
            $attendancepresent = (int) $attendance->presentcount;
            $attendancetotal = (int) $attendance->totalcount;
        }
    }
    $attendancepercent = $attendancetotal > 0 ? ($attendancepresent / $attendancetotal) * 100 : 0;

    $assessmentcompleted = 0;
    $assessmenttotal = 0;
    $mappedcourseids = [];
    if (!empty($schoolid) && !empty($gradeid) && $DB->get_manager()->table_exists('poc_copy_course')) {
        $mappedcourseids = array_map('intval', $DB->get_fieldset_sql(
            "SELECT DISTINCT CAST(pcc.courseid AS UNSIGNED)
               FROM {poc_copy_course} pcc
              WHERE CAST(pcc.schoolid AS UNSIGNED) = :schoolid
                AND CAST(pcc.gradeid AS UNSIGNED) = :gradeid",
            [
                'schoolid' => $schoolid,
                'gradeid' => $gradeid,
            ]
        ));
        $mappedcourseids = array_values(array_unique(array_filter($mappedcourseids)));
    }

    if (!empty($mappedcourseids)) {
        list($coursesql, $courseparams) = $DB->get_in_or_equal($mappedcourseids, SQL_PARAMS_NAMED, 'studentdashcourse');
        $basefrom = "FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module
                     AND m.name = 'quiz'";
        $basewhere = "cm.course {$coursesql}";

        $assessmenttotal = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT cm.instance)
               {$basefrom}
              WHERE {$basewhere}",
            $courseparams
        );

        $attemptparams = $courseparams + [
            'studentdashuserid' => $studentuserid,
            'studentdashstate' => 'finished',
        ];
        $assessmentcompleted = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT qa.quiz)
               FROM {quiz_attempts} qa
               JOIN {course_modules} cm ON cm.instance = qa.quiz
               JOIN {modules} m ON m.id = cm.module
                AND m.name = 'quiz'
              WHERE {$basewhere}
                AND qa.userid = :studentdashuserid
                AND qa.state = :studentdashstate",
            $attemptparams
        );
        $assessmentcompleted = min($assessmentcompleted, $assessmenttotal);
    }
    $assessmentpercent = $assessmenttotal > 0 ? ($assessmentcompleted / $assessmenttotal) * 100 : 0;
    $overallprogress = ($attendancepercent * 0.3) + ($assessmentpercent * 0.7);
    $currentstreak = 0;
    $longeststreak = 0;
    $lastlogindate = 'Never';
    if ($DB->get_manager()->table_exists('local_mydashboard_streak')) {
        $streak = $DB->get_record('local_mydashboard_streak', ['userid' => $studentuserid],
            'currentstreak, longeststreak, lastlogindate');
        if ($streak) {
            $currentstreak = (int) $streak->currentstreak;
            $longeststreak = (int) $streak->longeststreak;
            $lastlogindate = local_mydashboard_format_streak_login_date((int) $streak->lastlogindate);
        }
    }
    $weeklyloginflags = local_mydashboard_get_weekly_login_flags($studentuserid);
    $timetablecontext = local_mydashboard_get_student_timetable_context($student);
    $learningpathcontext = local_mydashboard_get_student_learning_path_context($student);

    return array_merge([
        'overallprogressnumber' => (string) round($overallprogress),
        'overallprogress' => local_mydashboard_format_kpi_percent($overallprogress),
        'attendancepercentnumber' => (string) round($attendancepercent),
        'attendancepercent' => local_mydashboard_format_kpi_percent($attendancepercent),
        'attendancepresentcount' => $attendancepresent,
        'attendancetotalcount' => $attendancetotal,
        'assessmentpercentnumber' => (string) round($assessmentpercent),
        'assessmentpercent' => local_mydashboard_format_kpi_percent($assessmentpercent),
        'assessmentattemptedcount' => $assessmentcompleted,
        'assessmentcompletedcount' => $assessmentcompleted,
        'assessmenttotalcount' => $assessmenttotal,
        'currentstreak' => $currentstreak,
        'longeststreak' => $longeststreak,
        'lastlogindate' => $lastlogindate,
    ], $weeklyloginflags, $timetablecontext, $learningpathcontext);
}
