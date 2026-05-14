<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/enrol/manual/lib.php');

function local_students_get_approval_course_ids(stdClass $student, ?int $approverid = null): array {
    global $DB, $USER;

    if ($approverid === null) {
        $approverid = $USER->id;
    }

    $courseids = [];
    if (!empty($student->courseid)) {
        $courseids[] = (int)$student->courseid;
    }

    if (!empty($student->schoolid) && !empty($student->gradeid)) {
        $params = [
            'schoolid' => $student->schoolid,
            'gradeid' => $student->gradeid,
            'status' => 1,
        ];
        $pocfilter = '';

        if (!is_siteadmin($approverid)) {
            $pocparams = $params + ['pocid' => $approverid];
            if ($DB->record_exists('poc_copy_course', $pocparams)) {
                $pocfilter = ' AND pocid = :pocid';
                $params['pocid'] = $approverid;
            }
        }

        $mappedcourseids = $DB->get_fieldset_sql(
            "SELECT DISTINCT courseid
               FROM {poc_copy_course}
              WHERE schoolid = :schoolid
                AND gradeid = :gradeid
                AND status = :status
                AND courseid IS NOT NULL
                AND courseid <> 0
                    {$pocfilter}",
            $params
        );
        $courseids = array_merge($courseids, array_map('intval', $mappedcourseids));
    }

    return array_values(array_unique(array_filter($courseids)));
}

function local_students_get_student_roleid(): int {
    global $DB;

    $roleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
    if (!$roleid) {
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'learner']);
    }

    return (int)$roleid;
}

function local_students_enrol_approved_student(int $userid, array $courseids): int {
    global $DB;

    $roleid = local_students_get_student_roleid();
    if (empty($roleid)) {
        return 0;
    }

    $enrolled = 0;
    foreach ($courseids as $courseid) {
        $courseid = (int)$courseid;
        if (empty($courseid)) {
            continue;
        }

        $coursecontext = context_course::instance($courseid, IGNORE_MISSING);
        if (!$coursecontext) {
            continue;
        }

        if (is_enrolled($coursecontext, $userid)) {
            $enrolled++;
            continue;
        }

        if (enrol_try_internal_enrol($courseid, $userid, $roleid, time())) {
            $enrolled++;
            continue;
        }

        $manualplugin = enrol_get_plugin('manual');
        $manualinstance = $DB->get_record('enrol', [
            'courseid' => $courseid,
            'enrol' => 'manual',
            'status' => ENROL_INSTANCE_ENABLED,
        ]);
        if ($manualplugin && $manualinstance) {
            $manualplugin->enrol_user($manualinstance, $userid, $roleid, time());
            $enrolled++;
        }
    }

    return $enrolled;
}

function local_students_approve_student(int $userid, string $approvedby, ?int $approverid = null): int {
    global $DB, $USER;

    if ($approverid === null) {
        $approverid = $USER->id;
    }

    $student = $DB->get_record('student', ['userid' => $userid], '*', MUST_EXIST);
    $DB->set_field('student', 'status', 1, ['userid' => $userid]);
    $DB->set_field('student', 'approvedby', $approvedby, ['userid' => $userid]);
    $DB->set_field('user', 'confirmed', 1, ['id' => $userid]);

    $courseids = local_students_get_approval_course_ids($student, $approverid);
    return local_students_enrol_approved_student($userid, $courseids);
}
