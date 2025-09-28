<?php
require('../../config.php');
require('lib.php');
require_login();

$context = context_system::instance();
require_capability('local/videohub:manageany', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/videohub/manage.php'));
$PAGE->set_title('Video Hub: File Manager');
$PAGE->set_heading('Video Hub: File Manager');

$action = optional_param('action', '', PARAM_ALPHA);
$id     = optional_param('id', 0, PARAM_INT);

if ($action === 'harddelete' && $id) {
    require_sesskey();
    global $DB;
    if ($post = $DB->get_record('local_videohub_vid', ['id'=>$id])) {
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'local_videohub', 'video', $id);
        $DB->update_record('local_videohub_vid', (object)['id'=>$id, 'status'=>0, 'filename'=>null, 'contenthash'=>null]);
        redirect(new moodle_url('/local/videohub/manage.php'), 'File hard-deleted and post disabled.', 2);
    }
}

echo $OUTPUT->header();

global $CFG, $DB;
echo html_writer::div(html_writer::tag('strong', 'moodledata (CFG->dataroot): ') . s($CFG->dataroot), 'mb-3');

$records = $DB->get_records('local_videohub_vid', null, 'timecreated DESC');
$fs = get_file_storage();

$table = new html_table();
$table->head = ['ID','Title','Owner','Visibility','Filename','Contenthash','Disk path','Actions'];

foreach ($records as $rec) {
    $filename = $rec->filename ?: '-';
    $contenthash = $rec->contenthash ?: '-';

    // Compute disk path from contenthash.
    $diskpath = '-';
    if (!empty($rec->contenthash)) {
        $hash = $rec->contenthash;
        $diskpath = $CFG->dataroot . '/filedir/' . substr($hash,0,2) . '/' . substr($hash,2,2) . '/' . $hash;
    } else {
        // Fallback: ask file API.
        $files = $fs->get_area_files($context->id, 'local_videohub', 'video', $rec->id, 'id DESC', false);
        if ($files) {
            $file = reset($files);
            $filename = $file->get_filename();
            $contenthash = $file->get_contenthash();
            $hash = $contenthash;
            $diskpath = $CFG->dataroot . '/filedir/' . substr($hash,0,2) . '/' . substr($hash,2,2) . '/' . $hash;
        }
    }

    $owner = fullname(core_user::get_user($rec->userid));
    $visibility = ['Private','Class','Site'][$rec->visibility] ?? $rec->visibility;

    $actions = [];
    if ($filename !== '-') {
        $actions[] = html_writer::link(
            moodle_url::make_pluginfile_url($context->id, 'local_videohub', 'video', $rec->id, '/', $filename),
            'Open',
            ['target'=>'_blank']
        );
    }
    $actions[] = html_writer::link(
        new moodle_url('/local/videohub/manage.php', ['action'=>'harddelete','id'=>$rec->id,'sesskey'=>sesskey()]),
        'Hard delete file',
        ['class'=>'btn btn-danger btn-sm']
    );

    $table->data[] = [
        $rec->id,
        format_string($rec->title),
        s($owner),
        s($visibility),
        s($filename),
        s($contenthash),
        s($diskpath),
        implode(' ', $actions)
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
