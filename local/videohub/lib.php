<?php
defined('MOODLE_INTERNAL') || die();

function local_videohub_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB, $USER;

    if ($context->contextlevel != CONTEXT_SYSTEM) { return false; }
    if ($filearea !== 'video') { return false; }
    require_login();

    if (count($args) < 2) { return false; }
    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = '/'.implode('/', $args).'/';
    $filepath = preg_replace('|/+|','/',$filepath);

    if (!$post = $DB->get_record('local_videohub_vid', ['id' => $itemid, 'status' => 1])) { return false; }

    $canview = false;
    if ((int)$post->userid === (int)$USER->id) { $canview = true; }
    else if ($post->visibility == 2) { $canview = true; }
    else if ($post->visibility == 1) {
        list($myschool, $mygrade) = local_videohub_get_user_school_grade($USER->id);
        $canview = ($myschool == $post->schoolid && $mygrade == $post->gradeid);
    }

    if (!$canview) { return false; }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_videohub', 'video', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) { return false; }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function local_videohub_get_user_school_grade(int $userid): array {
    global $DB;
    $schoolshort = get_config('local_videohub', 'schoolfield') ?? 'schoolid';
    $gradeshort  = get_config('local_videohub', 'gradefield') ?? 'gradeid';

    $sql = "SELECT s.schoolid as schoolid, s.gradeid as gradeid
              FROM {student} s
             WHERE s.userid = :u";
    $rec = $DB->get_record_sql($sql, ['u'=>$userid]);
    $schoolid = $rec ? (int)$rec->schoolid : 0;
    $gradeid = $rec ? (int)$rec->gradeid : 0;
    return [$schoolid, $gradeid];
}

function local_videohub_can_manage($post): bool {
    global $USER;
    $sysctx = context_system::instance();
    if (has_capability('local/videohub:manageany', $sysctx)) { return true; }
    return ((int)$post->userid === (int)$USER->id);
}
defined('MOODLE_INTERNAL') || die();

// function local_videohub_extend_navigation(global_navigation $nav) {
//     if (!isloggedin() || isguestuser()) { return; }

//     // If it already exists, don’t create twice.
//     if ($nav->find('videohub', navigation_node::TYPE_CUSTOM)) { return; }

//     $node = navigation_node::create(
//         get_string('pluginname', 'local_videohub'),
//         new moodle_url('/local/videohub/index.php'),
//         navigation_node::TYPE_CUSTOM,
//         null,
//         'videohub', // stable key
//         new pix_icon('i/video', '')
//     );
//     // REQUIRED for Boost drawer:
//     $node->showinflatnavigation = true;

//     $nav->add_node($node);
// }
