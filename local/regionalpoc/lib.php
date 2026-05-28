<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Return the schools a Regional Manager can delegate to an ARM.
 *
 * @param int $userid
 * @return array
 */
function local_regionalpoc_get_assignable_school_options(int $userid): array {
    global $DB;

    $schoolids = $DB->get_fieldset_select('schoolassign', 'schoolid', 'userid = ?', [$userid]);
    $schoolids = array_values(array_unique(array_filter(array_map('intval', $schoolids))));
    if (empty($schoolids)) {
        return [];
    }

    list($insql, $params) = $DB->get_in_or_equal($schoolids, SQL_PARAMS_NAMED, 'rpschool');

    return $DB->get_records_sql_menu(
        "SELECT cc.id, COALESCE(sc.school_name, cc.name) AS schoolname
           FROM {course_categories} cc
      LEFT JOIN {school} sc ON sc.course_cat_id = cc.id OR sc.school_id = cc.idnumber
          WHERE cc.id {$insql}
       ORDER BY schoolname ASC",
        $params
    );
}

/**
 * Whether the user has the ARM role or an ARM regionalpoc record.
 *
 * @param int $userid
 * @return bool
 */
function local_regionalpoc_is_arm_user(int $userid): bool {
    global $DB;

    if (is_siteadmin($userid)) {
        return false;
    }

    return $DB->record_exists_sql(
        "SELECT 1
           FROM {role_assignments} ra
           JOIN {role} r ON r.id = ra.roleid
          WHERE ra.userid = :userid
            AND r.shortname = :roleshortname",
        ['userid' => $userid, 'roleshortname' => 'arm']
    ) || $DB->record_exists('regionalpoc', ['userid' => $userid, 'usertype' => 'asstmanager']);
}

/**
 * Whether the user can manage Assistant Regional Managers.
 *
 * @param int $userid
 * @return bool
 */
function local_regionalpoc_is_regional_manager_user(int $userid): bool {
    global $DB;

    if (is_siteadmin($userid) || local_regionalpoc_is_arm_user($userid)) {
        return false;
    }

    return $DB->record_exists_sql(
        "SELECT 1
           FROM {role_assignments} ra
           JOIN {role} r ON r.id = ra.roleid
          WHERE ra.userid = :userid
            AND r.shortname = :roleshortname",
        ['userid' => $userid, 'roleshortname' => 'pocschool']
    ) || ($DB->record_exists('poc', ['userid' => $userid]) &&
        $DB->record_exists('schoolassign', ['userid' => $userid]));
}

/**
 * Require Regional Manager access for ARM management pages.
 */
function local_regionalpoc_require_regional_manager(): void {
    global $USER;

    if (!local_regionalpoc_is_regional_manager_user((int) $USER->id)) {
        throw new required_capability_exception(context_system::instance(), 'local/pocschool:view', 'nopermissions', '');
    }
}

/**
 * Temporary ARM account email-flow diagnostics.
 *
 * Logs only metadata needed to identify whether account creation reaches an
 * email-sending call. Password values are never logged.
 *
 * @param string $stage
 * @param array $metadata
 */
function local_regionalpoc_arm_email_debug_log(string $stage, array $metadata = []): void {
    foreach ($metadata as $key => $value) {
        if (is_array($value)) {
            $metadata[$key] = array_values($value);
        }
    }

    error_log('[local_regionalpoc][arm_email_debug] ' . json_encode([
        'stage' => $stage,
        'metadata' => $metadata,
        'time' => time(),
    ]));
}

/**
 * Get ARM school assignments from the dedicated hierarchy mapping table.
 *
 * Falls back to the legacy schoolassign rows so existing data still scopes safely.
 *
 * @param int $userid
 * @return int[]
 */
function local_regionalpoc_get_arm_school_ids(int $userid): array {
    global $DB;

    $schoolids = [];
    if ($DB->get_manager()->table_exists('regionalpoc_arm_school')) {
        $schoolids = $DB->get_fieldset_select('regionalpoc_arm_school', 'schoolid', 'userid = ?', [$userid]);
    }

    if (empty($schoolids)) {
        $schoolids = $DB->get_fieldset_select('schoolassign', 'schoolid', 'userid = ?', [$userid]);
    }

    return array_values(array_unique(array_filter(array_map('intval', $schoolids))));
}

/**
 * Save ARM school assignments in the hierarchy table and legacy scope table.
 *
 * @param int $userid
 * @param int[] $schoolids
 * @param int $assignedby
 */
