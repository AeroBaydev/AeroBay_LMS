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

function local_mydashboard_format_recent_activity(
    string $type,
    string $icon,
    string $dotclass,
    string $title,
    string $description,
    int $timecreated,
    string $url = ''
): array {
    return [
        'type' => $type,
        'icon' => $icon,
        'dotclass' => $dotclass,
        'title' => $title,
        'hastitle' => $title !== '',
        'description' => $description,
        'timecreated' => $timecreated,
        'displaytime' => userdate($timecreated, get_string('strftimedatetimeshort', 'langconfig')),
        'url' => $url,
        'hasurl' => $url !== '',
    ];
}

function local_mydashboard_get_log_events(int $userid, array $eventnames, int $limit = 20): array {
    global $DB;

    if ($userid <= 0 || empty($eventnames)) {
        return [];
    }

    $readers = get_log_manager()->get_readers('\core\log\sql_reader');
    if (empty($readers)) {
        return [];
    }

    $reader = reset($readers);
    [$eventsql, $eventparams] = $DB->get_in_or_equal($eventnames, SQL_PARAMS_NAMED, 'recentactivityevent');
    $params = [
        'recentactivityuserid' => $userid,
        'recentactivityrelateduserid' => $userid,
    ] + $eventparams;

    return array_values($reader->get_events_select(
        "(userid = :recentactivityuserid OR relateduserid = :recentactivityrelateduserid)
         AND eventname {$eventsql}",
        $params,
        'timecreated DESC',
        0,
        $limit
    ));
}

function local_mydashboard_collect_login_events(int $userid): array {
    $activities = [];
    $events = local_mydashboard_get_log_events($userid, ['\core\event\user_loggedin'], 10);

    foreach ($events as $event) {
        $url = $event->get_url();
        $activities[] = local_mydashboard_format_recent_activity(
            'login',
            'fa-solid fa-right-to-bracket',
            'ad-blue',
            '',
            'Logged in',
            (int) $event->timecreated,
            $url ? $url->out(false) : ''
        );
    }

    return $activities;
}

function local_mydashboard_collect_quiz_events(int $userid): array {
    global $DB;

    $activities = [];
    $events = local_mydashboard_get_log_events($userid, [
        '\mod_quiz\event\attempt_started',
        '\mod_quiz\event\attempt_submitted',
    ], 40);
    $quizcache = [];

    foreach ($events as $event) {
        $quizid = (int) ($event->other['quizid'] ?? 0);
        if ($quizid <= 0 && !empty($event->objectid)) {
            $quizid = (int) $DB->get_field('quiz_attempts', 'quiz', ['id' => $event->objectid]);
        }
        if ($quizid <= 0) {
            continue;
        }
        if (!array_key_exists($quizid, $quizcache)) {
            $quizcache[$quizid] = $DB->get_record('quiz', ['id' => $quizid], 'id, name');
        }
        if (!$quizcache[$quizid]) {
            continue;
        }

        $iscompleted = $event instanceof \mod_quiz\event\attempt_submitted;
        $url = $event->get_url();
        $activities[] = local_mydashboard_format_recent_activity(
            $iscompleted ? 'quiz_completed' : 'quiz_attempted',
            $iscompleted ? 'fa-solid fa-check' : 'fa-solid fa-file-pen',
            $iscompleted ? 'ad-green' : 'ad-amber',
            format_string($quizcache[$quizid]->name),
            $iscompleted ? 'Completed quiz' : 'Attempted quiz',
            (int) $event->timecreated,
            $url ? $url->out(false) : ''
        );
    }

    if (!$DB->get_manager()->table_exists('quiz_grades')) {
        return $activities;
    }

    $graderecords = $DB->get_records_sql(
        "SELECT qg.id, qg.quiz, qg.timemodified AS timecreated, q.name, q.grade AS maxgrade, qg.grade, cm.id AS cmid
           FROM {quiz_grades} qg
           JOIN {quiz} q ON q.id = qg.quiz
           JOIN {modules} m ON m.name = :modulename
           JOIN {course_modules} cm ON cm.module = m.id
                                   AND cm.instance = q.id
          WHERE qg.userid = :userid
            AND EXISTS (
                SELECT 1
                  FROM {quiz_attempts} qa
                 WHERE qa.quiz = qg.quiz
                   AND qa.userid = qg.userid
                   AND qa.state = :state
                   AND qa.timefinish > 0
            )
       ORDER BY qg.timemodified DESC",
        [
            'modulename' => 'quiz',
            'userid' => $userid,
            'state' => 'finished',
        ],
        0,
        40
    );
    foreach ($graderecords as $record) {
        $percentage = (float) $record->maxgrade > 0
            ? ((float) $record->grade / (float) $record->maxgrade) * 100
            : 0;
        $activities[] = local_mydashboard_format_recent_activity(
            'quiz_score',
            'fa-solid fa-chart-simple',
            'ad-purple',
            format_string($record->name) . ' - ' . format_float($percentage, 1) . '%',
            'Quiz score received',
            (int) $record->timecreated,
            (new moodle_url('/mod/quiz/view.php', ['id' => $record->cmid]))->out(false)
        );
    }

    return $activities;
}

