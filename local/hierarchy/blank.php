<?php

require_once(__DIR__ . '/../../config.php');
global $CFG, $DB;

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Blank');
$PAGE->set_heading('Blank');

$PAGE->set_url(new moodle_url('/local/hierarchy/blank.php'));

echo $OUTPUT->header();

$a=1;
$a=(500/1000);
echo 5%2;

echo $OUTPUT->footer();

?>