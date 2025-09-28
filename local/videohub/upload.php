<?php
require('../../config.php');
require('lib.php');
require_once($CFG->libdir.'/formslib.php');
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/videohub/upload.php'));
$PAGE->set_title(get_string('addvideo','local_videohub'));
$PAGE->set_heading(get_string('addvideo','local_videohub'));

//require_capability('local/videohub:post', $context);

require_once(__DIR__.'/classes/form/upload_form.php');

$mform = new \local_videohub\form\upload_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/videohub/index.php'));
} else if ($data = $mform->get_data()) {
    global $DB, $USER;

    list($myschool, $mygrade) = local_videohub_get_user_school_grade($USER->id);

    $rec = (object)[
        'userid' => $USER->id,
        'schoolid' => $myschool,
        'gradeid' => $mygrade,
        'title' => $data->title,
        'description' => $data->description['text'] ?? '',
        'visibility' => $data->visibility,
        'status' => 1,
        'timecreated' => time(),
        'timemodified' => time(),
    ];
    $id = $DB->insert_record('local_videohub_vid', $rec);

    // Save file into moodledata.
    $draftid = $data->videofile ?? 0;
    $fileoptions = ['subdirs'=>0,'maxfiles'=>1,'maxbytes'=>get_max_upload_file_size()];
    file_save_draft_area_files($draftid, $context->id, 'local_videohub', 'video', $id, $fileoptions);

    // Read file record and store filename + contenthash in our table for convenience.
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
$PAGE->requires->js(new moodle_url('/local/videohub/custom.js'));
echo $OUTPUT->header();

$mform->display();
echo $OUTPUT->footer();

