<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/regionalpoc/lib.php');

/**
 * Build admin dashboard statistics from LMS data.
 *
 * @return array
 */
function local_dashboard_get_admin_stats_context(array $scope = []): array {
    global $DB, $CFG;

    $dbman = $DB->get_manager();
    $scope = local_dashboard_normalize_school_scope($scope);

    $stats = [
        'total_schools' => 0,
        'total_trainers' => 0,
        'total_pocs' => 0,
        'total_students' => 0,
        'active_courses' => 0,
        'total_schools_change' => 0,
        'total_trainers_change' => 0,
        'total_pocs_change' => 0,
        'total_students_change' => 0,
        'active_courses_change' => 0,
        'attendance_change' => 0,
        'attendance_present_today' => 0,
        'attendance_absent_today' => 0,
        'attendance_marked_today' => 0,
        'todays_attendance_percent' => 0,
        'total_logins_today' => 0,
    ];
    $daystart = usergetmidnight(time());
    $dayend = $daystart + DAYSECS - 1;
    $yearstart = strtotime('-12 months', $daystart);
    $quarterstart = strtotime('-4 months', $daystart);

    if ($dbman->table_exists('school')) {
        $schoolwhere = 'cc.visible = 1';
        $schoolparams = [];
        local_dashboard_apply_school_table_scope($schoolwhere, $schoolparams, $scope, 'sc', 'cc');

        $stats['total_schools'] = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT sc.id)
               FROM {school} sc
               JOIN {course_categories} cc ON sc.school_id = cc.idnumber
              WHERE {$schoolwhere}",
            $schoolparams
        );

        $schoolchangeparams = $schoolparams + ['yearstart' => $yearstart];
        $stats['total_schools_change'] = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT sc.id)
               FROM {school} sc
               JOIN {course_categories} cc ON sc.school_id = cc.idnumber
              WHERE {$schoolwhere}
                AND sc.timecreated >= :yearstart",
            $schoolchangeparams
        );
    }

    if ($dbman->table_exists('trainer')) {
        $trainerwhere = "u.deleted = 0
                AND u.suspended = 0";
        $trainerparams = [];
        local_dashboard_apply_trainer_school_scope($trainerwhere, $trainerparams, $scope, 't');

        $stats['total_trainers'] = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT t.userid)
               FROM {trainer} t
               JOIN {user} u ON u.id = t.userid
              WHERE {$trainerwhere}",
            $trainerparams
        );

        $trainerchangeparams = $trainerparams + ['quarterstart' => $quarterstart];
        $stats['total_trainers_change'] = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT t.userid)
               FROM {trainer} t
               JOIN {user} u ON u.id = t.userid
              WHERE {$trainerwhere}
                AND u.timecreated >= :quarterstart",
            $trainerchangeparams
        );
    }

    if (!empty($scope['is_school_scoped']) && $dbman->table_exists('regionalpoc')) {
        $pocwhere = "u.deleted = 0
                AND u.suspended = 0
                AND rp.usertype = :armusertype";
        $pocparams = ['armusertype' => 'asstmanager'];
        if (empty($scope['regional_manager_userid'])) {
            $pocwhere .= ' AND 1 = 0';
        } else {
            $pocwhere .= ' AND rp.pocid = :regionalmanagerid';
            $pocparams['regionalmanagerid'] = $scope['regional_manager_userid'];
        }

        $stats['total_pocs'] = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT rp.userid)
               FROM {regionalpoc} rp
               JOIN {user} u ON u.id = rp.userid
              WHERE {$pocwhere}",
            $pocparams
        );

        $pocchangeparams = $pocparams + ['quarterstart' => $quarterstart];
        $stats['total_pocs_change'] = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT rp.userid)
               FROM {regionalpoc} rp
               JOIN {user} u ON u.id = rp.userid
              WHERE {$pocwhere}
                AND u.timecreated >= :quarterstart",
            $pocchangeparams
        );
    } else if ($dbman->table_exists('poc')) {
        $pocjoin = '';
        $pocwhere = "u.deleted = 0
                AND u.suspended = 0";
        $pocparams = [];
        local_dashboard_apply_poc_school_scope($pocjoin, $pocwhere, $pocparams, $scope, 'p');

        $stats['total_pocs'] = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT p.userid)
               FROM {poc} p
               JOIN {user} u ON u.id = p.userid
              {$pocjoin}
              WHERE {$pocwhere}",
            $pocparams
        );

        $pocchangeparams = $pocparams + ['quarterstart' => $quarterstart];
        $stats['total_pocs_change'] = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT p.userid)
               FROM {poc} p
               JOIN {user} u ON u.id = p.userid
              {$pocjoin}
              WHERE {$pocwhere}
                AND u.timecreated >= :quarterstart",
            $pocchangeparams
        );
    }

    if ($dbman->table_exists('student')) {
        $studentwhere = "u.deleted = 0
                AND u.suspended = 0";
        $studentparams = [];
        local_dashboard_apply_student_school_scope($studentwhere, $studentparams, $scope, 's');

        $stats['total_students'] = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT s.userid)
               FROM {student} s
               JOIN {user} u ON u.id = s.userid
              WHERE {$studentwhere}",
            $studentparams
        );

        $studentchangeparams = $studentparams + ['yearstart' => $yearstart];
        $stats['total_students_change'] = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT s.userid)
               FROM {student} s
               JOIN {user} u ON u.id = s.userid
              WHERE {$studentwhere}
                AND u.timecreated >= :yearstart",
            $studentchangeparams
        );
    }

    $coursewhere = "c.visible = 1
            AND c.id <> :siteid";
    $courseparams = ['siteid' => SITEID];
    local_dashboard_apply_course_school_scope($coursewhere, $courseparams, $scope, 'c');
    $stats['active_courses'] = (int) $DB->count_records_sql(
        "SELECT COUNT(DISTINCT c.id)
           FROM {course} c
          WHERE {$coursewhere}",
        $courseparams
    );
    $coursechangeparams = $courseparams + ['yearstart' => $yearstart];
    $stats['active_courses_change'] = (int) $DB->count_records_sql(
        "SELECT COUNT(DISTINCT c.id)
           FROM {course} c
          WHERE {$coursewhere}
            AND timecreated >= :yearstart",
        $coursechangeparams
    );

    if ($dbman->table_exists('student')) {
        $attendance = local_dashboard_get_attendance_counts($daystart, $dayend, $scope);

        if ($attendance) {
            $stats['attendance_present_today'] = (int) $attendance->presentcount;
            $stats['attendance_absent_today'] = (int) $attendance->absentcount;
            $stats['attendance_marked_today'] = (int) $attendance->enrolledcount;
        }
    }

    if ($stats['attendance_marked_today'] > 0) {
        $stats['todays_attendance_percent'] =
            ($stats['attendance_present_today'] / $stats['attendance_marked_today']) * 100;
    }
    if (empty($scope['is_school_scoped'])) {
        $stats['total_logins_today'] = local_dashboard_get_today_unique_login_count($daystart, $dayend);
    }

    $schoolattendance = local_dashboard_get_school_attendance_context(5, $daystart, $dayend, $scope);
    $attendancetrendjson = json_encode(local_dashboard_get_attendance_trend_context($daystart, $scope));
    $traineractivity = local_dashboard_get_trainer_activity_summary_context($daystart, $dayend, $scope);
    $poccontext = local_dashboard_get_poc_context($scope);
    $recentactivities = local_dashboard_get_recent_activities_context(15, $scope);

    return [
        'config' => ['wwwroot' => $CFG->wwwroot],
        'dashboard_title' => empty($scope['is_school_scoped']) ? 'Admin Dashboard' :
            (empty($scope['regional_manager_userid']) ? 'ARM Dashboard' : 'Zonal Manager Dashboard'),
        'dashboard_subtitle' => empty($scope['is_school_scoped']) ?
            'School operations, trainer activity, student attendance, and LMS health monitoring.' :
            'Assigned school operations, trainer activity, student attendance, and scoped LMS analytics.',
        'school_management_url' => (new moodle_url('/local/school/index.php'))->out(false),
        'trainer_management_url' => (new moodle_url(
            empty($scope['is_school_scoped']) ? '/local/trainer/index.php' : '/local/trainer/trainer_manage.php'
        ))->out(false),
        'poc_management_url' => (new moodle_url('/local/poc/poc_management.php'))->out(false),
        'student_management_url' => (new moodle_url(
            empty($scope['is_school_scoped']) ? '/local/studentadmin/index.php' : '/local/students/student_manage.php'
        ))->out(false),
        'course_management_url' => (new moodle_url('/my/courses.php'))->out(false),
        'attendance_management_url' => (new moodle_url('/local/attendance_new/index.php'))->out(false),
        'trainer_activity_url' => (new moodle_url('/local/dashboard/admin/trainer_activity.php'))->out(false),
        'arm_management_url' => (new moodle_url('/local/regionalpoc/rm_arm_manage.php', ['usertype' => 'arm']))->out(false),
        'show_arm_management_link' => empty($scope['is_school_scoped']),
        'poc_card_aria_label' => empty($scope['is_school_scoped']) ? 'Open POC List' : 'Open ARM List',
        'poc_card_label' => empty($scope['is_school_scoped']) ? 'Total POCs' : 'Total ARM',
        'poc_coverage_label' => empty($scope['is_school_scoped']) ? 'POC coverage' : 'ARM coverage',
        'poc_coverage_text' => empty($scope['is_school_scoped']) ?
            'POCs assigned across active schools' :
            'ARM assigned across active schools',
        'poc_modal_title' => empty($scope['is_school_scoped']) ? 'POC Listing' : 'ARM Listing',
        'poc_modal_count_label' => empty($scope['is_school_scoped']) ? 'POCs' : 'ARM',
        'poc_modal_close_label' => empty($scope['is_school_scoped']) ? 'Close POC listing' : 'Close ARM listing',
        'poc_empty_text' => empty($scope['is_school_scoped']) ? 'No POC data is available.' : 'No ARM data is available.',
        'total_schools' => local_dashboard_format_count($stats['total_schools']),
        'total_trainers' => local_dashboard_format_count($stats['total_trainers']),
        'total_pocs' => local_dashboard_format_count($stats['total_pocs']),
        'total_students' => local_dashboard_format_count($stats['total_students']),
        'active_courses' => local_dashboard_format_count($stats['active_courses']),
        'todays_attendance_percent' => local_dashboard_format_percent($stats['todays_attendance_percent']),
        'total_schools_change' => local_dashboard_format_signed_count($stats['total_schools_change']),
        'total_trainers_change' => local_dashboard_format_signed_count($stats['total_trainers_change']),
        'total_pocs_change' => local_dashboard_format_signed_count($stats['total_pocs_change']),
        'total_students_change' => local_dashboard_format_signed_count($stats['total_students_change']),
        'active_courses_change' => local_dashboard_format_signed_count($stats['active_courses_change']),
        'attendance_change' => 'Daily',
        'total_schools_period' => 'Yearly',
        'total_trainers_period' => 'Quarterly',
        'total_pocs_period' => 'Quarterly',
        'total_students_period' => 'Yearly',
        'active_courses_period' => 'Yearly',
        'students_present_today' => local_dashboard_format_count($stats['attendance_present_today']),
        'students_absent_today' => local_dashboard_format_count($stats['attendance_absent_today']),
        'total_logins_today' => local_dashboard_format_count($stats['total_logins_today']),
        'attendance_trend_json' => $attendancetrendjson ?: '{"labels":[],"values":[]}',
        'show_operational_health_metrics' => empty($scope['is_school_scoped']),
    ] + $schoolattendance + $traineractivity + $poccontext + $recentactivities;
}

