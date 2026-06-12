<?php

define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once($CFG->dirroot . '/local/mydashboard/lib.php');

require_login();
require_sesskey();

global $CFG, $USER;

$chatid = required_param('chatid', PARAM_INT);

$response = function(array $payload): void {
    header('Content-Type: application/json');
    echo json_encode($payload);
    die();
};

try {
    local_mydashboard_require_owned_chat($chatid, (int) $USER->id);
    $messages = local_mydashboard_get_chat_messages($chatid, (int) $USER->id, 200);
    local_mydashboard_mark_chat_read($chatid, (int) $USER->id);
} catch (Throwable $exception) {
    $response(['success' => false, 'error' => 'You do not have access to this chat.']);
}

$fs = get_file_storage();
$contextid = context_system::instance()->id;
$items = [];
foreach ($messages as $message) {
    $attachmenturl = '';
    $attachmentname = '';
    if (!empty($message->attachment)) {
        $files = $fs->get_area_files(
            $contextid,
            'local_mydashboard',
            'chat_message_attachment',
            (int) $message->id,
            'id',
            false
        );
        foreach ($files as $file) {
            if ($file->get_filename() !== $message->attachment) {
                continue;
            }
            $attachmenturl = moodle_url::make_pluginfile_url(
                $contextid,
                'local_mydashboard',
                'chat_message_attachment',
                (int) $message->id,
                $file->get_filepath(),
                $file->get_filename()
            )->out(false);
            $attachmentname = $file->get_filename();
            break;
        }
    }
    $items[] = [
        'id' => (int) $message->id,
        'message' => format_text((string) $message->message, FORMAT_MOODLE),
        'sendertype' => (string) $message->sendertype,
        'ismine' => (int) $message->senderid === (int) $USER->id,
        'timestamp' => userdate((int) $message->timecreated, '%d %b %Y, %I:%M %p'),
        'attachmenturl' => $attachmenturl,
        'attachmentname' => $attachmentname,
    ];
}

$response(['success' => true, 'messages' => $items]);
