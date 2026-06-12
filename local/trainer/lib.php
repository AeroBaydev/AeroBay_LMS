<?php
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');
require_once($CFG->dirroot . '/enrol/manual/lib.php');


function local_trainer_extend_navigation(global_navigation $navigation) {
    global $CFG, $PAGE;
    if (local_pocschool_is_trainer_user()) {
        return;
    }

    $url = is_siteadmin() ? '/local/trainer/index.php' : '/local/trainer/trainer_manage.php';

        $navigation->add(
            "Trainer Management",
            new moodle_url($CFG->wwwroot . $url),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_trainer',
            new pix_icon('i/user','')
        )->showinflatnavigation = true; 
      //  $PAGE->navigation->action="https://dev.icloudcampus.com/update/mydashboard/";
}

function local_trainer_get_school_course_ids($schoolid) {
    global $DB;

    $schoolid = (int) $schoolid;
    if (empty($schoolid)) {
        return [];
    }

    return array_map('intval', $DB->get_fieldset_sql(
        "SELECT DISTINCT c.id
           FROM {course} c
      LEFT JOIN {course_categories} cc ON cc.id = c.category
      LEFT JOIN {course_categories} schoolcat ON schoolcat.id = :schoolid
          WHERE c.visible = 1
            AND c.id <> :siteid
            AND (
                c.category = :schoolcategory
                OR (
                    schoolcat.path IS NOT NULL
                    AND cc.path IS NOT NULL
                    AND cc.path LIKE " . $DB->sql_concat('schoolcat.path', "'/%'") . "
                )
                OR EXISTS (
                    SELECT 1
                      FROM {poc_copy_course} pcc
                     WHERE pcc.schoolid = :pccschoolid
                       AND pcc.courseid = c.id
                )
            )",
        [
            'schoolid' => $schoolid,
            'siteid' => SITEID,
            'schoolcategory' => $schoolid,
            'pccschoolid' => $schoolid,
        ]
    ));
}

function local_trainer_enrol_in_course($courseid, $userid) {
    global $CFG, $DB;

    $courseid = (int) $courseid;
    $userid = (int) $userid;
    if (empty($courseid) || empty($userid)) {
        return false;
    }

    $coursecontext = context_course::instance($courseid, IGNORE_MISSING);
    if (!$coursecontext || is_enrolled($coursecontext, $userid)) {
        return true;
    }

    $manualplugin = enrol_get_plugin('manual');
    if (!$manualplugin) {
        return false;
    }

    $manualinstance = $DB->get_record('enrol', [
        'courseid' => $courseid,
        'enrol' => 'manual',
        'status' => ENROL_INSTANCE_ENABLED,
    ]);
    if (!$manualinstance) {
        return false;
    }

    $role = $DB->get_record('role', ['shortname' => 'trainer']);
    if (!$role) {
        $role = $DB->get_record('role', ['shortname' => 'teacher']);
    }
    if (!$role) {
        $role = $DB->get_record('role', ['shortname' => 'editingteacher']);
    }
    if (!$role) {
        return false;
    }

    $sendcoursewelcome = $manualinstance->customint1;
    $manualinstance->customint1 = ENROL_DO_NOT_SEND_EMAIL;
    $CFG->enrol_manual_sendcoursewelcomemessage = 0;
    try {
        $manualplugin->enrol_user($manualinstance, $userid, $role->id, time());
    } finally {
        $manualinstance->customint1 = $sendcoursewelcome;
        unset($CFG->enrol_manual_sendcoursewelcomemessage);
    }
    return true;
}

function local_trainer_get_managed_enrolled_course_ids($userid) {
    global $DB;

    return array_map('intval', $DB->get_fieldset_sql(
        "SELECT DISTINCT e.courseid
           FROM {user_enrolments} ue
           JOIN {enrol} e ON e.id = ue.enrolid
           JOIN {context} ctx ON ctx.instanceid = e.courseid
                            AND ctx.contextlevel = :coursecontext
           JOIN {role_assignments} ra ON ra.userid = ue.userid
                                     AND ra.contextid = ctx.id
           JOIN {role} r ON r.id = ra.roleid
          WHERE ue.userid = :userid
            AND r.shortname IN ('trainer', 'teacher', 'editingteacher')",
        ['userid' => (int) $userid, 'coursecontext' => CONTEXT_COURSE]
    ));
}

function local_trainer_unenrol_from_course($courseid, $userid) {
    global $DB;

    $enrolments = $DB->get_records_sql(
        "SELECT ue.id, ue.enrolid, e.enrol
           FROM {user_enrolments} ue
           JOIN {enrol} e ON e.id = ue.enrolid
          WHERE ue.userid = :userid
            AND e.courseid = :courseid",
        ['userid' => (int) $userid, 'courseid' => (int) $courseid]
    );

    foreach ($enrolments as $enrolment) {
        $plugin = enrol_get_plugin($enrolment->enrol);
        $instance = $DB->get_record('enrol', ['id' => $enrolment->enrolid]);
        if ($plugin && $instance) {
            $plugin->unenrol_user($instance, (int) $userid);
        }
    }
}

function local_trainer_sync_course_enrolments($traineruserid, $schoolid) {
    $targetcourseids = local_trainer_get_school_course_ids($schoolid);
    $currentcourseids = local_trainer_get_managed_enrolled_course_ids($traineruserid);

    foreach (array_diff($currentcourseids, $targetcourseids) as $courseid) {
        local_trainer_unenrol_from_course($courseid, $traineruserid);
    }

    foreach ($targetcourseids as $courseid) {
        local_trainer_enrol_in_course($courseid, $traineruserid);
    }
}