function local_dashboard_normalize_school_scope(array $scope): array {
    $schoolids = array_values(array_unique(array_filter(array_map('intval', $scope['schoolids'] ?? []))));

    return [
        'is_school_scoped' => !empty($scope['is_school_scoped']),
        'schoolids' => $schoolids,
        'regional_manager_userid' => (int) ($scope['regional_manager_userid'] ?? 0),
    ];
}

function local_dashboard_get_pocschool_scope(int $userid): array {
    global $DB;

    if (!local_dashboard_is_pocschool_user($userid)) {
        return ['is_school_scoped' => false, 'schoolids' => []];
    }

    if (local_regionalpoc_is_arm_user($userid)) {
        return [
            'is_school_scoped' => true,
            'schoolids' => local_regionalpoc_get_arm_school_ids($userid),
            'regional_manager_userid' => 0,
        ];
    }

    return [
        'is_school_scoped' => true,
        'schoolids' => $DB->get_fieldset_select('schoolassign', 'schoolid', 'userid = ?', [$userid]),
        'regional_manager_userid' => $userid,
    ];
}

function local_dashboard_is_pocschool_user(int $userid): bool {
    global $DB;

    if (is_siteadmin($userid)) {
        return false;
    }

    if (local_dashboard_user_has_role_shortname($userid, 'pocschool') ||
            local_regionalpoc_is_arm_user($userid)) {
        return true;
    }

    return $DB->record_exists('poc', ['userid' => $userid]) &&
        $DB->record_exists('schoolassign', ['userid' => $userid]);
}

function local_dashboard_user_has_role_shortname(int $userid, string $roleshortname): bool {
    global $DB;

    return $DB->record_exists_sql(
        "SELECT 1
           FROM {role_assignments} ra
           JOIN {role} r ON r.id = ra.roleid
          WHERE ra.userid = :userid
            AND r.shortname = :roleshortname",
        ['userid' => $userid, 'roleshortname' => $roleshortname]
    );
}

function local_dashboard_scope_is_empty(array $scope): bool {
    return !empty($scope['is_school_scoped']) && empty($scope['schoolids']);
}

function local_dashboard_apply_school_scope(
    string &$where,
    array &$params,
    array $scope,
    string $alias,
    string $field,
    string $prefix = 'dashschool'
): void {
    global $DB;

    if (empty($scope['is_school_scoped'])) {
        return;
    }

    if (local_dashboard_scope_is_empty($scope)) {
        $where .= ' AND 1 = 0';
        return;
    }

    list($insql, $inparams) = $DB->get_in_or_equal($scope['schoolids'], SQL_PARAMS_NAMED, $prefix);
    $where .= " AND {$alias}.{$field} {$insql}";
    $params += $inparams;
}

function local_dashboard_apply_student_school_scope(
    string &$where,
    array &$params,
    array $scope,
    string $alias,
    string $prefix = 'dashstudent'
): void {
    local_dashboard_apply_school_scope($where, $params, $scope, $alias, 'schoolid', $prefix);
}

function local_dashboard_apply_school_table_scope(string &$where, array &$params, array $scope, string $schoolalias, string $categoryalias): void {
    global $DB;

    if (empty($scope['is_school_scoped'])) {
        return;
    }

    if (local_dashboard_scope_is_empty($scope)) {
        $where .= ' AND 1 = 0';
        return;
    }

    list($categorysql, $categoryparams) = $DB->get_in_or_equal($scope['schoolids'], SQL_PARAMS_NAMED, 'dashschoolcat');
    list($schoolsql, $schoolparams) = $DB->get_in_or_equal($scope['schoolids'], SQL_PARAMS_NAMED, 'dashschoolrecord');
    $where .= " AND ({$categoryalias}.id {$categorysql} OR {$schoolalias}.course_cat_id {$schoolsql})";
    $params += $categoryparams + $schoolparams;
}

