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
    ], $weeklyloginflags);
}