function local_trainer_sync_school_assignment($traineruserid, $schoolid, $pocuserid = 0) {
    global $DB;

    $traineruserid = (int) $traineruserid;
    $schoolid = (int) $schoolid;
    $pocuserid = (int) $pocuserid;
    $trainer = $DB->get_record('trainer', ['userid' => $traineruserid], '*', MUST_EXIST);

    if ($DB->get_manager()->table_exists('local_mydashboard_chat')) {
        if (!empty($schoolid)) {
            $archivedchats = $DB->get_records_sql(
                "SELECT c.id
                   FROM {local_mydashboard_chat} c
                   JOIN {student} s ON s.userid = c.studentid
                  WHERE c.trainerid = :trainerid
                    AND s.schoolid = :schoolid
                    AND c.status = 'archived'",
                ['trainerid' => $traineruserid, 'schoolid' => $schoolid]
            );

            mtrace('Trainer: ' . $traineruserid);
            mtrace('School: ' . $schoolid);
            mtrace('Archived chats found: ' . count($archivedchats));

            foreach ($archivedchats as $chat) {
                $DB->set_field(
                    'local_mydashboard_chat',
                    'status',
                    'active',
                    ['id' => $chat->id]
                );
                mtrace('Reactivated chat ID: ' . $chat->id);
            }
        }

        $stalechats = [];
        if (!empty($schoolid)) {
            $stalechats = $DB->get_records_sql(
                "SELECT c.id, c.studentid, c.trainerid
                   FROM {local_mydashboard_chat} c
                   JOIN {student} s ON s.userid = c.studentid
                  WHERE c.status = 'active'
                    AND s.schoolid = :schoolid
                    AND c.trainerid <> :trainerid",
                ['schoolid' => $schoolid, 'trainerid' => $traineruserid]
            );
        }

        $leavingchats = $DB->get_records_sql(
            "SELECT c.id, c.studentid, c.trainerid
               FROM {local_mydashboard_chat} c
               JOIN {student} s ON s.userid = c.studentid
              WHERE c.status = 'active'
                AND c.trainerid = :trainerid
                AND (:schoolid = 0 OR s.schoolid <> :currentschoolid)",
            [
                'trainerid' => $traineruserid,
                'schoolid' => $schoolid,
                'currentschoolid' => $schoolid,
            ]
        );

        foreach (array_merge($stalechats, $leavingchats) as $oldchat) {
            $DB->set_field('local_mydashboard_chat', 'status', 'archived', [
                'studentid' => (int) $oldchat->studentid,
                'trainerid' => (int) $oldchat->trainerid,
            ]);
        }
    }

    $updatetrainer = new stdClass();
    $updatetrainer->id = $trainer->id;
    $updatetrainer->schoolid = !empty($schoolid) ? $schoolid : null;
    $DB->update_record('trainer', $updatetrainer);

    $DB->delete_records('schoolassign', ['userid' => $traineruserid]);
    if (!empty($schoolid)) {
        $columns = $DB->get_columns('schoolassign');
        $now = time();
        $role = !empty($pocuserid)
            ? $DB->get_record_sql("SELECT roleid FROM {role_assignments} WHERE userid = :userid", ['userid' => $pocuserid], IGNORE_MULTIPLE)
            : false;

        $schoolassign = new stdClass();
        $schoolassign->userid = $traineruserid;
        $schoolassign->schoolid = $schoolid;
        if (array_key_exists('schoolassignedto', $columns)) {
            $schoolassign->schoolassignedto = $traineruserid;
        }
        if (array_key_exists('schoolassignby', $columns)) {
            $schoolassign->schoolassignby = $pocuserid;
        }
        if (array_key_exists('assigneeroleid', $columns)) {
            $schoolassign->assigneeroleid = $role ? $role->roleid : 0;
        }
        if (array_key_exists('status', $columns)) {
            $schoolassign->status = 1;
        }
        if (array_key_exists('timecreated', $columns)) {
            $schoolassign->timecreated = $now;
        }
        if (array_key_exists('timemodified', $columns)) {
            $schoolassign->timemodified = $now;
        }
        if (array_key_exists('usertype', $columns)) {
            $schoolassign->usertype = 'trainer';
        }
        $DB->insert_record('schoolassign', $schoolassign);
    }

    if ($DB->get_manager()->table_exists('trainer_course_mapping')) {
        $DB->delete_records('trainer_course_mapping', ['traineruserid' => $traineruserid]);
        if (!empty($schoolid)) {
            $mappedcourses = $DB->get_records('poc_copy_course', [
                'schoolid' => $schoolid,
                'status' => 1,
            ]);
            $now = time();
            foreach ($mappedcourses as $mappedcourse) {
                $trainermapping = new stdClass();
                $trainermapping->trainerrecordid = $trainer->id;
                $trainermapping->traineruserid = $traineruserid;
                $trainermapping->pocid = $pocuserid;
                $trainermapping->schoolid = $schoolid;
                $trainermapping->gradeid = $mappedcourse->gradeid;
                $trainermapping->courseid = $mappedcourse->courseid;
                $trainermapping->poccourseid = $mappedcourse->id;
                $trainermapping->status = 1;
                $trainermapping->timecreated = $now;
                $trainermapping->timemodified = $now;
                $DB->insert_record('trainer_course_mapping', $trainermapping);
            }
        }
    }

    local_trainer_sync_course_enrolments($traineruserid, $schoolid);

    if (!empty($schoolid)) {
        $traineruser = $DB->get_record('user', ['id' => $traineruserid], 'id', IGNORE_MISSING);
        $school = $DB->get_record('school', ['course_cat_id' => $schoolid], 'id, school_name', IGNORE_MISSING);
        if ($traineruser && $school) {
            \local_emailtemplates\email_sender::send_email(
                'trainer_assigned',
                $traineruserid,
                'trainer_assigned',
                $school->school_name
            );
        }
    }
}

?>