function local_dashboard_apply_trainer_school_scope(string &$where, array &$params, array $scope, string $traineralias): void {
    global $DB;

    if (empty($scope['is_school_scoped'])) {
        return;
    }

    if (!empty($scope['regional_manager_userid'])) {
        $params['dashtrainerpocuserid'] = $scope['regional_manager_userid'];
        $params['dashtrainercreatedby'] = $scope['regional_manager_userid'];
        $legacycondition = "(({$traineralias}.schoolid IS NULL OR {$traineralias}.schoolid = 0
                    OR NOT EXISTS (
                        SELECT 1
                          FROM {course_categories} dash_trainer_cc
                         WHERE dash_trainer_cc.id = {$traineralias}.schoolid
                    ))
                    AND {$traineralias}.createdby = :dashtrainercreatedby)";
        $assignedcondition = "({$traineralias}.schoolid IS NOT NULL
                    AND {$traineralias}.schoolid <> 0
                    AND EXISTS (
                        SELECT 1
                          FROM {schoolassign} dash_trainer_sa
                         WHERE dash_trainer_sa.schoolid = {$traineralias}.schoolid
                           AND dash_trainer_sa.userid = :dashtrainerpocuserid
                    ))";

        $where .= " AND ({$assignedcondition} OR {$legacycondition})";
        return;
    }

    if (local_dashboard_scope_is_empty($scope)) {
        $where .= ' AND 1 = 0';
        return;
    }

    list($trainersql, $trainerparams) = $DB->get_in_or_equal($scope['schoolids'], SQL_PARAMS_NAMED, 'dashtrainerrecord');
    $where .= " AND {$traineralias}.schoolid {$trainersql}";
    $params += $trainerparams;
}

function local_dashboard_apply_poc_school_scope(string &$join, string &$where, array &$params, array $scope, string $pocalias): void {
    global $DB;

    if (empty($scope['is_school_scoped'])) {
        return;
    }

    if (local_dashboard_scope_is_empty($scope) || !$DB->get_manager()->table_exists('schoolassign')) {
        $where .= ' AND 1 = 0';
        return;
    }

    list($insql, $inparams) = $DB->get_in_or_equal($scope['schoolids'], SQL_PARAMS_NAMED, 'dashpoc');
    $join .= " JOIN {schoolassign} dash_poc_sa ON dash_poc_sa.userid = {$pocalias}.userid
                AND dash_poc_sa.schoolid {$insql}";
    $params += $inparams;
}

function local_dashboard_apply_course_school_scope(string &$where, array &$params, array $scope, string $coursealias): void {
    global $DB;

    if (empty($scope['is_school_scoped'])) {
        return;
    }

    if (local_dashboard_scope_is_empty($scope)) {
        $where .= ' AND 1 = 0';
        return;
    }

    list($categorysql, $categoryparams) = $DB->get_in_or_equal($scope['schoolids'], SQL_PARAMS_NAMED, 'dashcoursecat');
    $conditions = [
        "EXISTS (
            SELECT 1
              FROM {course_categories} dash_grade
             WHERE dash_grade.id = {$coursealias}.category
               AND dash_grade.parent {$categorysql}
        )",
    ];
    $params += $categoryparams;

    if ($DB->get_manager()->table_exists('poc_copy_course')) {
        list($pocsql, $pocparams) = $DB->get_in_or_equal($scope['schoolids'], SQL_PARAMS_NAMED, 'dashcoursepoc');
        $conditions[] = "EXISTS (
            SELECT 1
              FROM {poc_copy_course} dash_pcc
             WHERE dash_pcc.courseid = {$coursealias}.id
               AND dash_pcc.status = 1
               AND dash_pcc.schoolid {$pocsql}
        )";
        $params += $pocparams;
    }

    $where .= ' AND (' . implode(' OR ', $conditions) . ')';
}

/**
 * Calculate percentage growth between a current and previous count.
 *
 * @param int $current
 * @param int $previous
 * @return int
 */
function local_dashboard_calculate_growth(int $current, int $previous): int {
    if ($previous <= 0) {
        return $current > 0 ? 100 : 0;
    }

    return (int) round((($current - $previous) / $previous) * 100);
}

/**
 * Count distinct active non-guest users who logged in during a date range.
 *
 * @param int $daystart
 * @param int $dayend
 * @return int
 */
function local_dashboard_get_today_unique_login_count(int $daystart, int $dayend): int {
    global $CFG, $DB;

    if (!$DB->get_manager()->table_exists('logstore_standard_log')) {
        return 0;
    }

    $guestid = (int) ($CFG->siteguest ?? 0);

    return (int) $DB->count_records_sql(
        "SELECT COUNT(DISTINCT l.userid)
           FROM {logstore_standard_log} l
           JOIN {user} u ON u.id = l.userid
          WHERE l.eventname = :eventname
            AND l.timecreated BETWEEN :daystart AND :dayend
            AND l.userid > 0
            AND l.userid <> :guestid
            AND u.deleted = 0
            AND u.suspended = 0",
        [
            'eventname' => '\\core\\event\\user_loggedin',
            'daystart' => $daystart,
            'dayend' => $dayend,
            'guestid' => $guestid,
        ]
    );
}

/**
 * Count global LMS attendance for a date range.
 *
 * The denominator is all active enrolled students across the LMS, so schools
 * with no submitted attendance still contribute zero present students.
 *
 * @param int $daystart
 * @param int $dayend
 * @return stdClass
 */
function local_dashboard_get_attendance_counts(int $daystart, int $dayend, array $scope = []): stdClass {
    global $DB;

    $scope = local_dashboard_normalize_school_scope($scope);
    $dbman = $DB->get_manager();
    if (!$dbman->table_exists('student')) {
        $counts = new stdClass();
        $counts->presentcount = 0;
        $counts->absentcount = 0;
        $counts->enrolledcount = 0;
        return $counts;
    }

    $presentjoin = '';
    $presentfield = '0 AS presentcount';
    $presentwhere = "att.date BETWEEN :presentdaystart AND :presentdayend
                           AND UPPER(ast.status) = 'P'
                           AND pu.deleted = 0
                           AND pu.suspended = 0";
    $presentparams = ['presentdaystart' => $daystart, 'presentdayend' => $dayend];
    local_dashboard_apply_student_school_scope($presentwhere, $presentparams, $scope, 'ps', 'dashpresentstudent');

    if ($dbman->table_exists('attendance') && $dbman->table_exists('attendance_student')) {
        $presentfield = 'COALESCE(present.presentcount, 0) AS presentcount';
        $presentjoin = "LEFT JOIN (
                SELECT COUNT(DISTINCT presentstudents.studentid) AS presentcount
                  FROM (
                        SELECT ast.studentid
                          FROM {attendance_student} ast
                          JOIN {attendance} att ON att.id = ast.attendanceid
                          JOIN {student} ps ON ps.userid = ast.studentid
                           AND ps.schoolid = att.schoolid
                          JOIN {user} pu ON pu.id = ps.userid
                         WHERE {$presentwhere}
                      GROUP BY ast.studentid
                       ) presentstudents
                ) present ON 1 = 1";
    }

    $where = "u.deleted = 0
            AND u.suspended = 0";
    $params = $presentparams;
    local_dashboard_apply_student_school_scope($where, $params, $scope, 's', 'dashenrolledstudent');

    $counts = $DB->get_record_sql(
        "SELECT $presentfield,
                COUNT(DISTINCT s.userid) AS enrolledcount
           FROM {student} s
           JOIN {user} u ON u.id = s.userid
    $presentjoin
          WHERE {$where}",
        $params
    );

    if (!$counts) {
        $counts = new stdClass();
        $counts->presentcount = 0;
        $counts->enrolledcount = 0;
    }
    $counts->presentcount = min((int) $counts->presentcount, (int) $counts->enrolledcount);
    $counts->absentcount = max((int) $counts->enrolledcount - (int) $counts->presentcount, 0);

    return $counts;
}

/**
 * Build school-wise attendance data for the admin dashboard.
 *
 * @param int $limit
 * @param int $daystart
 * @param int $dayend
 * @return array
 */
function local_dashboard_get_school_attendance_context(int $limit, int $daystart, int $dayend, array $scope = []): array {
    $schools = local_dashboard_get_school_attendance_records($daystart, $dayend, $scope);
    $topschools = array_slice($schools, 0, $limit);

    return [
        'school_attendance_top5' => $topschools,
        'school_attendance_all' => $schools,
        'has_school_attendance' => !empty($schools),
        'show_school_attendance_more' => count($schools) > $limit,
        'school_attendance_total_count' => count($schools),
    ];
}

/**
 * Get attendance totals grouped by active school category for a date range.
 *
 * @param int $daystart
 * @param int $dayend
 * @return array
 */
