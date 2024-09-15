<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once('forms/upload_form.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/uploaduser/locallib.php');

global $DB, $OUTPUT, $PAGE;

require_login(); // Ensure the user is logged in
$context = context_system::instance();
$PAGE->set_context($context); // Set the context for the page
$PAGE->set_url(new moodle_url('/local/csvpoc/index.php'));
$PAGE->set_title(get_string('pluginname', 'local_csvpoc'));
$PAGE->set_heading(get_string('pluginname', 'local_csvpoc'));

// admin_externalpage_setup('admin');

$mform = new upload_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/csvpoc/index.php'));
} else if ($data = $mform->get_data()) {
    $content = $mform->get_file_content('userfile');
    if ($content === false) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('fileuploaderror', 'local_csvpoc'), 'notifyproblem');
        echo $OUTPUT->footer();
        die();
    }

    // Define local plugin path
    $pluginpath = __DIR__;
    
    // Ensure the temp directory exists and is writable
    //$tempdir = $pluginpath . '/temp/csvimport/2';
    $tempdir ='/var/www/moodledev/temp/csvimport/uploaduser/2';
    if (!file_exists($tempdir)) {
        mkdir($tempdir, 0777, true);
    }

    if (!is_writable($tempdir)) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification("Temp directory is not writable", 'notifyproblem');
        echo $OUTPUT->footer();
        die();
    }

    // Create a temporary file
    $tempfile = $tempdir . '/uploadusers_' . uniqid();
    $filehandle = fopen($tempfile, 'w');
    if ($filehandle === false) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('filehandleerror', 'local_csvpoc'), 'notifyproblem');
        echo $OUTPUT->footer();
        die();
    }

    // Write content to the temporary file
    if (fwrite($filehandle, $content) === false) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('filewriteerror', 'local_csvpoc'), 'notifyproblem');
        echo $OUTPUT->footer();
        fclose($filehandle);
        die();
    }
    fclose($filehandle);

    // Check if the file is readable
    if (!is_readable($tempfile)) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification("Temp file is not readable", 'notifyproblem');
        echo $OUTPUT->footer();
        die();
    }

    // Pass the temporary file path to the csv_import_reader
    $iid = csv_import_reader::get_new_iid('uploaduser');
    $csvreader  = new csv_import_reader($iid, 'uploaduser');

    $process = new \tool_uploaduser\process($csvreader);
    $filecolumns = $process->get_file_columns();

    print_r($filecolumns);

    // $csvreader->load_csv_content($tempfile, 'utf-8', 'comma');
    // $columns = $csvreader->get_columns();

    // $users = [];
    // while ($line = $csvreader->next()) {
    //     $user = new stdClass();
    //     $user->username = trim($line[0]);
    //     $user->firstname = trim($line[1]);
    //     $user->lastname = trim($line[2]);
    //     $user->email = trim($line[3]);

    //     // Check if user exists
    //     if ($DB->record_exists('user', array('username' => $user->username))) {
    //         $user->exists = true;
    //     } else {
    //         $user->exists = false;
    //     }

    //     $users[] = $user;
    // }

    // $csvreader->close();

    // if (!$csvreader->load_csv_content($tempfile, 'utf-8', 'comma')) {
    //     echo $OUTPUT->header();
    //     echo $OUTPUT->notification("Error loading CSV content", 'notifyproblem');
    //     echo $OUTPUT->footer();
    //     die();
    // }

    // $columns = $csvreader->get_columns();

    // if (!$columns) {
    //     echo $OUTPUT->header();
    //     echo $OUTPUT->notification("No columns found in CSV file", 'notifyproblem');
    //     echo $OUTPUT->footer();
    //     die();
    // }

    // $users = [];
    // while ($line = $csvreader->next()) {
    //     if (empty($line)) {
    //         continue;
    //     }

    //     $user = new stdClass();
    //     $user->username = trim($line[0]);
    //     $user->firstname = trim($line[1]);
    //     $user->lastname = trim($line[2]);
    //     $user->email = trim($line[3]);

    //     // Check if user exists
    //     if ($DB->record_exists('user', array('username' => $user->username))) {
    //         $user->exists = true;
    //     } else {
    //         $user->exists = false;
    //     }

    //     $users[] = $user;
    // }

    // $csvreader->close();













    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('uploadusersresult', 'local_csvpoc'));

    echo html_writer::start_tag('table', array('class' => 'generaltable'));
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('username'));
    echo html_writer::tag('th', get_string('firstname'));
    echo html_writer::tag('th', get_string('lastname'));
    echo html_writer::tag('th', get_string('email'));
    echo html_writer::tag('th', get_string('exists', 'local_csvpoc'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($users as $user) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $user->username);
        echo html_writer::tag('td', $user->firstname);
        echo html_writer::tag('td', $user->lastname);
        echo html_writer::tag('td', $user->email);
        echo html_writer::tag('td', $user->exists ? get_string('yes') : get_string('no'));
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    // Add a continue button
    $continueurl = new moodle_url('/local/csvpoc/continue.php', array('users' => json_encode($users)));
    echo $OUTPUT->single_button($continueurl, get_string('continue'));

    echo $OUTPUT->footer();
} else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
