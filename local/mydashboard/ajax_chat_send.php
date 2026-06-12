<?php

define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once($CFG->dirroot . '/local/mydashboard/lib.php');

require_login();
require_sesskey();

global $DB, $USER;

const LOCAL_MYDASHBOARD_CHAT_MAX_MESSAGE_LENGTH = 4000;
const LOCAL_MYDASHBOARD_CHAT_ATTACHMENT_MAX_BYTES = 5 * 1024 * 1024;

$chatid = required_param('chatid', PARAM_INT);
$message = trim(optional_param('message', '', PARAM_TEXT));
$attachment = $_FILES['attachment'] ?? null;

$response = function(array $payload): void {
    header('Content-Type: application/json');
    echo json_encode($payload);
    die();
};

try {
    [$chat, $sendertype] = local_mydashboard_require_owned_chat($chatid, (int) $USER->id);
} catch (Throwable $exception) {
    $response(['success' => false, 'error' => 'You do not have access to this chat.']);
}

if (core_text::strlen($message) > LOCAL_MYDASHBOARD_CHAT_MAX_MESSAGE_LENGTH) {
    $response(['success' => false, 'error' => 'Message cannot exceed 4000 characters.']);
}

$validatedattachment = null;
if ($attachment && (int) ($attachment['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if (!isset($attachment['error'], $attachment['name'], $attachment['tmp_name'])
            || is_array($attachment['error']) || is_array($attachment['name']) || is_array($attachment['tmp_name'])
            || (int) $attachment['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($attachment['tmp_name'])) {
        $response(['success' => false, 'error' => 'The image attachment upload is invalid.']);
    }
    $actualsize = filesize($attachment['tmp_name']);
    if ($actualsize === false || $actualsize > LOCAL_MYDASHBOARD_CHAT_ATTACHMENT_MAX_BYTES) {
        $response(['success' => false, 'error' => 'Attachment cannot exceed 5 MB.']);
    }
    $extension = core_text::strtolower(pathinfo($attachment['name'], PATHINFO_EXTENSION));
    $imageinfo = @getimagesize($attachment['tmp_name']);
    $allowedmimetypes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
    if (!isset($allowedmimetypes[$extension]) || !$imageinfo || ($imageinfo['mime'] ?? '') !== $allowedmimetypes[$extension]) {
        $response(['success' => false, 'error' => 'Attachment must be a valid JPG, JPEG, or PNG image.']);
    }
    $filename = clean_param($attachment['name'], PARAM_FILE) ?: 'chat-image.' . $extension;
    $validatedattachment = [
        'tmp_name' => $attachment['tmp_name'],
        'filename' => $filename,
        'mimetype' => $allowedmimetypes[$extension],
    ];
}

if ($message === '' && !$validatedattachment) {
    $response(['success' => false, 'error' => 'Write a message or attach an image.']);
}

$transaction = $DB->start_delegated_transaction();
$now = time();
$record = (object) [
    'chatid' => $chatid,
    'senderid' => (int) $USER->id,
    'sendertype' => $sendertype,
    'message' => $message,
    'timecreated' => $now,
];
$messageid = $DB->insert_record('local_mydashboard_chat_messages', $record);
$attachmenturl = '';
$attachmentname = '';

if ($validatedattachment) {
    $contextid = context_system::instance()->id;
    $filerecord = [
        'contextid' => $contextid,
        'component' => 'local_mydashboard',
        'filearea' => 'chat_message_attachment',
        'itemid' => $messageid,
        'filepath' => '/',
        'filename' => $validatedattachment['filename'],
        'mimetype' => $validatedattachment['mimetype'],
        'userid' => (int) $USER->id,
    ];
    $file = get_file_storage()->create_file_from_pathname($filerecord, $validatedattachment['tmp_name']);
    if (!$file) {
        throw new moodle_exception('Unable to save the chat attachment.');
    }
    $attachmentname = $file->get_filename();
    $attachmenturl = moodle_url::make_pluginfile_url(
        $contextid,
        'local_mydashboard',
        'chat_message_attachment',
        $messageid,
        $file->get_filepath(),
        $attachmentname
    )->out(false);
    $DB->set_field('local_mydashboard_chat_messages', 'attachment', $attachmentname, ['id' => $messageid]);
}

$DB->set_field('local_mydashboard_chat', 'timemodified', $now, ['id' => $chatid]);
local_mydashboard_mark_chat_read($chatid, (int) $USER->id, $messageid);
$transaction->allow_commit();

$response([
    'success' => true,
    'message' => [
        'id' => (int) $messageid,
        'message' => format_text($message, FORMAT_MOODLE),
        'sendertype' => $sendertype,
        'ismine' => true,
        'timestamp' => userdate($now, '%d %b %Y, %I:%M %p'),
        'attachmenturl' => $attachmenturl,
        'attachmentname' => $attachmentname,
    ],
]);
