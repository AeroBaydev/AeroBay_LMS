<?php
require_once('../../config.php');

global $DB, $OUTPUT, $PAGE;

$id = required_param('id', PARAM_INT);

require_login();
$context = context_system::instance();
$PAGE->set_context($context);

$PAGE->set_url(new moodle_url('/local/emailtemplates/delete.php', ['id' => $id]));
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('deletetemplate', 'local_emailtemplates'));
$PAGE->set_heading(get_string('deletetemplate', 'local_emailtemplates'));

if (confirm_sesskey()) {
    $DB->delete_records('local_emailtemplates', ['id' => $id]);
    redirect(new moodle_url('/local/emailtemplates/index.php'), get_string('deletetemplate', 'local_emailtemplates'));
}

echo $OUTPUT->header();
echo $OUTPUT->confirm(get_string('deletetemplate', 'local_emailtemplates'),
    new moodle_url('/local/emailtemplates/delete.php', ['id' => $id, 'sesskey' => sesskey()]),
    new moodle_url('/local/emailtemplates/index.php'));

echo $OUTPUT->footer();
