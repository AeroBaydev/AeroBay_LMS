<?php
require('../../config.php');
require('lib.php');
require_login();
require_sesskey();

$id = required_param('id', PARAM_INT);
$hard = optional_param('hard', 0, PARAM_INT);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/videohub/delete.php', ['id'=>$id]));

global $DB;
$post = $DB->get_record('local_videohub_vid', ['id'=>$id], '*', MUST_EXIST);
if (!local_videohub_can_manage($post)) {
    print_error('nopermissions', '', '', 'delete video');
}

$DB->set_field('local_videohub_vid', 'status', 0, ['id'=>$id]);

if ($hard) {
    $fs = get_file_storage();
    // Remove all files in the filearea for this itemid.
    $fs->delete_area_files($context->id, 'local_videohub', 'video', $id);
    // Optionally clear metadata.
    $DB->update_record('local_videohub_vid', (object)['id'=>$id, 'filename'=>null, 'contenthash'=>null]);
}

redirect(new moodle_url('/local/videohub/index.php'));
