<?php
require_once("../../config.php");


$context = context_system::instance();
$page = optional_param('page', 0, PARAM_INT); 
$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");

$data = $DB->get_record_sql("SELECT * FROM {school}");

    $templatecontext = [
     
    ];

    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_school/school_profile', $templatecontext);
    echo $OUTPUT->footer();


