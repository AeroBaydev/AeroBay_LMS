<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for local_mydashboard.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_mydashboard_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026060400) {
        $table = new xmldb_table('local_mydashboard_streak');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('currentstreak', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('longeststreak', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('restoreused', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('lastlogindate', XMLDB_TYPE_INTEGER, '8', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('userid', XMLDB_INDEX_UNIQUE, ['userid']);

            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_mydashboard_streak_log');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('logindate', XMLDB_TYPE_INTEGER, '8', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('userid-logindate', XMLDB_INDEX_UNIQUE, ['userid', 'logindate']);
            $table->add_index('logindate', XMLDB_INDEX_NOTUNIQUE, ['logindate']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026060400, 'local', 'mydashboard');
    }

    if ($oldversion < 2026060500) {
        $table = new xmldb_table('local_session_progress');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('schoolid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('gradeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('trainerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
            $table->add_field('completeddays', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('school-grade-course-section', XMLDB_INDEX_UNIQUE, ['schoolid', 'gradeid', 'courseid', 'sectionid']);
            $table->add_index('sectionid', XMLDB_INDEX_NOTUNIQUE, ['sectionid']);
            $table->add_index('school-grade-course', XMLDB_INDEX_NOTUNIQUE, ['schoolid', 'gradeid', 'courseid']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026060500, 'local', 'mydashboard');
    }
    if ($oldversion < 2026060600) {
        $table = new xmldb_table('local_trainer_rating');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('trainerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('schoolid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('gradeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('rating', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('feedback', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('student-trainer', XMLDB_INDEX_UNIQUE, ['studentid', 'trainerid']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026060600, 'local', 'mydashboard');
    }

    if ($oldversion < 2026060800) {
        $table = new xmldb_table('local_mydashboard_doubt');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('trainerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('subject', XMLDB_TYPE_CHAR, '255');
            $table->add_field('question', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'open');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('studentid', XMLDB_INDEX_NOTUNIQUE, ['studentid']);
            $table->add_index('trainerid', XMLDB_INDEX_NOTUNIQUE, ['trainerid']);
            $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026060800, 'local', 'mydashboard');
    }

    if ($oldversion < 2026060801) {
        upgrade_plugin_savepoint(true, 2026060801, 'local', 'mydashboard');
    }

    if ($oldversion < 2026061100) {
        $table = new xmldb_table('local_mydashboard_doubt');

        // Add 'reply' text field (nullable) for trainer's response.
        $field = new xmldb_field('reply', XMLDB_TYPE_TEXT, null, null, null, null, null, 'status');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add 'replied_at' integer field (nullable) for reply timestamp.
        $field = new xmldb_field('replied_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'reply');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026061100, 'local', 'mydashboard');
    }

    if ($oldversion < 2026061101) {
        $table = new xmldb_table('local_mydashboard_chat');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('trainerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('schoolid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'active');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('student-trainer', XMLDB_INDEX_UNIQUE, ['studentid', 'trainerid']);
            $table->add_index('trainer-status', XMLDB_INDEX_NOTUNIQUE, ['trainerid', 'status']);
            $table->add_index('student-status', XMLDB_INDEX_NOTUNIQUE, ['studentid', 'status']);
            $table->add_index('school-status', XMLDB_INDEX_NOTUNIQUE, ['schoolid', 'status']);

            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_mydashboard_chat_messages');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('chatid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('senderid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('sendertype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'student');
            $table->add_field('message', XMLDB_TYPE_TEXT);
            $table->add_field('attachment', XMLDB_TYPE_CHAR, '255');
            $table->add_field('legacydoubtid', XMLDB_TYPE_INTEGER, '10');
            $table->add_field('legacytype', XMLDB_TYPE_CHAR, '20');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('chat-time', XMLDB_INDEX_NOTUNIQUE, ['chatid', 'timecreated']);
            $table->add_index('senderid', XMLDB_INDEX_NOTUNIQUE, ['senderid']);
            $table->add_index('legacy-doubt-type', XMLDB_INDEX_UNIQUE, ['legacydoubtid', 'legacytype']);

            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_mydashboard_chat_read');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('chatid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('lastreadmessageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('chat-user', XMLDB_INDEX_UNIQUE, ['chatid', 'userid']);
            $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);

            $dbman->create_table($table);
        }

        $doubttable = new xmldb_table('local_mydashboard_doubt');
        if ($dbman->table_exists($doubttable)) {
            $doubts = $DB->get_records('local_mydashboard_doubt', null, 'studentid, trainerid, timecreated, id');
            $studenttable = new xmldb_table('student');
            $trainertable = new xmldb_table('trainer');
            $canreadstudentschool = $dbman->table_exists($studenttable)
                && $dbman->field_exists($studenttable, new xmldb_field('userid'))
                && $dbman->field_exists($studenttable, new xmldb_field('schoolid'));
            $canreadtrainerschool = $dbman->table_exists($trainertable)
                && $dbman->field_exists($trainertable, new xmldb_field('userid'))
                && $dbman->field_exists($trainertable, new xmldb_field('schoolid'));
            $context = context_system::instance();
            $fs = get_file_storage();
            $chats = [];

            foreach ($doubts as $doubt) {
                $studentid = (int) $doubt->studentid;
                $trainerid = (int) $doubt->trainerid;
                if ($studentid <= 0 || $trainerid <= 0) {
                    continue;
                }

                $pairkey = $studentid . ':' . $trainerid;
                if (!isset($chats[$pairkey])) {
                    $chat = $DB->get_record('local_mydashboard_chat', [
                        'studentid' => $studentid,
                        'trainerid' => $trainerid,
                    ]);
                    if (!$chat) {
                        $schoolid = 0;
                        if ($canreadstudentschool) {
                            $schoolid = (int) $DB->get_field('student', 'schoolid', ['userid' => $studentid]);
                        }
                        if ($schoolid <= 0 && $canreadtrainerschool) {
                            $schoolid = (int) $DB->get_field('trainer', 'schoolid', ['userid' => $trainerid]);
                        }

                        $chat = (object) [
                            'studentid' => $studentid,
                            'trainerid' => $trainerid,
                            'schoolid' => $schoolid,
                            'status' => 'active',
                            'timecreated' => (int) $doubt->timecreated,
                            'timemodified' => max(
                                (int) $doubt->timecreated,
                                (int) $doubt->timemodified,
                                (int) ($doubt->replied_at ?? 0)
                            ),
                        ];
                        $chat->id = $DB->insert_record('local_mydashboard_chat', $chat);
                    }
                    $chats[$pairkey] = $chat;
                }

                $chat = $chats[$pairkey];
                $doubttimecreated = (int) $doubt->timecreated;
                $doubttimemodified = max(
                    $doubttimecreated,
                    (int) $doubt->timemodified,
                    (int) ($doubt->replied_at ?? 0)
                );
                if ($doubttimecreated < (int) $chat->timecreated || $doubttimemodified > (int) $chat->timemodified) {
                    $chat->timecreated = min((int) $chat->timecreated, $doubttimecreated);
                    $chat->timemodified = max((int) $chat->timemodified, $doubttimemodified);
                    $DB->update_record('local_mydashboard_chat', $chat);
                }

                if (!$DB->record_exists('local_mydashboard_chat_messages', [
                    'legacydoubtid' => (int) $doubt->id,
                    'legacytype' => 'question',
                ])) {
                    $messagerecord = (object) [
                        'chatid' => (int) $chat->id,
                        'senderid' => $studentid,
                        'sendertype' => 'student',
                        'message' => (string) $doubt->question,
                        'legacydoubtid' => (int) $doubt->id,
                        'legacytype' => 'question',
                        'timecreated' => $doubttimecreated,
                    ];
                    $messageid = $DB->insert_record('local_mydashboard_chat_messages', $messagerecord);
                    $oldfiles = $fs->get_area_files(
                        $context->id,
                        'local_mydashboard',
                        'doubt_attachment',
                        (int) $doubt->id,
                        'id',
                        false
                    );
                    foreach ($oldfiles as $oldfile) {
                        $filerecord = [
                            'contextid' => $context->id,
                            'component' => 'local_mydashboard',
                            'filearea' => 'chat_message_attachment',
                            'itemid' => $messageid,
                        ];
                        $fs->create_file_from_storedfile($filerecord, $oldfile);
                        if (empty($messagerecord->attachment)) {
                            $messagerecord->attachment = $oldfile->get_filename();
                        }
                    }
                    if (!empty($messagerecord->attachment)) {
                        $messagerecord->id = $messageid;
                        $DB->update_record('local_mydashboard_chat_messages', $messagerecord);
                    }
                }

                $reply = trim((string) ($doubt->reply ?? ''));
                if ($reply !== '' && !$DB->record_exists('local_mydashboard_chat_messages', [
                    'legacydoubtid' => (int) $doubt->id,
                    'legacytype' => 'reply',
                ])) {
                    $replytime = (int) ($doubt->replied_at ?? 0);
                    if ($replytime <= 0) {
                        $replytime = (int) $doubt->timemodified ?: $doubttimecreated;
                    }
                    $DB->insert_record('local_mydashboard_chat_messages', (object) [
                        'chatid' => (int) $chat->id,
                        'senderid' => $trainerid,
                        'sendertype' => 'trainer',
                        'message' => $reply,
                        'legacydoubtid' => (int) $doubt->id,
                        'legacytype' => 'reply',
                        'timecreated' => $replytime,
                    ]);
                }
            }

            foreach ($chats as $chat) {
                $lastmessageid = (int) $DB->get_field_sql(
                    'SELECT MAX(id) FROM {local_mydashboard_chat_messages} WHERE chatid = :chatid',
                    ['chatid' => (int) $chat->id]
                );
                foreach ([(int) $chat->studentid, (int) $chat->trainerid] as $userid) {
                    if (!$DB->record_exists('local_mydashboard_chat_read', [
                        'chatid' => (int) $chat->id,
                        'userid' => $userid,
                    ])) {
                        $DB->insert_record('local_mydashboard_chat_read', (object) [
                            'chatid' => (int) $chat->id,
                            'userid' => $userid,
                            'lastreadmessageid' => $lastmessageid,
                            'timemodified' => time(),
                        ]);
                    }
                }
            }
        }

        upgrade_plugin_savepoint(true, 2026061101, 'local', 'mydashboard');
    }

    return true;
}
