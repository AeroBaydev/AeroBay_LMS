<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/regionalpoc/lib.php');

function local_pocschool_is_poc_user($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    return !is_siteadmin($userid) &&
        ($DB->record_exists('poc', ['userid' => $userid]) || local_regionalpoc_is_arm_user((int) $userid));
}

function local_pocschool_is_trainer_user($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    return !is_siteadmin($userid) && $DB->record_exists('trainer', ['userid' => $userid]);
}

function local_pocschool_get_assigned_school_ids($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    if (local_pocschool_is_trainer_user((int) $userid)) {
        // trainer school filter
        $schoolid = (int) $DB->get_field('trainer', 'schoolid', ['userid' => $userid]);
        return !empty($schoolid) ? [$schoolid] : [];
    }

    if (local_regionalpoc_is_arm_user((int) $userid)) {
        return local_regionalpoc_get_arm_school_ids((int) $userid);
    }

    return array_map('intval', $DB->get_fieldset_select('schoolassign', 'schoolid', 'userid = ?', [$userid]));
}

function local_pocschool_get_effective_poc_userid($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    if (local_pocschool_is_trainer_user((int) $userid)) {
        // assigned school visibility
        $schoolid = (int) $DB->get_field('trainer', 'schoolid', ['userid' => $userid]);
        if (!empty($schoolid)) {
            $pocid = $DB->get_field_sql(
                "SELECT sa.userid
                   FROM {schoolassign} sa
                   JOIN {poc} p ON p.userid = sa.userid
                  WHERE sa.schoolid = :schoolid",
                ['schoolid' => $schoolid],
                IGNORE_MULTIPLE
            );
            if (!empty($pocid)) {
                return (int) $pocid;
            }
        }
    }

    if (local_regionalpoc_is_arm_user((int) $userid)) {
        $pocid = $DB->get_field('regionalpoc', 'pocid', [
            'userid' => $userid,
            'usertype' => 'asstmanager',
        ]);
        if (!empty($pocid)) {
            return (int) $pocid;
        }
    }

    return (int) $userid;
}

function local_pocschool_get_trainer_grade_ids($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $schoolid = (int) $DB->get_field('trainer', 'schoolid', ['userid' => $userid]);
    if (empty($schoolid)) {
        return [];
    }

    // removed trainer grade dependency
    return array_map('intval', $DB->get_fieldset_select(
        'course_categories',
        'id',
        'parent = ? AND visible = 1',
        [$schoolid]
    ));
}

function local_pocschool_get_trainer_course_ids($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    $schoolid = (int) $DB->get_field('trainer', 'schoolid', ['userid' => $userid]);
    if (empty($schoolid)) {
        return [];
    }

    // trainer visibility by school mapping
    return array_map('intval', $DB->get_fieldset_sql(
        "SELECT DISTINCT c.id
           FROM {course} c
      LEFT JOIN {course_categories} cc ON cc.id = c.category
      LEFT JOIN {course_categories} schoolcc ON schoolcc.id = :schoolid
          WHERE c.visible = 1
            AND c.id <> :siteid
            AND (
                c.category = :schoolcategory
                OR (
                    schoolcc.path IS NOT NULL
                    AND cc.path IS NOT NULL
                    AND cc.path LIKE " . $DB->sql_concat('schoolcc.path', "'/%'") . "
                )
            )",
        [
            'schoolid' => $schoolid,
            'siteid' => SITEID,
            'schoolcategory' => $schoolid,
        ]
    ));
}

function local_pocschool_user_can_access_school($schoolid, $userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    if (is_siteadmin($userid)) {
        return true;
    }

    if (!local_pocschool_is_poc_user($userid) && !local_pocschool_is_trainer_user($userid)) {
        return true;
    }

    if (local_pocschool_is_trainer_user($userid)) {
        // trainer school-only restriction
        $assignedschoolid = (int) $DB->get_field('trainer', 'schoolid', ['userid' => $userid]);
        return !empty($assignedschoolid) && (int) $assignedschoolid === (int) $schoolid;
    }

    return $DB->record_exists('schoolassign', ['userid' => $userid, 'schoolid' => $schoolid]);
}

function local_pocschool_require_school_access($schoolid, $userid = null) {
    if (!local_pocschool_user_can_access_school($schoolid, $userid)) {
        throw new required_capability_exception(context_system::instance(), 'local/pocschool:view', 'nopermissions', '');
    }
}

function local_pocschool_require_grade_access($schoolid, $gradeid, $userid = null) {
    global $DB;

    local_pocschool_require_school_access($schoolid, $userid);

    if (!empty($gradeid) && !$DB->record_exists('course_categories', ['id' => $gradeid, 'parent' => $schoolid])) {
        throw new required_capability_exception(context_system::instance(), 'local/pocschool:view', 'nopermissions', '');
    }

    // removed trainer grade dependency
}

function local_pocschool_apply_school_filter(&$from, &$where, array &$params, $schoolalias = 'cc', $schoolfield = 'id') {
    global $USER;

    if (!local_pocschool_is_poc_user() && !local_pocschool_is_trainer_user()) {
        return;
    }

    if (local_pocschool_is_trainer_user()) {
        // trainer visibility by school mapping
        $schoolids = local_pocschool_get_assigned_school_ids();
        if (empty($schoolids)) {
            $where .= " AND 1 = 0";
            return;
        }

        $where .= " AND {$schoolalias}.{$schoolfield} = :trainerassignedschoolid";
        $params['trainerassignedschoolid'] = reset($schoolids);
        return;
    }

    $from .= " JOIN {schoolassign} poc_sa ON poc_sa.schoolid = {$schoolalias}.{$schoolfield}
                AND poc_sa.userid = :pocschoolfilteruserid";
    $params['pocschoolfilteruserid'] = $USER->id;
}

function local_pocschool_apply_trainer_grade_filter(&$where, array &$params, $gradealias = 'cc', $gradefield = 'id') {
    global $DB;

    if (!local_pocschool_is_trainer_user()) {
        return;
    }

    // trainer school-only restriction
    $schoolids = local_pocschool_get_assigned_school_ids();
    if (empty($schoolids)) {
        $where .= " AND 1 = 0";
        return;
    }

    $where .= " AND {$gradealias}.parent = :trainergradeschoolid";
    $params['trainergradeschoolid'] = reset($schoolids);
}

function local_pocschool_apply_trainer_student_filter(&$where, array &$params, $studentalias = 'st') {
    if (!local_pocschool_is_trainer_user()) {
        return;
    }

    $schoolids = local_pocschool_get_assigned_school_ids();
    if (empty($schoolids)) {
        $where .= " AND 1 = 0";
        return;
    }

    $prefix = $studentalias !== '' ? "{$studentalias}." : '';

    // trainer school-only restriction
    $where .= " AND {$prefix}schoolid = :trainerstudentschoolid";
    $params['trainerstudentschoolid'] = reset($schoolids);
}
