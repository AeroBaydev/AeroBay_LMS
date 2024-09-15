<?php
require_once('../../config.php');

global $DB, $OUTPUT, $PAGE;

require_login();
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/emailtemplates/index.php'));
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('pluginname', 'local_emailtemplates'));
$PAGE->set_heading(get_string('pluginname', 'local_emailtemplates'));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pluginname', 'local_emailtemplates'));

// Fetch all email templates from the database
$templates = $DB->get_records('local_emailtemplates');

// Create a new HTML table to display the templates
$table = new html_table();
$table->head = [
    get_string('templatename', 'local_emailtemplates'),   // Column for template name
    get_string('subject', 'local_emailtemplates'),        // Column for template subject
    get_string('timemodified', 'local_emailtemplates'),   // Column for last modified time
    get_string('actions', 'local_emailtemplates')         // Column for action links (edit, delete)
];

// Iterate through each template and populate the table rows
foreach ($templates as $template) {
    $editurl = new moodle_url('/local/emailtemplates/edit.php', ['id' => $template->id]); // Edit URL
    $deleteurl = new moodle_url('/local/emailtemplates/delete.php', ['id' => $template->id]); // Delete URL

    // Actions column with Edit and Delete links
    $actions = html_writer::link($editurl, get_string('edittemplate', 'local_emailtemplates')) . ' | ' .
               html_writer::link($deleteurl, get_string('deletetemplate', 'local_emailtemplates'));

    // Add a row to the table with the template data
    $table->data[] = [
        format_string($template->name),                // Template name
        format_string($template->subject),             // Template subject
        userdate($template->timemodified),             // Last modified time
        $actions                                      // Edit and Delete actions
    ];
}

// Display the table
echo html_writer::table($table);

// Add button to create a new template
$addurl = new moodle_url('/local/emailtemplates/edit.php');
echo $OUTPUT->single_button($addurl, get_string('addtemplate', 'local_emailtemplates'));

echo $OUTPUT->footer();