function local_dashboard_get_school_attendance_records(int $daystart, int $dayend, array $scope = []): array {
    global $DB;

    $scope = local_dashboard_normalize_school_scope($scope);
    $dbman = $DB->get_manager();
    if (!$dbman->table_exists('school') || !$dbman->table_exists('student') ||
            !$dbman->table_exists('attendance') || !$dbman->table_exists('attendance_student')) {
        return [];
    }

    $where = 'cc.visible = 1';
    $studentwhere = "u.deleted = 0
                   AND u.suspended = 0";
    $attendancewhere = "att.date BETWEEN :daystart AND :dayend
                           AND u.deleted = 0
                           AND u.suspended = 0";
    $params = ['daystart' => $daystart, 'dayend' => $dayend];
    local_dashboard_apply_school_scope($where, $params, $scope, 'cc', 'id', 'dashschoolrow');
    local_dashboard_apply_student_school_scope($studentwhere, $params, $scope, 's', 'dashschoolstudent');
    local_dashboard_apply_school_scope($attendancewhere, $params, $scope, 'att', 'schoolid', 'dashschoolatt');

    $records = $DB->get_records_sql(
        "SELECT sc.id,
                sc.school_name,
                COALESCE(students.totalstudents, 0) AS totalstudents,
                COALESCE(attendance.presentstudents, 0) AS presentstudents,
                COALESCE(attendance.absentstudents, 0) AS absentstudents
           FROM {school} sc
           JOIN {course_categories} cc ON cc.id = sc.course_cat_id
            AND cc.visible = 1
      LEFT JOIN (
                SELECT s.schoolid,
                       COUNT(DISTINCT s.userid) AS totalstudents
                  FROM {student} s
                  JOIN {user} u ON u.id = s.userid
                 WHERE {$studentwhere}
              GROUP BY s.schoolid
                ) students ON students.schoolid = sc.course_cat_id
      LEFT JOIN (
                SELECT attstudents.schoolid,
                       SUM(attstudents.haspresent) AS presentstudents,
                       SUM(CASE WHEN attstudents.haspresent = 0 THEN 1 ELSE 0 END) AS absentstudents
                  FROM (
                        SELECT att.schoolid,
                               s.userid AS studentid,
                               MAX(CASE WHEN UPPER(ast.status) = 'P' THEN 1 ELSE 0 END) AS haspresent,
                               MAX(CASE WHEN UPPER(ast.status) = 'A' THEN 1 ELSE 0 END) AS hasabsent
                          FROM {attendance} att
                          JOIN {student} s ON s.schoolid = att.schoolid
                     LEFT JOIN {attendance_student} ast ON ast.attendanceid = att.id
                           AND ast.studentid = s.userid
                          JOIN {user} u ON u.id = s.userid
                         WHERE {$attendancewhere}
                      GROUP BY att.schoolid, s.userid
                       ) attstudents
              GROUP BY attstudents.schoolid
                ) attendance ON attendance.schoolid = sc.course_cat_id
          WHERE {$where}
       ORDER BY sc.school_name ASC",
        $params
    );

    $schools = [];
    foreach ($records as $record) {
        $totalstudents = (int) $record->totalstudents;
        $presentstudents = (int) $record->presentstudents;
        $absentstudents = (int) $record->absentstudents;
        $markedstudents = $presentstudents + $absentstudents;
        $percentage = $markedstudents > 0 ? round(($presentstudents / $markedstudents) * 100, 2) : 0;

        $schools[] = [
            'school_name' => format_string($record->school_name),
            'total_students' => local_dashboard_format_count($totalstudents),
            'present_students' => local_dashboard_format_count($presentstudents),
            'absent_students' => local_dashboard_format_count($absentstudents),
            'attendance_percentage' => local_dashboard_format_percent($percentage),
            'attendance_width' => min(100, max(0, $percentage)),
            'attendance_sort' => $percentage,
        ];
    }

    usort($schools, function(array $first, array $second): int {
        if ($first['attendance_sort'] === $second['attendance_sort']) {
            return strcmp($first['school_name'], $second['school_name']);
        }

        return $first['attendance_sort'] < $second['attendance_sort'] ? 1 : -1;
    });

    return $schools;
}

/**
 * Build the last seven days of attendance percentages.
 *
 * @param int $todaystart
 * @return array
 */
function local_dashboard_get_attendance_trend_context(int $todaystart, array $scope = []): array {
    global $DB;

    $labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $valuesbyweekday = array_fill(1, 7, '0');
    $dbman = $DB->get_manager();

    for ($i = 6; $i >= 0; $i--) {
        $start = $todaystart - ($i * DAYSECS);
        $end = $start + DAYSECS - 1;
        $weekday = (int) userdate($start, '%u');

        if ($dbman->table_exists('attendance') && $dbman->table_exists('attendance_student')) {
            $attendance = local_dashboard_get_attendance_counts($start, $end, $scope);
            if ((int) $attendance->enrolledcount > 0) {
                $valuesbyweekday[$weekday] = local_dashboard_format_trend_percent(
                    local_dashboard_round_percent(((int) $attendance->presentcount / (int) $attendance->enrolledcount) * 100)
                );
            }
        }
    }

    return [
        'labels' => $labels,
        'values' => array_values($valuesbyweekday),
    ];
}

/**
 * Build live trainer activity counters for the dashboard card.
 *
 * @param int $daystart
 * @param int $dayend
 * @return array
 */
function local_dashboard_get_trainer_activity_summary_context(int $daystart, int $dayend, array $scope = []): array {
    $summary = local_dashboard_get_trainer_activity_summary($daystart, $dayend, $scope);

    return [
        'trainer_activity_total_assigned' => local_dashboard_format_count($summary['totalassigned']),
        'trainer_activity_active_count' => local_dashboard_format_count($summary['active']),
        'trainer_activity_inactive_count' => local_dashboard_format_count($summary['inactive']),
        'trainer_activity_sessions_scheduled' => local_dashboard_format_count($summary['sessions']),
        'trainer_activity_active_percent' => local_dashboard_format_percent($summary['activepercent']),
        'trainer_activity_active_percent_number' => local_dashboard_format_trend_percent($summary['activepercent']),
        'trainer_activity_json' => json_encode([
            'active' => $summary['active'],
            'inactive' => $summary['inactive'],
        ]) ?: '{"active":0,"inactive":0}',
        'trainer_activity_today_name' => local_dashboard_get_weekday_name($daystart),
    ];
}

/**
 * Get raw live trainer activity counters.
 *
 * @param int $daystart
 * @param int $dayend
 * @return array
 */