function local_regionalpoc_save_arm_school_assignments(int $userid, array $schoolids, int $assignedby): void {
    global $DB;

    $schoolids = array_values(array_unique(array_filter(array_map('intval', $schoolids))));
    $now = time();

    if ($DB->get_manager()->table_exists('regionalpoc_arm_school')) {
        $DB->delete_records('regionalpoc_arm_school', ['userid' => $userid]);
        foreach ($schoolids as $schoolid) {
            $record = (object) [
                'userid' => $userid,
                'schoolid' => $schoolid,
                'assignedby' => $assignedby,
                'timecreated' => $now,
            ];
            $DB->insert_record('regionalpoc_arm_school', $record);
        }
    }

    $DB->delete_records('schoolassign', ['userid' => $userid]);
    $columns = $DB->get_columns('schoolassign');
    foreach ($schoolids as $schoolid) {
        $record = (object) [
            'schoolid' => $schoolid,
            'userid' => $userid,
            'status' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        if (array_key_exists('schoolassignee', $columns)) {
            $record->schoolassignee = $assignedby;
        }
        if (array_key_exists('schoolassignedto', $columns)) {
            $record->schoolassignedto = $userid;
        }
        if (array_key_exists('schoolassignby', $columns)) {
            $record->schoolassignby = $assignedby;
        }

        $DB->insert_record('schoolassign', $record);
    }

    local_regionalpoc_sync_arm_course_enrolments($userid);
}

/**
 * Enrol ARM users into copied courses for their assigned schools.
 *
 * This is what makes the courses appear in /my/courses.php and allows
 * /course/view.php access, while stale course access is removed when schools
 * are reassigned.
 *
 * @param int $userid
 * @return array
 */
function local_regionalpoc_sync_arm_course_enrolments(int $userid): array {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/enrol/manual/lib.php');

    $summary = ['eligible' => 0, 'enrolled' => 0, 'removed' => 0, 'skipped' => 0];
    $regionalpoc = $DB->get_record('regionalpoc', [
        'userid' => $userid,
        'usertype' => 'asstmanager',
    ], 'id, userid, pocid', IGNORE_MISSING);
    if (!$regionalpoc || empty($regionalpoc->pocid)) {
        return $summary;
    }

    $manualplugin = enrol_get_plugin('manual');
    $courserole = $DB->get_record('role', ['shortname' => 'pocschool']);
    if (!$manualplugin || !$courserole) {
        return $summary;
    }

    $schoolids = local_regionalpoc_get_arm_school_ids($userid);
    $eligiblecourseids = [];
    if (!empty($schoolids)) {
        list($schoolsql, $schoolparams) = $DB->get_in_or_equal($schoolids, SQL_PARAMS_NAMED, 'armcoursesyncschool');
        $courses = $DB->get_records_sql(
            "SELECT DISTINCT c.id
               FROM {poc_copy_course} pcc
               JOIN {course} c ON c.id = pcc.courseid
              WHERE pcc.pocid = :pocid
                AND pcc.schoolid {$schoolsql}
                AND pcc.status = 1
                AND c.visible = 1",
            ['pocid' => (int) $regionalpoc->pocid] + $schoolparams
        );
        $eligiblecourseids = array_map('intval', array_keys($courses));
    }

    $summary['eligible'] = count($eligiblecourseids);
    foreach ($eligiblecourseids as $courseid) {
        $manualinstance = $DB->get_record('enrol', [
            'courseid' => $courseid,
            'enrol' => 'manual',
            'status' => ENROL_INSTANCE_ENABLED,
        ]);
        if (!$manualinstance) {
            $summary['skipped']++;
            continue;
        }

        $coursecontext = context_course::instance($courseid, IGNORE_MISSING);
        if (!$coursecontext) {
            $summary['skipped']++;
            continue;
        }

        if (!is_enrolled($coursecontext, $userid)) {
            $manualplugin->enrol_user($manualinstance, $userid, $courserole->id, time());
            $summary['enrolled']++;
        } else if (!$DB->record_exists('role_assignments', [
            'roleid' => $courserole->id,
            'contextid' => $coursecontext->id,
            'userid' => $userid,
        ])) {
            role_assign($courserole->id, $userid, $coursecontext->id);
        }
    }

    $parentcourseids = $DB->get_fieldset_select(
        'poc_copy_course',
        'DISTINCT courseid',
        'pocid = ? AND status = 1',
        [(int) $regionalpoc->pocid]
    );
    $eligiblelookup = array_flip($eligiblecourseids);
    foreach (array_map('intval', $parentcourseids) as $courseid) {
        if (isset($eligiblelookup[$courseid])) {
            continue;
        }

        $manualinstance = $DB->get_record('enrol', [
            'courseid' => $courseid,
            'enrol' => 'manual',
            'status' => ENROL_INSTANCE_ENABLED,
        ]);
        $coursecontext = context_course::instance($courseid, IGNORE_MISSING);
        if (!$manualinstance || !$coursecontext) {
            continue;
        }

        if ($DB->record_exists('role_assignments', [
            'roleid' => $courserole->id,
            'contextid' => $coursecontext->id,
            'userid' => $userid,
        ])) {
            role_unassign($courserole->id, $userid, $coursecontext->id);
            if (is_enrolled($coursecontext, $userid)) {
                $manualplugin->unenrol_user($manualinstance, $userid);
            }
            $summary['removed']++;
        }
    }

    return $summary;
}

/**
 * Copy system-level permissions from pocschool to arm so ARM can reuse modules.
 */
function local_regionalpoc_sync_arm_role_capabilities(): void {
    global $DB;

    $pocrole = $DB->get_record('role', ['shortname' => 'pocschool']);
    $armrole = $DB->get_record('role', ['shortname' => 'arm']);
    if (!$pocrole || !$armrole) {
        return;
    }

    $systemcontext = context_system::instance();
    $capabilities = $DB->get_records('role_capabilities', [
        'roleid' => $pocrole->id,
        'contextid' => $systemcontext->id,
    ]);

    foreach ($capabilities as $capability) {
        assign_capability(
            $capability->capability,
            (int) $capability->permission,
            (int) $armrole->id,
            $systemcontext->id,
            true
        );
    }
}
