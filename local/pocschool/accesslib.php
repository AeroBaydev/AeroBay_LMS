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

    if (!$DB->get_manager()->table_exists('trainer_course_mapping')) {
        return [];
    }

    return array_map('intval', $DB->get_fieldset_select(
        'trainer_course_mapping',
        'DISTINCT gradeid',
        'traineruserid = ? AND status = 1 AND gradeid IS NOT NULL AND gradeid <> 0',
        [$userid]
    ));
}

function local_pocschool_get_trainer_course_ids($userid = null) {
    global $DB, $USER;

    if ($userid === null) {
        $userid = $USER->id;
    }

    if (!$DB->get_manager()->table_exists('trainer_course_mapping')) {
        return [];
    }

    return array_map('intval', $DB->get_fieldset_select(
        'trainer_course_mapping',
        'DISTINCT courseid',
        'traineruserid = ? AND status = 1 AND courseid IS NOT NULL AND courseid <> 0',
        [$userid]
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

    if (!empty($gradeid) && local_pocschool_is_trainer_user($userid)) {
        $gradeids = local_pocschool_get_trainer_grade_ids($userid);
        if (empty($gradeids) || !in_array((int)$gradeid, $gradeids, true)) {
            throw new required_capability_exception(context_system::instance(), 'local/pocschool:view', 'nopermissions', '');
        }
    }
}

function local_pocschool_apply_school_filter(&$from, &$where, array &$params, $schoolalias = 'cc', $schoolfield = 'id') {
    global $USER;

    if (!local_pocschool_is_poc_user() && !local_pocschool_is_trainer_user()) {
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

    $gradeids = local_pocschool_get_trainer_grade_ids();
    if (empty($gradeids)) {
        $where .= " AND 1 = 0";
        return;
    }

    list($gradesql, $gradeparams) = $DB->get_in_or_equal($gradeids, SQL_PARAMS_NAMED, 'trainergrade');
    $where .= " AND {$gradealias}.{$gradefield} {$gradesql}";
    $params += $gradeparams;
}

function local_pocschool_apply_trainer_student_filter(&$where, array &$params, $studentalias = 'st') {
    global $DB;

    if (!local_pocschool_is_trainer_user()) {
        return;
    }

    $gradeids = local_pocschool_get_trainer_grade_ids();
    if (empty($gradeids)) {
        $where .= " AND 1 = 0";
        return;
    }

    $prefix = $studentalias !== '' ? "{$studentalias}." : '';

    list($gradesql, $gradeparams) = $DB->get_in_or_equal($gradeids, SQL_PARAMS_NAMED, 'trainerstudentgrade');
    $where .= " AND {$prefix}gradeid {$gradesql}";
    $params += $gradeparams;

    $courseids = local_pocschool_get_trainer_course_ids();
    if (!empty($courseids)) {
        list($coursesql, $courseparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'trainerstudentcourse');
        $where .= " AND ({$prefix}courseid {$coursesql} OR {$prefix}courseid IS NULL OR {$prefix}courseid = 0)";
        $params += $courseparams;
    }
}