function local_dashboard_get_trainer_activity_summary(int $daystart, int $dayend, array $scope = []): array {
    global $DB;

    $scope = local_dashboard_normalize_school_scope($scope);
    $dbman = $DB->get_manager();
    $required = ['trainer', 'trainer_course_mapping', 'user'];
    foreach ($required as $table) {
        if (!$dbman->table_exists($table)) {
            return ['totalassigned' => 0, 'active' => 0, 'inactive' => 0, 'sessions' => 0, 'activepercent' => 0];
        }
    }

    $where = "tcm.status = 1
            AND tcm.traineruserid IS NOT NULL
            AND tcm.traineruserid <> 0
            AND u.deleted = 0
            AND u.suspended = 0";
    $params = [];
    local_dashboard_apply_school_scope($where, $params, $scope, 'tcm', 'schoolid');

    $totalassigned = (int) $DB->count_records_sql(
        "SELECT COUNT(DISTINCT tcm.traineruserid)
           FROM {trainer_course_mapping} tcm
           JOIN {trainer} t ON t.userid = tcm.traineruserid
           JOIN {user} u ON u.id = tcm.traineruserid
          WHERE {$where}",
        $params
    );

    $active = 0;
    if ($dbman->table_exists('local_dashboard_activity_logs') && $dbman->table_exists('attendance') &&
            $dbman->table_exists('attendance_student')) {
        $activewhere = "l.activitytype = :activitytype
                AND l.timecreated BETWEEN :daystart AND :dayend
                AND att.date BETWEEN :daystart2 AND :dayend2
                AND EXISTS (
                    SELECT 1
                      FROM {attendance_student} ast
                     WHERE ast.attendanceid = att.id
                )
                AND l.actorid IS NOT NULL
                AND l.actorid <> 0
                AND u.deleted = 0
                AND u.suspended = 0";
        $activeparams = [
            'activitytype' => 'attendance_submitted',
            'daystart' => $daystart,
            'dayend' => $dayend,
            'daystart2' => $daystart,
            'dayend2' => $dayend,
        ];
        local_dashboard_apply_school_scope($activewhere, $activeparams, $scope, 'att', 'schoolid');

        $active = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT l.actorid)
               FROM {local_dashboard_activity_logs} l
               JOIN {attendance} att ON (
                    l.metadata LIKE CONCAT('%\"attendanceid\":', att.id, ',%')
                    OR l.metadata LIKE CONCAT('%\"attendanceid\":', att.id, '}%')
                )
               JOIN {trainer_course_mapping} tcm ON tcm.traineruserid = l.actorid
                AND tcm.status = 1
                AND tcm.schoolid = att.schoolid
                AND tcm.gradeid = att.gradeid
               JOIN {trainer} t ON t.userid = l.actorid
               JOIN {user} u ON u.id = l.actorid
              WHERE {$activewhere}",
            $activeparams
        );
    }

    $sessions = 0;
    if ($dbman->table_exists('timetable')) {
        $sessionwhere = "tt.day = :dayname
                AND tt.period IS NOT NULL
                AND tt.period <> ''
                AND tt.schoolid IS NOT NULL
                AND tt.schoolid <> 0
                AND tt.gradeid IS NOT NULL
                AND tt.gradeid <> 0
                AND EXISTS (
                    SELECT 1
                      FROM {trainer_course_mapping} tcm
                      JOIN {user} u ON u.id = tcm.traineruserid
                     WHERE tcm.status = 1
                       AND tcm.schoolid = tt.schoolid
                       AND tcm.gradeid = tt.gradeid
                       AND tcm.traineruserid IS NOT NULL
                       AND tcm.traineruserid <> 0
                       AND u.deleted = 0
                       AND u.suspended = 0
                )";
        $sessionparams = ['dayname' => local_dashboard_get_weekday_name($daystart)];
        local_dashboard_apply_school_scope($sessionwhere, $sessionparams, $scope, 'tt', 'schoolid');

        $sessions = (int) $DB->count_records_sql(
            "SELECT COUNT(DISTINCT tt.id)
               FROM {timetable} tt
              WHERE {$sessionwhere}",
            $sessionparams
        );
    }

    $inactive = max($totalassigned - $active, 0);
    $activepercent = $totalassigned > 0 ? (($active / $totalassigned) * 100) : 0;

    return [
        'totalassigned' => $totalassigned,
        'active' => min($active, $totalassigned),
        'inactive' => $inactive,
        'sessions' => $sessions,
        'activepercent' => $activepercent,
    ];
}

/**
 * Build the trainer activity monitoring page context.
 *
 * @param array $filters
 * @param int $page
 * @param int $perpage
 * @return array
 */
function local_dashboard_get_trainer_activity_page_context(array $filters, int $page = 0, int $perpage = 25, array $scope = []): array {
    global $CFG, $DB;

    $daystart = usergetmidnight(time());
    $dayend = $daystart + DAYSECS - 1;
    $summary = local_dashboard_get_trainer_activity_summary($daystart, $dayend, $scope);
    $rows = local_dashboard_get_trainer_activity_rows($daystart, $dayend, $filters, $scope);
    $totalrows = count($rows);
    $page = max(0, $page);
    $perpage = max(1, $perpage);
    $pagedrows = array_slice($rows, $page * $perpage, $perpage);

    $baseparams = [];
    foreach (['search', 'schoolid', 'status'] as $key) {
        if (!empty($filters[$key])) {
            $baseparams[$key] = $filters[$key];
        }
    }

    $nextparams = $baseparams + ['page' => $page + 1];
    $prevparams = $baseparams + ['page' => max(0, $page - 1)];
    $firstitem = $totalrows > 0 ? ($page * $perpage) + 1 : 0;
    $lastitem = min(($page + 1) * $perpage, $totalrows);
    $schooloptions = local_dashboard_get_trainer_activity_school_options((int) ($filters['schoolid'] ?? 0), $scope);

    return [
        'config' => ['wwwroot' => $CFG->wwwroot],
        'dashboard_url' => (new moodle_url('/local/dashboard/admin/index.php'))->out(false),
        'trainer_activity_today_name' => local_dashboard_get_weekday_name($daystart),
        'trainer_activity_total_assigned' => local_dashboard_format_count($summary['totalassigned']),
        'trainer_activity_active_count' => local_dashboard_format_count($summary['active']),
        'trainer_activity_inactive_count' => local_dashboard_format_count($summary['inactive']),
        'trainer_activity_sessions_scheduled' => local_dashboard_format_count($summary['sessions']),
        'trainer_activity_active_percent' => local_dashboard_format_percent($summary['activepercent']),
        'trainer_activity_rows' => $pagedrows,
        'has_trainer_activity_rows' => !empty($pagedrows),
        'trainer_activity_total_rows' => local_dashboard_format_count($totalrows),
        'trainer_activity_range' => $firstitem . '-' . $lastitem,
        'trainer_activity_school_options' => $schooloptions,
        'filter_search' => s($filters['search'] ?? ''),
        'filter_status_all_selected' => empty($filters['status']) ? 'selected' : '',
        'filter_status_active_selected' => (($filters['status'] ?? '') === 'active') ? 'selected' : '',
        'filter_status_pending_selected' => (($filters['status'] ?? '') === 'pending') ? 'selected' : '',
        'filter_status_inactive_selected' => (($filters['status'] ?? '') === 'inactive') ? 'selected' : '',
        'has_prev_page' => $page > 0,
        'has_next_page' => $lastitem < $totalrows,
        'prev_page_url' => (new moodle_url('/local/dashboard/admin/trainer_activity.php', $prevparams))->out(false),
        'next_page_url' => (new moodle_url('/local/dashboard/admin/trainer_activity.php', $nextparams))->out(false),
    ];
}

/**
 * Get trainer rows with today's operational activity status.
 *
 * @param int $daystart
 * @param int $dayend
 * @param array $filters
 * @return array
 */
