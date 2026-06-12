<?php
define('CLI_SCRIPT', true);
require_once('config.php');
require_once('local/mydashboard/lib.php');

$chat = $DB->get_record('local_mydashboard_chat', [], '*', IGNORE_MULTIPLE);
$messages = local_mydashboard_get_chat_messages($chat->id, $chat->studentid, 200);

$fs = get_file_storage();
$contextid = context_system::instance()->id;
$items = [];
foreach ($messages as $message) {
    $files = $fs->get_area_files(
        $contextid,
        'local_mydashboard',
        'chat_message_attachment',
        (int) $message->id,
        'id',
        false
    );
    $attachmenturl = '';
    $attachmentname = '';
    if ($files) {
        $file = reset($files);
        $attachmenturl = moodle_url::make_pluginfile_url(
            $contextid,
            'local_mydashboard',
            'chat_message_attachment',
            (int) $message->id,
            $file->get_filepath(),
            $file->get_filename()
        )->out(false);
        $attachmentname = $file->get_filename();
    }
    $items[] = [
        'id' => (int) $message->id,
        'message' => (string) $message->message,
        'sendertype' => (string) $message->sendertype,
        'timestamp' => userdate((int) $message->timecreated, '%d %b %Y, %I:%M %p'),
        'attachmenturl' => $attachmenturl,
        'attachmentname' => $attachmentname,
    ];
}

echo json_encode(['success' => true, 'messages' => $items], JSON_PRETTY_PRINT);
