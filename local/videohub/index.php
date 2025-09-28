<?php
require('../../config.php');
require('lib.php');
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/videohub/index.php'));
$PAGE->set_title(get_string('pluginname','local_videohub'));
$PAGE->set_heading(get_string('pluginname','local_videohub'));
$PAGE->set_pagelayout('poup');
$mode = optional_param('mode', 'class', PARAM_ALPHA);
$perpage = (int)(get_config('local_videohub', 'perpage') ?? 1);
$page = optional_param('page', 0, PARAM_INT);
$PAGE->requires->js(new moodle_url('/local/videohub/custom.js'));
list($myschool, $mygrade) = local_videohub_get_user_school_grade($USER->id);

$where = "status = 1";
$params = [];

if ($mode === 'mine') {
    $where .= " AND userid = :u";
    $params['u'] = $USER->id;
} else if ($mode === 'all') {
    $where .= " AND (visibility = 2 OR (visibility = 1 AND schoolid = :s AND gradeid = :g) OR userid = :u)";
    $params += ['s'=>$myschool, 'g'=>$mygrade, 'u'=>$USER->id];
} else {
    $where .= " AND ((visibility = 1 AND schoolid = :s AND gradeid = :g) OR (userid = :u))";
    $params += ['s'=>$myschool, 'g'=>$mygrade, 'u'=>$USER->id];
}

$total = $DB->count_records_select('local_videohub_vid', $where, $params);
$records = $DB->get_records_select('local_videohub_vid', $where, $params, 'timecreated DESC', '*', $page*$perpage, $perpage);

echo $OUTPUT->header();

$btns = [];
$btns[] = html_writer::link(new moodle_url('/local/videohub/upload.php'), get_string('addvideo','local_videohub'), ['class'=>'btn btn-primary']);
$btns[] = html_writer::link(new moodle_url('/local/videohub/index.php', ['mode'=>'mine']), get_string('myvideos','local_videohub'), ['class'=>'btn btn-secondary']);
//$btns[] = html_writer::link(new moodle_url('/local/videohub/index.php', ['mode'=>'class']), get_string('classfeed','local_videohub'), ['class'=>'btn btn-secondary']);
$btns[] = html_writer::link(new moodle_url('/local/videohub/index.php', ['mode'=>'all']), get_string('allvideos','local_videohub'), ['class'=>'btn btn-secondary']);
if (has_capability('local/videohub:manageany', $context)) {
    $btns[] = html_writer::link(new moodle_url('/local/videohub/manage.php'), get_string('managefiles','local_videohub'), ['class'=>'btn btn-warning']);
}
echo html_writer::div(implode(' ', $btns), 'mb-3');

if (!$records) {
    echo $OUTPUT->notification(get_string('novideos','local_videohub'), \core\output\notification::NOTIFY_INFO);
} else {
    $fs = get_file_storage();
    foreach ($records as $rec) {
        // Prefer table metadata; fallback to File API.
        $filename = $rec->filename ?? null;

        $files = $fs->get_area_files($context->id, 'local_videohub', 'video', $rec->id, 'id DESC', false);
        $file = $files ? reset($files) : null;
        if (!$filename && $file) { $filename = $file->get_filename(); }

        $videourl = $filename ? moodle_url::make_pluginfile_url($context->id, 'local_videohub', 'video', $rec->id, '/', $filename) : null;

        $by = fullname(core_user::get_user($rec->userid));
        $date = userdate($rec->timecreated);
        $meta = get_string('postedby','local_videohub', (object)['name'=>$by,'date'=>$date]);

        echo html_writer::start_div('card mb-3');
        echo html_writer::div(html_writer::tag('h5', format_string($rec->title)), 'card-header');
        echo html_writer::start_div('card-body');

        // if ($videourl) {
        //     $mimetype = $file ? $file->get_mimetype() : 'video/mp4';
        //     echo html_writer::tag('video', html_writer::tag('source', '', ['src'=>$videourl, 'type'=>$mimetype]), ['controls'=>'controls', 'style'=>'max-width:100%']);
        // }
        if ($videourl) {
    $mimetype = $file ? $file->get_mimetype() : 'video/mp4';
    echo html_writer::div(
        html_writer::tag('video',
            html_writer::tag('source', '', ['src'=>$videourl, 'type'=>$mimetype]),
            ['controls' => 'controls', 'style' => 'max-width:80%; height:auto;']
        ),
        'd-flex justify-content-center'
    );
}

        if (!empty($rec->description)) {
            echo html_writer::div(format_text($rec->description, FORMAT_HTML), 'mt-2');
        }
        echo html_writer::div(s($meta), 'text-muted small mt-2');

        $actions = [];
        if (local_videohub_can_manage($rec)) {
            $actions[] = html_writer::link(new moodle_url('/local/videohub/edit.php', ['id'=>$rec->id]), get_string('updatevideo','local_videohub'), ['class'=>'btn btn-outline-secondary btn-sm']);
            $actions[] = html_writer::link(new moodle_url('/local/videohub/delete.php', ['id'=>$rec->id, 'sesskey'=>sesskey()]), get_string('deletevideo','local_videohub'), ['class'=>'btn btn-outline-danger btn-sm']);
            $actions[] = html_writer::link(new moodle_url('/local/videohub/delete.php', ['id'=>$rec->id, 'hard'=>1, 'sesskey'=>sesskey()]), 'Delete + remove file', ['class'=>'btn btn-danger btn-sm']);
        }
        echo html_writer::div(implode(' ', $actions), 'mt-2');

        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    echo $OUTPUT->paging_bar($total, $page, $perpage, new moodle_url('/local/videohub/index.php', ['mode'=>$mode]));
}

echo $OUTPUT->footer();