function local_dashboard_get_trainer_activity_rows(int $daystart, int $dayend, array $filters = [], array $scope = []): array {
    global $DB;

    $scope = local_dashboard_normalize_school_scope($scope);
    $dbman = $DB->get_manager();
    foreach (['trainer', 'trainer_course_mapping', 'user'] as $table) {
        if (!$dbman->table_exists($table)) {
            return [];
        }
    }

    $params = [
        'dayname' => local_dashboard_get_weekday_name($daystart),
        'daystart' => $daystart,
        'dayend' => $dayend,
        'daystart2' => $daystart,
        'dayend2' => $dayend,
    ];
    $where = "u.deleted = 0
            AND u.suspended = 0
            AND tcm.status = 1
            AND tcm.traineruserid IS NOT NULL
            AND tcm.traineruserid <> 0";

    if (!empty($filters['search'])) {
        $where .= " AND (" . $DB->sql_like($DB->sql_concat('u.firstname', "' '", 'u.lastname'), ':search', false) .
            " OR " . $DB->sql_like('u.email', ':searchemail', false) . ")";
        $params['search'] = '%' . $DB->sql_like_escape($filters['search']) . '%';
        $params['searchemail'] = '%' . $DB->sql_like_escape($filters['search']) . '%';
    }

    if (!empty($filters['schoolid'])) {
        $where .= " AND tcm.schoolid = :filterschoolid";
        $params['filterschoolid'] = (int) $filters['schoolid'];
    }
    local_dashboard_apply_school_scope($where, $params, $scope, 'tcm', 'schoolid');

    $schooljoin = $dbman->table_exists('school') ?
        "LEFT JOIN {school} sc ON sc.course_cat_id = tcm.schoolid" :
        "";
    $schoolfield = $dbman->table_exists('school') ?
        "COALESCE(sc.school_name, schoolcat.name)" :
        "schoolcat.name";
    $hasactivitytables = $dbman->table_exists('local_dashboard_activity_logs') && $dbman->table_exists('attendance') &&
            $dbman->table_exists('attendance_student');
    $activitywhere = "l.activitytype = 'attendance_submitted'
                   AND l.timecreated BETWEEN :daystart AND :dayend
                   AND att.date BETWEEN :daystart2 AND :dayend2
                   AND EXISTS (
                       SELECT 1
                         FROM {attendance_student} ast
                        WHERE ast.attendanceid = att.id
                   )";
    if ($hasactivitytables) {
        local_dashboard_apply_school_scope($activitywhere, $params, $scope, 'att', 'schoolid', 'dashtrainerrowactivity');
    }
    $activityjoin = $hasactivitytables ?
        "LEFT JOIN (
                SELECT l.actorid,
                       MAX(l.timecreated) AS todaysubmitted,
                       MAX(l.timecreated) AS lastactivity
                  FROM {local_dashboard_activity_logs} l
                  JOIN {attendance} att ON (
                       l.metadata LIKE CONCAT('%\"attendanceid\":', att.id, ',%')
                       OR l.metadata LIKE CONCAT('%\"attendanceid\":', att.id, '}%')
                  )
                 WHERE {$activitywhere}
              GROUP BY l.actorid
                ) activity ON activity.actorid = u.id" :
        "";
    $activityfields = $hasactivitytables ?
        "COALESCE(activity.todaysubmitted, 0) AS todaysubmitted,
         COALESCE(activity.lastactivity, 0) AS lastactivity" :
        "0 AS todaysubmitted, 0 AS lastactivity";
    $activitygroup = $hasactivitytables ?
        ", activity.todaysubmitted, activity.lastactivity" :
        "";
    $sessionjoin = $dbman->table_exists('timetable') ?
        "LEFT JOIN {timetable} tt ON tt.schoolid = tcm.schoolid
                 AND tt.gradeid = tcm.gradeid
                 AND tt.day = :dayname
                 AND tt.period IS NOT NULL
                 AND tt.period <> ''
                 AND tt.schoolid IS NOT NULL
                 AND tt.schoolid <> 0
                 AND tt.gradeid IS NOT NULL
                 AND tt.gradeid <> 0" :
        "";
    $sessionfield = $dbman->table_exists('timetable') ?
        "COUNT(DISTINCT tt.id) AS sessionsscheduled" :
        "0 AS sessionsscheduled";

    $records = $DB->get_records_sql(
        "SELECT u.id,
                u.firstname,
                u.lastname,
                u.email,
                GROUP_CONCAT(DISTINCT {$schoolfield} ORDER BY {$schoolfield} SEPARATOR ', ') AS schoolnames,
                GROUP_CONCAT(DISTINCT gradecat.name ORDER BY gradecat.name SEPARATOR ', ') AS gradenames,
                {$sessionfield},
                {$activityfields}
           FROM {trainer_course_mapping} tcm
           JOIN {trainer} tr ON tr.userid = tcm.traineruserid
           JOIN {user} u ON u.id = tcm.traineruserid
      LEFT JOIN {course_categories} schoolcat ON schoolcat.id = tcm.schoolid
      LEFT JOIN {course_categories} gradecat ON gradecat.id = tcm.gradeid
           {$schooljoin}
           {$sessionjoin}
           {$activityjoin}
          WHERE {$where}
       GROUP BY u.id, u.firstname, u.lastname, u.email {$activitygroup}
       ORDER BY todaysubmitted DESC, sessionsscheduled DESC, u.firstname ASC, u.lastname ASC",
        $params
    );

    $rows = [];
    $filterstatus = strtolower((string) ($filters['status'] ?? ''));
    foreach ($records as $record) {
        $todaysubmitted = (int) $record->todaysubmitted;
        $sessions = (int) $record->sessionsscheduled;
        if ($todaysubmitted > 0) {
            $status = 'active';
            $statuslabel = 'Active';
        } else if ($sessions > 0) {
            $status = 'pending';
            $statuslabel = 'Pending';
        } else {
            $status = 'inactive';
            $statuslabel = 'Inactive';
        }

        if ($filterstatus !== '' && $filterstatus !== $status) {
            continue;
        }

        $rows[] = [
            'trainer_name' => format_string(fullname($record)),
            'school_name' => format_string($record->schoolnames ?: '-'),
            'assigned_grades' => 'All Grades',
            'attendance_status' => $statuslabel,
            'attendance_status_class' => 'is-' . $status,
            'sessions_scheduled_today' => local_dashboard_format_count($sessions),
            'last_activity' => !empty($record->lastactivity) ? local_dashboard_relative_time((int) $record->lastactivity) : '-',
            'attendance_submitted_time' => $todaysubmitted > 0 ? userdate($todaysubmitted, '%I:%M %p') : '-',
        ];
    }

    return $rows;
}

/**
 * Get school filter options for trainer activity.
 *
 * @param int $selectedschoolid
 * @return array
 */
function local_dashboard_get_trainer_activity_school_options(int $selectedschoolid = 0, array $scope = []): array {
    global $DB;

    $scope = local_dashboard_normalize_school_scope($scope);
    if (!$DB->get_manager()->table_exists('trainer_course_mapping')) {
        return [];
    }

    $schooljoin = $DB->get_manager()->table_exists('school') ?
        "LEFT JOIN {school} sc ON sc.course_cat_id = tcm.schoolid" :
        "";
    $schoolfield = $DB->get_manager()->table_exists('school') ?
        "COALESCE(sc.school_name, cc.name)" :
        "cc.name";

    $where = "tcm.status = 1
            AND tcm.schoolid IS NOT NULL
            AND tcm.schoolid <> 0
            AND u.deleted = 0
            AND u.suspended = 0";
    $params = [];
    local_dashboard_apply_school_scope($where, $params, $scope, 'tcm', 'schoolid');

    $records = $DB->get_records_sql(
        "SELECT tcm.schoolid AS id,
                {$schoolfield} AS schoolname
           FROM {trainer_course_mapping} tcm
           JOIN {user} u ON u.id = tcm.traineruserid
      LEFT JOIN {course_categories} cc ON cc.id = tcm.schoolid
           {$schooljoin}
          WHERE {$where}
       GROUP BY tcm.schoolid, {$schoolfield}
       ORDER BY {$schoolfield} ASC",
        $params
    );

    $options = [];
    foreach ($records as $record) {
        $options[] = [
            'id' => (int) $record->id,
            'school_name' => format_string($record->schoolname ?: '-'),
            'selected' => (int) $record->id === $selectedschoolid ? 'selected' : '',
        ];
    }

    return $options;
}

/**
 * Resolve weekday names as stored by the timetable plugin.
 *
 * @param int $timestamp
 * @return string
 */
function local_dashboard_get_weekday_name(int $timestamp): string {
    return userdate($timestamp, '%A');
}

/**
 * Build POC or ARM listing data for the dashboard modal.
 *
 * @return array
 */
