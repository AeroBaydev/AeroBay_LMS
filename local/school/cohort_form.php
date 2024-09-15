<?php
require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once('classes/form/cohort_form.php');
global $DB, $OUTPUT;

$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('cohort', 'local_school'));
$PAGE->set_heading(get_string('cohort', 'local_school'));
$PAGE->set_pagelayout('standard');

$form = new cohort_form();

if ($form->is_cancelled()) {
    redirect("$CFG->wwwroot/my/");
} elseif ($data = $form->get_data()) {
    $selected_cohort_id = $data->cohortid;
    $selected_user_ids = (array)$data->userids; 
    foreach ($selected_user_ids as $user_id) {
        cohort_add_member($selected_cohort_id, $user_id);
    }
    redirect("$CFG->wwwroot/cohort/index.php?contextid=1&showall=1");
} else {
    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
?>
