<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once('classes/form/department_form.php');

global $PAGE, $CFG;

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Department Form');
$PAGE->set_heading('Department Form');


$mform = new department_form();

if ($mform->is_cancelled()) {

    redirect("$CFG->wwwroot/my/");
} elseif ($data = $mform->get_data()) {

    $DB->insert_record('school', $data);

    redirect("$CFG->wwwroot/my/");
} else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
