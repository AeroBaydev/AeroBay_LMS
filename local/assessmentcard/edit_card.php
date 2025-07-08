<?php
require_once('../../config.php');
require_once('classes/form/card_form.php');

global $DB, $PAGE, $OUTPUT;

// Get the ID from the URL
$assessmentcardid = required_param('id', PARAM_INT); // Require an ID, or throw an error if missing

// Set up the page
$actionurl = new moodle_url('/local/assessmentcard/edit_card.php', array('id' => $assessmentcardid));
$PAGE->set_url($actionurl);
$PAGE->set_context(context_system::instance());
$PAGE->navbar->add('Assessment Badge Management', "$CFG->wwwroot/local/assessmentcard/index.php");
$PAGE->navbar->add('Edit Assessment Badge', "$CFG->wwwroot/local/assessmentcard/addassessmentcard.php");
$actionurl = new moodle_url("/local/assessmentcard/edit_card.php?id=$assessmentcardid");
$PAGE->set_title(get_string('editassessmentcard', 'local_assessmentcard'));
$PAGE->set_heading(get_string('editassessmentcard', 'local_assessmentcard'));
$PAGE->requires->js('/local/assessmentcard/js/custom.js');
// Instantiate the form with the assessmentcard ID
$mform = new card_form($actionurl, $assessmentcardid);

if ($mform->is_cancelled()) {
    // Handle form cancellation
    redirect(new moodle_url('/local/assessmentcard/index.php'));
} else if ($data = $mform->get_data()) {
    // Handle form submission (only updates, no new entries)
    $assessmentcard = new stdClass();
    $assessmentcard->id = $assessmentcardid; // Use the provided ID for the update

    // Handle file upload
    $new_name = $mform->get_new_filename('badgefile');
    if ($new_name) {
        // Save the file to a specific directory
      
        // $parent = isset($parent) ? $parent : 0;
        $path= 'badgesimg/'.$new_name;
        $fullpath = "$CFG->httpswwwroot/local/assessmentcard/". $path;
        $success = $mform->save_file('badgefile', $path, true);

      

        if ($success) {
            // Update the image path in the database
            $assessmentcard->imgpath = $fullpath;
        }
    }

    // Update other fields
    $assessmentcard->name = $data->name;
    $assessmentcard->rang1 = $data->rang1;
    $assessmentcard->rang2 = $data->rang2;
    $assessmentcard->timemodified = time();
   

    // Update the record in the database
    $DB->update_record('assessmentcard', $assessmentcard);

    // Redirect after updating
    redirect(new moodle_url('/local/assessmentcard/index.php'));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();