function local_dashboard_get_poc_context(array $scope = []): array {
    global $DB;

    $scope = local_dashboard_normalize_school_scope($scope);
    $dbman = $DB->get_manager();
    if (empty($scope['is_school_scoped'])) {
        if (!$dbman->table_exists('poc')) {
            return [
                'poc_list' => [],
                'has_poc_list' => false,
                'poc_total_count' => 0,
            ];
        }

        $schoolcountjoin = '';
        $schoolcountfield = '0 AS assignedschools';
        if ($dbman->table_exists('schoolassign')) {
            $schoolcountfield = 'COALESCE(schools.assignedschools, 0) AS assignedschools';
            $schoolcountjoin = "LEFT JOIN (
                    SELECT sa.userid,
                           COUNT(DISTINCT sa.schoolid) AS assignedschools
                      FROM {schoolassign} sa
                  GROUP BY sa.userid
                    ) schools ON schools.userid = p.userid";
        }

        $records = $DB->get_records_sql(
            "SELECT p.userid AS id,
                    p.poc_id,
                    p.firstname,
                    p.lastname,
                    p.email,
                    p.contact_number,
                    p.designation,
                    p.suspended,
                    $schoolcountfield
               FROM {poc} p
               JOIN {user} u ON u.id = p.userid
         $schoolcountjoin
              WHERE u.deleted = 0
                AND u.suspended = 0
           ORDER BY p.id DESC"
        );

        $pocs = [];
        foreach ($records as $record) {
            $fullname = fullname((object) [
                'firstname' => $record->firstname,
                'lastname' => $record->lastname,
            ]);

            $pocs[] = [
                'poc_id' => $record->poc_id ?: '-',
                'poc_name' => format_string($fullname ?: '-'),
                'poc_email' => $record->email ?: '-',
                'poc_contact' => $record->contact_number ?: '-',
                'poc_designation' => format_string($record->designation ?: '-'),
                'poc_assigned_schools' => local_dashboard_format_count((int) $record->assignedschools),
                'poc_status' => !empty($record->suspended) ? 'Suspended' : 'Active',
            ];
        }

        return [
            'poc_list' => $pocs,
            'has_poc_list' => !empty($pocs),
            'poc_total_count' => count($pocs),
        ];
    }

    if (!$dbman->table_exists('regionalpoc')) {
        return [
            'poc_list' => [],
            'has_poc_list' => false,
            'poc_total_count' => 0,
        ];
    }

    $schoolcountjoin = '';
    $schoolcountfield = '0 AS assignedschools';
    if ($dbman->table_exists('regionalpoc_arm_school')) {
        $schoolcountfield = 'COALESCE(schools.assignedschools, 0) AS assignedschools';
        $schoolcountjoin = "LEFT JOIN (
                SELECT ras.userid,
                       COUNT(DISTINCT ras.schoolid) AS assignedschools
                  FROM {regionalpoc_arm_school} ras
              GROUP BY ras.userid
                ) schools ON schools.userid = rp.userid";
    } else if ($dbman->table_exists('schoolassign')) {
        $schoolcountwhere = '';
        $schoolcountparams = [];
        if (!empty($scope['is_school_scoped'])) {
            if (local_dashboard_scope_is_empty($scope)) {
                $schoolcountwhere = ' WHERE 1 = 0';
            } else {
                list($schoolcountsql, $schoolcountparams) = $DB->get_in_or_equal(
                    $scope['schoolids'],
                    SQL_PARAMS_NAMED,
                    'dashpoccount'
                );
                $schoolcountwhere = " WHERE sa.schoolid {$schoolcountsql}";
            }
        }

        $schoolcountfield = 'COALESCE(schools.assignedschools, 0) AS assignedschools';
        $schoolcountjoin = "LEFT JOIN (
                SELECT sa.userid,
                       COUNT(DISTINCT sa.schoolid) AS assignedschools
                  FROM {schoolassign} sa
                  {$schoolcountwhere}
              GROUP BY sa.userid
                ) schools ON schools.userid = rp.userid";
    }

    $where = "u.deleted = 0
            AND u.suspended = 0
            AND rp.usertype = :armusertype";
    $params = $schoolcountparams ?? [];
    $params['armusertype'] = 'asstmanager';
    if (!empty($scope['is_school_scoped'])) {
        if (empty($scope['regional_manager_userid'])) {
            $where .= ' AND 1 = 0';
        } else {
            $where .= ' AND rp.pocid = :regionalmanagerid';
            $params['regionalmanagerid'] = $scope['regional_manager_userid'];
        }
    }

    $records = $DB->get_records_sql(
        "SELECT rp.userid AS id,
                rp.username,
                rp.firstname,
                rp.lastname,
                rp.email,
                rp.contact_number,
                rp.designation,
                u.suspended,
                $schoolcountfield
           FROM {regionalpoc} rp
           JOIN {user} u ON u.id = rp.userid
     $schoolcountjoin
          WHERE {$where}
       ORDER BY rp.id DESC",
        $params
    );

    $pocs = [];
    foreach ($records as $record) {
        $fullname = fullname((object) [
            'firstname' => $record->firstname,
            'lastname' => $record->lastname,
        ]);

        $pocs[] = [
            'poc_id' => $record->username ?: '-',
            'poc_name' => format_string($fullname ?: '-'),
            'poc_email' => $record->email ?: '-',
            'poc_contact' => $record->contact_number ?: '-',
            'poc_designation' => format_string($record->designation ?: '-'),
            'poc_assigned_schools' => local_dashboard_format_count((int) $record->assignedschools),
            'poc_status' => !empty($record->suspended) ? 'Suspended' : 'Active',
        ];
    }

    return [
        'poc_list' => $pocs,
        'has_poc_list' => !empty($pocs),
        'poc_total_count' => count($pocs),
    ];
}

/**
 * Format dashboard counts for card display.
 *
 * @param int $value
 * @return string
 */
function local_dashboard_format_count(int $value): string {
    return number_format($value);
}

/**
 * Format a signed count for KPI change chips.
 *
 * @param int $value
 * @return string
 */
function local_dashboard_format_signed_count(int $value): string {
    return ($value >= 0 ? '+' : '') . local_dashboard_format_count($value);
}

/**
 * Format a percentage value for card display.
 *
 * @param int|float $value
 * @return string
 */
function local_dashboard_format_percent($value): string {
    return local_dashboard_format_percent_number((float) $value) . '%';
}

/**
 * Format a signed percentage value for KPI change chips.
 *
 * @param int|float $value
 * @return string
 */
function local_dashboard_format_signed_percent($value): string {
    $value = (float) $value;
    $prefix = $value > 0 ? '+' : '';

    return $prefix . local_dashboard_format_percent_number($value) . '%';
}

/**
 * Round percentage values consistently for dashboard output.
 *
 * @param float $value
 * @return float
 */
function local_dashboard_round_percent(float $value): float {
    return round($value, 2);
}

/**
 * Format a percentage number without hiding small non-zero values.
 *
 * @param float $value
 * @return string
 */
function local_dashboard_format_percent_number(float $value): string {
    $rounded = local_dashboard_normalize_percent_value(local_dashboard_round_percent($value));

    if (abs($rounded) > 0 && abs($rounded) < 1) {
        return number_format($rounded, 2);
    }

    if (floor($rounded) != $rounded) {
        return rtrim(rtrim(number_format($rounded, 2), '0'), '.');
    }

    return number_format($rounded);
}

/**
 * Normalize rounded percentages before JSON encoding or string formatting.
 *
 * @param float $value
 * @return float
 */
function local_dashboard_normalize_percent_value(float $value): float {
    return (float) number_format($value, 2, '.', '');
}

/**
 * Format a trend percentage for JSON without PHP float precision noise.
 *
 * @param float $value
 * @return string
 */
function local_dashboard_format_trend_percent(float $value): string {
    return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
}

/**
 * Insert or refresh a meaningful operational dashboard activity.
 *
 * @param string $activitytype
 * @param string $title
 * @param string $description
 * @param int $schoolid
 * @param array $options
 * @return int
 */
function local_dashboard_log_activity(
    string $activitytype,
    string $title,
    string $description,
    int $schoolid = 0,
    array $options = []
): int {
    global $DB, $USER;

    if (!$DB->get_manager()->table_exists('local_dashboard_activity_logs')) {
        return 0;
    }

    $allowedtypes = [
        'student_added',
        'bulk_student_import',
        'attendance_submitted',
        'trainer_assigned',
        'school_added',
        'course_assigned',
        'course_created',
        'course_deleted',
        'poc_added',
        'student_deleted',
        'timetable_updated',
        'session_scheduled',
    ];
    if (!in_array($activitytype, $allowedtypes, true)) {
        return 0;
    }

    $metadata = $options['metadata'] ?? [];
    if (!is_array($metadata)) {
        $metadata = ['value' => $metadata];
    }

    $schoolname = $options['schoolname'] ?? local_dashboard_get_school_name($schoolid);
    $actorid = isset($options['actorid']) ? (int) $options['actorid'] : (int) ($USER->id ?? 0);
    $actorname = $options['actorname'] ?? local_dashboard_get_actor_name($actorid);
    $countvalue = isset($options['countvalue']) ? (int) $options['countvalue'] : 0;
    $timecreated = isset($options['timecreated']) ? (int) $options['timecreated'] : time();
    $metadatajson = json_encode($metadata);
    if ($metadatajson === false) {
        $metadatajson = '{}';
    }

    $dedupekey = $options['dedupekey'] ?? sha1($activitytype . '|' . $schoolid . '|' . $metadatajson);

    $record = new stdClass();
    $record->activitytype = clean_param($activitytype, PARAM_ALPHANUMEXT);
    $record->title = clean_param($title, PARAM_TEXT);
    $record->description = clean_param($description, PARAM_TEXT);
    $record->schoolid = $schoolid;
    $record->schoolname = clean_param($schoolname, PARAM_TEXT);
    $record->actorid = $actorid;
    $record->actorname = clean_param($actorname, PARAM_TEXT);
    $record->countvalue = $countvalue;
    $record->metadata = $metadatajson;
    $record->dedupekey = $dedupekey;
    $record->timecreated = $timecreated;

    if ($existing = $DB->get_record('local_dashboard_activity_logs', ['dedupekey' => $dedupekey], '*', IGNORE_MULTIPLE)) {
        $record->id = $existing->id;
        $DB->update_record('local_dashboard_activity_logs', $record);
        return (int) $existing->id;
    }

    $id = (int) $DB->insert_record('local_dashboard_activity_logs', $record);
    local_dashboard_cleanup_activity_logs(1000);

    return $id;
}

