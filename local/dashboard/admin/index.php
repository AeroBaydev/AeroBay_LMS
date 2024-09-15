<?php

require_once('../../../config.php'); // Moodle configuration file

global $DB;

$title = 'Admin Dashboard';
$pagetitle = $title;
$PAGE->set_title($title);
$PAGE->set_heading($title);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$somdata=array();
 



echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_dashboard/index', $somdata);

echo $OUTPUT->footer();
