<?php

define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once($CFG->dirroot . '/local/mydashboard/lib.php');

require_login();
require_sesskey();

global $DB, $USER;

$search = trim(optional_param('search', '', PARAM_TEXT));

$response = function(array $payload): void {
    header('Content-Type: application/json');
    echo json_encode($payload);
    die();
};

if (!$DB->record_exists('trainer', ['userid' => $USER->id])) {
    $response(['success' => false, 'error' => 'Only trainers can access this chat list.']);
}

$params = ['trainerid' => (int) $USER->id];
$searchsql = '';
if ($search !== '') {
    $searchsql = ' AND (' . $DB->sql_like('u.firstname', ':firstname', false)
        . ' OR ' . $DB->sql_like('u.lastname', ':lastname', false) . ')';
    $like = '%' . $DB->sql_like_escape($search) . '%';
    $params['firstname'] = $like;
    $params['lastname'] = $like;
}

$chats = $DB->get_records_sql(
    "SELECT c.id, c.studentid, c.timemodified, u.firstname, u.lastname
       FROM {local_mydashboard_chat} c
       JOIN {user} u ON u.id = c.studentid
       JOIN {student} s ON s.userid = c.studentid
       JOIN {trainer} t ON t.userid = c.trainerid AND t.schoolid = s.schoolid
      WHERE c.trainerid = :trainerid
        AND c.status = 'active'
            {$searchsql}
   ORDER BY c.timemodified DESC, c.id DESC",
    $params
);

$items = [];
$totalunread = 0;
foreach ($chats as $chat) {
    $lastmessage = $DB->get_record_sql(
        "SELECT id, senderid, message, attachment, timecreated
           FROM {local_mydashboard_chat_messages}
          WHERE chatid = :chatid
       ORDER BY timecreated DESC, id DESC",
        ['chatid' => (int) $chat->id],
        IGNORE_MULTIPLE
    );
    $unreadcount = local_mydashboard_get_unread_count((int) $chat->id, (int) $USER->id);
    $totalunread += $unreadcount;

    $preview = 'No messages yet';
    $lastactivity = userdate((int) $chat->timemodified, '%d %b');
    if ($lastmessage) {
        $preview = trim((string) $lastmessage->message);
        if ($preview === '') {
            $preview = !empty($lastmessage->attachment) ? 'Image attachment' : 'Message';
        }
        if ((int) $lastmessage->senderid === (int) $USER->id) {
            $preview = 'You: ' . $preview;
        }
        $lastactivity = userdate((int) $lastmessage->timecreated, '%d %b, %I:%M %p');
    }

    $studentname = fullname($chat);
    $initials = core_text::strtoupper(
        core_text::substr(trim((string) $chat->firstname), 0, 1)
        . core_text::substr(trim((string) $chat->lastname), 0, 1)
    );
    $items[] = [
        'id' => (int) $chat->id,
        'studentid' => (int) $chat->studentid,
        'studentname' => $studentname,
        'initials' => $initials ?: 'ST',
        'preview' => shorten_text($preview, 70),
        'lastactivity' => $lastactivity,
        'unreadcount' => $unreadcount,
    ];
}

$response([
    'success' => true,
    'chats' => $items,
    'totalunread' => $totalunread,
]);
