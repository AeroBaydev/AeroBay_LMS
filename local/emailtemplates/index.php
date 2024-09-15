<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/local/emailtemplates/classes/form/edit_template_form.php');

global $DB, $OUTPUT, $PAGE;

$id = optional_param('id', 0, PARAM_INT); // ID parameter for editing an existing template

require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/emailtemplates/edit.php', ['id' => $id]));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('edittemplate', 'local_emailtemplates'));
$PAGE->set_heading(get_string('edittemplate', 'local_emailtemplates'));

// Load existing template if editing
$template = $id ? $DB->get_record('local_emailtemplates', ['id' => $id], '*', MUST_EXIST) : null;

$mform = new \local_emailtemplates\form\edit_template_form(null, ['id' => $id]);

if ($mform->is_cancelled()) {
    // If form is canceled, redirect to the list
    redirect(new moodle_url('/local/emailtemplates/list.php'));
} else if ($data = $mform->get_data()) {
    $data->timemodified = time();
    
    // Ensure that the body content is handled correctly (assuming the body is a simple text field)
    $data->body = $data->body['text'] ?? $data->body; // Adjust if 'body' is an editor

    if ($id) {
        // Update existing template
        $data->id = $id;
        $DB->update_record('local_emailtemplates', $data);
    } else {
        // Insert new template
        $data->timecreated = time();
        $DB->insert_record('local_emailtemplates', $data);
    }

    // Redirect back to the index page
    redirect(new moodle_url('/local/emailtemplates/list.php'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('edittemplate', 'local_emailtemplates'));

// Set form data for editing
$mform->set_data($template);
$mform->display();

echo $OUTPUT->footer();
