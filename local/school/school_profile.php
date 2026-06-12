<?php
require_once("../../config.php");
require_once($CFG->dirroot . '/local/school/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Get 'id' from URL parameter, default to 0 if not set

if (!$id) {
    print_error('invalidid', 'local_school'); // Handle the case where no ID is provided
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title('School Profile');
$PAGE->navbar->add('School Management', "$CFG->wwwroot/local/school/index.php");
$PAGE->navbar->add(' School Details', "");
$PAGE->set_heading('School Profile');

// Fetch the school record by ID
$school = $DB->get_record('school', ['id' => $id]);

if (!$school) {
    print_error('recordnotfound', 'local_school'); // Handle the case where no record is found
}

// Prepare the data for the template
$templatecontext = [
    'school' => $school,
    'bannerurl' => local_school_get_banner_url($id),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_school/school_profile', $templatecontext);
echo $OUTPUT->footer();