/**
 * Get recent activity cards for the admin dashboard.
 *
 * @param int $limit
 * @return array
 */
function local_dashboard_get_recent_activities_context(int $limit = 15, array $scope = []): array {
    global $DB;

    $scope = local_dashboard_normalize_school_scope($scope);
    if (!$DB->get_manager()->table_exists('local_dashboard_activity_logs')) {
        return [
            'recent_activities' => [],
            'has_recent_activities' => false,
        ];
    }

    $where = '1 = 1';
    $params = [];
    if (!empty($scope['is_school_scoped'])) {
        if (local_dashboard_scope_is_empty($scope)) {
            $where .= ' AND 1 = 0';
        } else {
            list($insql, $inparams) = $DB->get_in_or_equal($scope['schoolids'], SQL_PARAMS_NAMED, 'dashactivity');
            $where .= " AND schoolid {$insql}";
            $params += $inparams;
        }
    }

    $records = $DB->get_records_select(
        'local_dashboard_activity_logs',
        $where,
        $params,
        'timecreated DESC, id DESC',
        '*',
        0,
        $limit
    );

    $activities = [];
    foreach ($records as $record) {
        $activities[] = [
            'icon' => local_dashboard_activity_icon($record->activitytype),
            'iconclass' => local_dashboard_activity_icon_class($record->activitytype),
            'title' => format_string($record->title),
            'description' => format_text($record->description, FORMAT_PLAIN, ['filter' => false]),
            'schoolname' => format_string($record->schoolname ?: 'LMS'),
            'timeago' => local_dashboard_relative_time((int) $record->timecreated),
            'has_countvalue' => (int) $record->countvalue > 0,
            'countvalue' => local_dashboard_format_count((int) $record->countvalue),
        ];
    }

    return [
        'recent_activities' => $activities,
        'has_recent_activities' => !empty($activities),
    ];
}

/**
 * Resolve school display name from either a school table id or category id.
 *
 * @param int $schoolid
 * @return string
 */
function local_dashboard_get_school_name(int $schoolid): string {
    global $DB;

    if (empty($schoolid)) {
        return '';
    }

    $dbman = $DB->get_manager();
    if ($dbman->table_exists('school')) {
        $record = $DB->get_record_sql(
            "SELECT school_name
               FROM {school}
              WHERE id = :id OR course_cat_id = :catid
           ORDER BY CASE WHEN course_cat_id = :sortcatid THEN 0 ELSE 1 END",
            ['id' => $schoolid, 'catid' => $schoolid, 'sortcatid' => $schoolid],
            IGNORE_MULTIPLE
        );
        if ($record && !empty($record->school_name)) {
            return $record->school_name;
        }
    }

    if ($dbman->table_exists('course_categories')) {
        return (string) $DB->get_field('course_categories', 'name', ['id' => $schoolid]);
    }

    return '';
}

/**
 * Resolve actor name safely.
 *
 * @param int $actorid
 * @return string
 */
function local_dashboard_get_actor_name(int $actorid): string {
    global $DB;

    if (empty($actorid)) {
        return '';
    }

    $user = $DB->get_record('user', ['id' => $actorid], 'id, firstname, lastname', IGNORE_MISSING);
    return $user ? fullname($user) : '';
}

/**
 * Resolve a grade/category display name.
 *
 * @param int $gradeid
 * @return string
 */
function local_dashboard_get_grade_name(int $gradeid): string {
    global $DB;

    if (empty($gradeid) || !$DB->get_manager()->table_exists('course_categories')) {
        return '';
    }

    return (string) $DB->get_field('course_categories', 'name', ['id' => $gradeid]);
}

/**
 * Keep the operational feed bounded.
 *
 * @param int $limit
 * @return void
 */
function local_dashboard_cleanup_activity_logs(int $limit = 1000): void {
    global $DB;

    $total = $DB->count_records('local_dashboard_activity_logs');
    if ($total <= $limit) {
        return;
    }

    $offset = max($limit - 1, 0);
    $keeper = $DB->get_records(
        'local_dashboard_activity_logs',
        null,
        'timecreated DESC, id DESC',
        'id',
        $offset,
        1
    );
    $keeper = reset($keeper);
    if ($keeper) {
        $DB->delete_records_select('local_dashboard_activity_logs', 'id < :id', ['id' => $keeper->id]);
    }
}

/**
 * Map activity types to Font Awesome icon classes.
 *
 * @param string $activitytype
 * @return string
 */
function local_dashboard_activity_icon(string $activitytype): string {
    $icons = [
        'student_added' => 'fa-solid fa-user-graduate',
        'bulk_student_import' => 'fa-solid fa-users',
        'attendance_submitted' => 'fa-solid fa-clipboard-check',
        'trainer_assigned' => 'fa-solid fa-chalkboard-user',
        'school_added' => 'fa-solid fa-school',
        'course_assigned' => 'fa-solid fa-book-open',
        'course_created' => 'fa-solid fa-circle-plus',
        'course_deleted' => 'fa-solid fa-trash-can',
        'poc_added' => 'fa-solid fa-user-shield',
        'student_deleted' => 'fa-solid fa-user-minus',
        'timetable_updated' => 'fa-solid fa-calendar-days',
        'session_scheduled' => 'fa-solid fa-calendar-plus',
    ];

    return $icons[$activitytype] ?? 'fa-solid fa-bell';
}

/**
 * Map activity types to existing dashboard color classes.
 *
 * @param string $activitytype
 * @return string
 */
function local_dashboard_activity_icon_class(string $activitytype): string {
    $classes = [
        'student_added' => 'aero-bg-amber',
        'bulk_student_import' => 'aero-bg-amber',
        'attendance_submitted' => 'aero-bg-indigo',
        'trainer_assigned' => 'aero-bg-blue',
        'school_added' => 'aero-bg-green',
        'course_assigned' => 'aero-bg-indigo',
        'course_created' => 'aero-bg-green',
        'course_deleted' => 'aero-bg-rose',
        'poc_added' => 'aero-bg-blue',
        'student_deleted' => 'aero-bg-rose',
        'timetable_updated' => 'aero-bg-rose',
        'session_scheduled' => 'aero-bg-blue',
    ];

    return $classes[$activitytype] ?? 'aero-bg-blue';
}

/**
 * Format a compact relative time label.
 *
 * @param int $timestamp
 * @return string
 */
function local_dashboard_relative_time(int $timestamp): string {
    if (empty($timestamp)) {
        return '';
    }

    $diff = time() - $timestamp;
    if ($diff < MINSECS) {
        return 'Just now';
    }
    if ($diff < HOURSECS) {
        $mins = max(1, (int) floor($diff / MINSECS));
        return $mins . ' min' . ($mins === 1 ? '' : 's') . ' ago';
    }
    if ($diff < DAYSECS) {
        $hours = max(1, (int) floor($diff / HOURSECS));
        return $hours . ' hr' . ($hours === 1 ? '' : 's') . ' ago';
    }
    if ($diff < (2 * DAYSECS)) {
        return 'Yesterday';
    }

    return userdate($timestamp, '%d %b %Y');
}
