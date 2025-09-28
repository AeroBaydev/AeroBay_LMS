<?php
require('../../config.php');
require_once($CFG->libdir.'/formslib.php');
require_login();

$id = required_param('id', PARAM_INT);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/videohub/edit.php', ['id'=>$id]));
$PAGE->set_title(get_string('updatevideo','local_videohub'));
$PAGE->set_heading(get_string('updatevideo','local_videohub'));

require_once(__DIR__.'/classes/form/upload_form.php');

global $DB, $USER;
$post = $DB->get_record('local_videohub_vid', ['id'=>$id], '*', MUST_EXIST);
if (!local_videohub_can_manage($post)) {
    print_error('nopermissions', '', '', 'edit video');
}

$mform = new \local_videohub\form\upload_form(null, ['postid'=>$id]);

$draftitemid = file_get_submitted_draft_itemid('videofile');
file_prepare_draft_area($draftitemid, $context->id, 'local_videohub', 'video', $id, ['subdirs'=>0,'maxfiles'=>1]);
$toform = new stdClass();
$toform->id = $id;
$toform->title = $post->title;
$toform->description = ['text'=>$post->description, 'format'=>FORMAT_HTML];
$toform->visibility = $post->visibility;
$toform->videofile = $draftitemid;
$mform->set_data($toform);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/videohub/index.php'));
} else if ($data = $mform->get_data()) {
    $post->title = $data->title;
    $post->description = $data->description['text'] ?? '';
    $post->visibility = $data->visibility;
    $post->timemodified = time();
    $DB->update_record('local_videohub_vid', $post);

    file_save_draft_area_files($data->videofile, $context->id, 'local_videohub', 'video', $id, ['subdirs'=>0,'maxfiles'=>1]);

    // Update metadata again after edits.
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'local_videohub', 'video', $id, 'id DESC', false);
    if ($files) {
        $file = reset($files);
        $DB->update_record('local_videohub_vid', (object)[
            'id' => $id,
            'filename' => $file->get_filename(),
            'contenthash' => $file->get_contenthash(),
        ]);
    }

    redirect(new moodle_url('/local/videohub/index.php'));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