function local_mydashboard_collect_assignment_events(int $userid): array {
    $activities = [];
    $events = local_mydashboard_get_log_events($userid, ['\mod_assign\event\assessable_submitted'], 20);
    $assignmentcache = [];

    foreach ($events as $event) {
        $cmid = (int) $event->contextinstanceid;
        if ($cmid <= 0) {
            continue;
        }
        if (!array_key_exists($cmid, $assignmentcache)) {
            $assignmentcache[$cmid] = get_coursemodule_from_id('assign', $cmid, 0, false, IGNORE_MISSING);
        }
        if (!$assignmentcache[$cmid]) {
            continue;
        }

        $activities[] = local_mydashboard_format_recent_activity(
            'assignment_submitted',
            'fa-solid fa-file-arrow-up',
            'ad-rose',
            format_string($assignmentcache[$cmid]->name),
            'Submitted assignment',
            (int) $event->timecreated,
            (new moodle_url('/mod/assign/view.php', ['id' => $cmid]))->out(false)
        );
    }

    return $activities;
}

function local_mydashboard_collect_streak_events(int $userid): array {
    global $DB;

    if ($userid <= 0 || !$DB->get_manager()->table_exists('local_mydashboard_streak_log')) {
        return [];
    }

    $activities = [];
    $records = $DB->get_records(
        'local_mydashboard_streak_log',
        ['userid' => $userid],
        'timecreated DESC',
        'id, timecreated',
        0,
        15
    );
    foreach ($records as $record) {
        $activities[] = local_mydashboard_format_recent_activity(
            'login_streak',
            'fa-solid fa-fire',
            'ad-amber',
            '',
            'Login streak updated',
            (int) $record->timecreated,
            (new moodle_url('/mydashboard/index.php'))->out(false)
        );
    }

    return $activities;
}

