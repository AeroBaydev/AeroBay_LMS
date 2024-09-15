<?php

require_once("../../config.php");
require_once $CFG->libdir . '/formslib.php';
require_once "classes/form/poc_mapping.php";
global $PAGE, $OUTPUT, $DB, $CFG;


$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('poc_mapping', 'local_poc'));

$PAGE->set_url(new moodle_url('/local/poc/amd/src/script.js'));
$PAGE->requires->js(new moodle_url('/local/poc/amd/src/script.js'));

$mform = new pocmapping_form();
    

if ($mform->is_cancelled()) {
    redirect("$CFG->wwwroot/local/poc/poc_management.php");
} elseif ($data = $mform->get_data()) {
    $selected_role_id = $data->role;
    redirect("$CFG->wwwroot/local/poc/poc_mapping.php?selected_role_id=$selected_role_id");
} else {
    echo $OUTPUT->header();
    $mform->display();
    // if ($fromform = $mform->get_data()) {
        $selected_role_id1= optional_param('selected_role_id', true, PARAM_INT);
        echo $OUTPUT->footer();
    }