function local_mydashboard_get_recent_activities(int $userid): array {
    if ($userid <= 0) {
        return [];
    }

    $activities = array_merge(
        local_mydashboard_collect_quiz_events($userid),
        local_mydashboard_collect_assignment_events($userid)
    );
    usort($activities, static function(array $left, array $right): int {
        return $right['timecreated'] <=> $left['timecreated'];
    });

    return array_slice($activities, 0, 5);
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
    global $CFG, $DB, $USER;

    $schoolid = (int) ($student->schoolid ?? 0);
    $gradeid = (int) ($student->gradeid ?? 0);
    $studentuserid = (int) ($student->userid ?? $USER->id);
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
    $activesectionid = 0;
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
                $activesession = $section['sectionname'];
                $activesectionid = $section['sectionid'];
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

    $continuelearningurl = '';
    if ($courseid > 0 && $activesectionid > 0) {
        require_once($CFG->dirroot . '/course/lib.php');

        $modinfo = get_fast_modinfo($courseid, $studentuserid);
        $sectioninfo = $modinfo->get_section_info_by_id($activesectionid);
        if ($sectioninfo && $sectioninfo->visible && $sectioninfo->uservisible) {
            $sectionurl = course_get_url($courseid, $sectioninfo->section, ['navigation' => true]);
            if ($sectionurl) {
                $continuelearningurl = $sectionurl->out(false);
            }
        }
    }

    return [
        'continuelearningurl' => $continuelearningurl,
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

/**
 * Return a chat after confirming that the user is one of its participants.
 *
 * @param int $chatid
 * @param int $userid
 * @return stdClass
 */
function local_mydashboard_require_chat_participant(int $chatid, int $userid): stdClass {
    global $DB;

    if ($chatid <= 0 || $userid <= 0) {
        throw new invalid_parameter_exception('A valid chat and user are required.');
    }

    $chat = $DB->get_record('local_mydashboard_chat', ['id' => $chatid], '*', MUST_EXIST);
    if ($userid !== (int) $chat->studentid && $userid !== (int) $chat->trainerid) {
        throw new required_capability_exception(context_system::instance(), 'moodle/site:config', 'nopermissions', '');
    }

    return $chat;
}

/**
 * Return a chat owned by the logged-in participant and derive their sender type.
 *
 * @param int $chatid
 * @param int $userid
 * @return array
 */
function local_mydashboard_require_owned_chat(int $chatid, int $userid): array {
    $chat = local_mydashboard_require_chat_participant($chatid, $userid);

    if ($chat->status === 'archived') {
        throw new moodle_exception('chatarchived', 'local_mydashboard');
    }

    if ((int) $chat->studentid === $userid) {
        return [$chat, 'student'];
    }
    if ((int) $chat->trainerid === $userid) {
        return [$chat, 'trainer'];
    }

    throw new required_capability_exception(context_system::instance(), 'moodle/site:config', 'nopermissions', '');
}

/**
 * Return the trainer currently assigned to a student through their school.
 *
 * @param int $studentid
 * @return stdClass|null
 */
function local_mydashboard_get_student_assigned_trainer(int $studentid): ?stdClass {
    global $DB;

    if ($studentid <= 0) {
        return null;
    }

    $student = $DB->get_record('student', ['userid' => $studentid], 'userid, schoolid', IGNORE_MISSING);
    if (!$student || empty($student->schoolid)) {
        return null;
    }

    $trainers = $DB->get_records_sql(
        "SELECT t.id, t.userid, t.schoolid, u.firstname, u.lastname, u.lastaccess
           FROM {trainer} t
           JOIN {user} u ON u.id = t.userid
          WHERE t.schoolid = :schoolid
       ORDER BY t.id ASC",
        ['schoolid' => (int) $student->schoolid],
        0,
        1
    );

    return $trainers ? reset($trainers) : null;
}

/**
 * Confirm that a chat belongs to the student's currently assigned trainer.
 *
 * @param int $chatid
 * @param int $studentid
 * @return stdClass
 */
function local_mydashboard_require_student_assigned_chat(int $chatid, int $studentid): stdClass {
    $chat = local_mydashboard_require_chat_participant($chatid, $studentid);
    if ($chat->status === 'archived') {
        throw new moodle_exception('chatarchived', 'local_mydashboard');
    }

    return $chat;
}

/**
 * Get the persistent chat for a student and trainer, creating it when needed.
 *
 * @param int $studentid
 * @param int $trainerid
 * @param int $schoolid
 * @return stdClass
 */
function local_mydashboard_get_or_create_chat(int $studentid, int $trainerid, int $schoolid = 0): stdClass {
    global $DB;

    if ($studentid <= 0 || $trainerid <= 0) {
        throw new invalid_parameter_exception('A valid student and trainer are required.');
    }

    $params = ['studentid' => $studentid, 'trainerid' => $trainerid];
    $chat = $DB->get_record('local_mydashboard_chat', $params);
    if ($chat) {
        return $chat;
    }

    $dbman = $DB->get_manager();
    $studenttable = new xmldb_table('student');
    $trainertable = new xmldb_table('trainer');
    if ($schoolid <= 0 && $dbman->table_exists($studenttable)
            && $dbman->field_exists($studenttable, new xmldb_field('userid'))
            && $dbman->field_exists($studenttable, new xmldb_field('schoolid'))) {
        $schoolid = (int) $DB->get_field('student', 'schoolid', ['userid' => $studentid]);
    }
    if ($schoolid <= 0 && $dbman->table_exists($trainertable)
            && $dbman->field_exists($trainertable, new xmldb_field('userid'))
            && $dbman->field_exists($trainertable, new xmldb_field('schoolid'))) {
        $schoolid = (int) $DB->get_field('trainer', 'schoolid', ['userid' => $trainerid]);
    }

    $now = time();
    $record = (object) [
        'studentid' => $studentid,
        'trainerid' => $trainerid,
        'schoolid' => max(0, $schoolid),
        'status' => 'active',
        'timecreated' => $now,
        'timemodified' => $now,
    ];

    try {
        $record->id = $DB->insert_record('local_mydashboard_chat', $record);
        return $record;
    } catch (dml_write_exception $exception) {
        // Another request may have created the unique student/trainer pair first.
        return $DB->get_record('local_mydashboard_chat', $params, '*', MUST_EXIST);
    }
}

/**
 * Get chat messages in chronological order.
 *
 * @param int $chatid
 * @param int $userid
 * @param int $limit
 * @param int $beforemessageid
 * @return array
 */
function local_mydashboard_get_chat_messages(
    int $chatid,
    int $userid,
    int $limit = 100,
    int $beforemessageid = 0
): array {
    global $DB;

    local_mydashboard_require_chat_participant($chatid, $userid);
    $limit = max(1, min(500, $limit));
    $params = ['chatid' => $chatid];
    $beforesql = '';
    if ($beforemessageid > 0) {
        $beforesql = ' AND id < :beforemessageid';
        $params['beforemessageid'] = $beforemessageid;
    }

    $messages = $DB->get_records_sql(
        "SELECT *
           FROM {local_mydashboard_chat_messages}
          WHERE chatid = :chatid{$beforesql}
       ORDER BY timecreated DESC, id DESC",
        $params,
        0,
        $limit
    );

    return array_reverse(array_values($messages));
}

/**
 * Move a user's read position forward in a chat.
 *
 * @param int $chatid
 * @param int $userid
 * @param int $lastreadmessageid Zero marks through the latest message.
 * @return void
 */
function local_mydashboard_mark_chat_read(int $chatid, int $userid, int $lastreadmessageid = 0): void {
    global $DB;

    local_mydashboard_require_chat_participant($chatid, $userid);
    if ($lastreadmessageid > 0 && !$DB->record_exists('local_mydashboard_chat_messages', [
        'id' => $lastreadmessageid,
        'chatid' => $chatid,
    ])) {
        throw new invalid_parameter_exception('The read message does not belong to this chat.');
    }
    if ($lastreadmessageid <= 0) {
        $lastreadmessageid = (int) $DB->get_field_sql(
            'SELECT MAX(id) FROM {local_mydashboard_chat_messages} WHERE chatid = :chatid',
            ['chatid' => $chatid]
        );
    }

    $readrecord = $DB->get_record('local_mydashboard_chat_read', ['chatid' => $chatid, 'userid' => $userid]);
    if ($readrecord) {
        $readrecord->lastreadmessageid = max((int) $readrecord->lastreadmessageid, $lastreadmessageid);
        $readrecord->timemodified = time();
        $DB->update_record('local_mydashboard_chat_read', $readrecord);
        return;
    }

    $DB->insert_record('local_mydashboard_chat_read', (object) [
        'chatid' => $chatid,
        'userid' => $userid,
        'lastreadmessageid' => $lastreadmessageid,
        'timemodified' => time(),
    ]);
}

/**
 * Count unread messages sent by the other participant.
 *
 * @param int $chatid
 * @param int $userid
 * @return int
 */
function local_mydashboard_get_unread_count(int $chatid, int $userid): int {
    global $DB;

    local_mydashboard_require_chat_participant($chatid, $userid);
    $lastreadmessageid = (int) $DB->get_field('local_mydashboard_chat_read', 'lastreadmessageid', [
        'chatid' => $chatid,
        'userid' => $userid,
    ]);

    return (int) $DB->count_records_select(
        'local_mydashboard_chat_messages',
        'chatid = :chatid AND senderid <> :userid AND id > :lastreadmessageid',
        [
            'chatid' => $chatid,
            'userid' => $userid,
            'lastreadmessageid' => $lastreadmessageid,
        ]
    );
}

function local_mydashboard_get_student_progress_context(stdClass $student): array {
    global $DB, $USER;

    $schoolid = (int) ($student->schoolid ?? 0);
    $gradeid = (int) ($student->gradeid ?? 0);
    $studentuserid = (int) ($student->userid ?? $USER->id);

    $attendancepresent = 0;
    $attendancetotal = 0;
    $calendar_days = [];
    $selected_month_name = date('F Y');
    $att_month = optional_param('att_month', (int) date('n'), PARAM_INT);
    $att_year = optional_param('att_year', (int) date('Y'), PARAM_INT);
    $current_month = (int) date('n');
    $current_year = (int) date('Y');

    if ($att_month < 1 || $att_month > 12) {
        $att_month = $current_month;
    }
    if ($att_year < 2000 || $att_year > 2100) {
        $att_year = $current_year;
    }

    if ($att_year > $current_year || ($att_year == $current_year && $att_month > $current_month)) {
        $att_month = $current_month;
        $att_year = $current_year;
    }

    $prev_month = $att_month - 1;
    $prev_year = $att_year;
    if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
    
    $attendance_has_next = !($att_year == $current_year && $att_month == $current_month);
    $next_month = $att_month + 1;
    $next_year = $att_year;
    if ($next_month > 12) { $next_month = 1; $next_year++; }

    $month_options = [];
    for ($i = -6; $i <= 1; $i++) {
        $m_ts = mktime(0, 0, 0, $current_month + $i, 1, $current_year);
        $m_num = (int) date('n', $m_ts);
        $y_num = (int) date('Y', $m_ts);
        
        if ($y_num > $current_year || ($y_num == $current_year && $m_num > $current_month)) {
            continue;
        }
        
        $month_options[] = [
            'name' => date('F Y', $m_ts),
            'value' => $m_num . '_' . $y_num,
            'selected' => ($m_num == $att_month && $y_num == $att_year)
        ];
    }

    if (!empty($schoolid) && !empty($gradeid) &&
            $DB->get_manager()->table_exists('attendance') &&
            $DB->get_manager()->table_exists('attendance_student')) {
        
        $monthstart = mktime(0, 0, 0, $att_month, 1, $att_year);
        $nextmonthstart = mktime(0, 0, 0, $att_month + 1, 1, $att_year);
        $selected_month_name = date('F Y', $monthstart);

        $records = $DB->get_records_sql(
            "SELECT att.id, att.date, ast.status
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
        
        $daystatus = [];
        $last_attendance_status = 'N/A';
        $last_attendance_date = 0;
        foreach ($records as $rec) {
            $day = (int) date('j', $rec->date);
            $daystatus[$day] = strtoupper($rec->status);
            $attendancetotal++;
            if ($daystatus[$day] === 'P') {
                $attendancepresent++;
            }
            if ($rec->date > $last_attendance_date) {
                $last_attendance_date = $rec->date;
                $last_attendance_status = ($daystatus[$day] === 'P') ? 'Present' : (($daystatus[$day] === 'A') ? 'Absent' : 'N/A');
            }
        }

        $daysinmonth = (int) date('t', $monthstart);
        $firstdayofweek = (int) date('w', $monthstart);
        for ($i = 0; $i < $firstdayofweek; $i++) {
            $calendar_days[] = ['day' => '', 'empty' => true];
        }
        for ($d = 1; $d <= $daysinmonth; $d++) {
            $status = isset($daystatus[$d]) ? $daystatus[$d] : 'NONE';
            $calendar_days[] = [
                'day' => $d,
                'empty' => false,
                'ispresent' => $status === 'P',
                'isabsent' => $status === 'A',
                'isnone' => $status === 'NONE'
            ];
        }
    }
    
    if (empty($calendar_days)) {
        $fallback_monthstart = mktime(0, 0, 0, $att_month, 1, $att_year);
        $daysinmonth = (int) date('t', $fallback_monthstart);
        $firstdayofweek = (int) date('w', $fallback_monthstart);
        for ($i = 0; $i < $firstdayofweek; $i++) {
            $calendar_days[] = ['day' => '', 'empty' => true];
        }
        for ($d = 1; $d <= $daysinmonth; $d++) {
            $calendar_days[] = [
                'day' => $d,
                'empty' => false,
                'ispresent' => false,
                'isabsent' => false,
                'isnone' => true
            ];
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
    $recentactivities = local_mydashboard_get_recent_activities($studentuserid);
    return array_merge([
        'overallprogressnumber' => (string) round($overallprogress),
        'overallprogress' => local_mydashboard_format_kpi_percent($overallprogress),
        'attendancepercentnumber' => (string) round($attendancepercent),
        'attendancepercent' => local_mydashboard_format_kpi_percent($attendancepercent),
        'attendancepresentcount' => $attendancepresent,
        'attendanceabsentcount' => $attendancetotal - $attendancepresent,
        'attendancetotalcount' => $attendancetotal,
        'attendance_calendar_days' => $calendar_days,
        'attendance_month_name' => $selected_month_name,
        'attendance_prev_month' => $prev_month,
        'attendance_prev_year' => $prev_year,
        'attendance_next_month' => $next_month,
        'attendance_next_year' => $next_year,
        'attendance_has_next' => $attendance_has_next,
        'attendance_month_options' => $month_options,
        'attendance_status_label' => $attendancepercent >= 75 ? 'Good' : ($attendancepercent >= 50 ? 'Average' : 'Low Attendance'),
        'attendance_last_status' => $last_attendance_status,
        'assessmentpercentnumber' => (string) round($assessmentpercent),
        'assessmentpercent' => local_mydashboard_format_kpi_percent($assessmentpercent),
        'assessmentattemptedcount' => $assessmentcompleted,
        'assessmentcompletedcount' => $assessmentcompleted,
        'assessmenttotalcount' => $assessmenttotal,
        'currentstreak' => $currentstreak,
        'longeststreak' => $longeststreak,
        'lastlogindate' => $lastlogindate,
        'recentactivities' => $recentactivities,
        'hasrecentactivities' => !empty($recentactivities),
    ], $weeklyloginflags, $timetablecontext, $learningpathcontext);
}

/**
 * Serve files for the local_mydashboard plugin.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool false if file not found
 */
function local_mydashboard_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB, $USER;

    require_login();

    if ($filearea !== 'doubt_attachment' && $filearea !== 'chat_message_attachment') {
        return false;
    }

    $itemid = (int) array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $userid = (int) $USER->id;
    if ($filearea === 'doubt_attachment') {
        $doubt = $DB->get_record('local_mydashboard_doubt', ['id' => $itemid], '*', IGNORE_MISSING);
        if (!$doubt || ($userid !== (int) $doubt->studentid
                && $userid !== (int) $doubt->trainerid
                && !is_siteadmin())) {
            return false;
        }
    } else {
        $message = $DB->get_record('local_mydashboard_chat_messages', ['id' => $itemid], 'id, chatid', IGNORE_MISSING);
        if (!$message) {
            return false;
        }
        $chat = $DB->get_record('local_mydashboard_chat', ['id' => $message->chatid], '*', IGNORE_MISSING);
        if (!$chat || ($userid !== (int) $chat->studentid
                && $userid !== (int) $chat->trainerid)) {
            return false;
        }
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_mydashboard', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
