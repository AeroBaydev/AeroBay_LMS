<?php
require_once('../../config.php');
require_once('classes/form/subcard_form.php');

global $DB, $PAGE, $OUTPUT;

// Get the ID from the URL
$assessmentcardid = required_param('id', PARAM_INT); 
$parent = required_param('parent', PARAM_INT); 

// Set up the page
$actionurl = new moodle_url('/local/assessmentcard/subedit_card.php', array('id' => $assessmentcardid,'parent'=>$parent));
$PAGE->set_url($actionurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('editassessmentcard', 'local_assessmentcard'));
$PAGE->set_heading(get_string('editassessmentcard', 'local_assessmentcard'));

// Instantiate the form with the assessmentcard ID
$mform = new subcard_form($actionurl, $assessmentcardid,$parent);

if ($mform->is_cancelled()) {
    // Handle form cancellation
    redirect(new moodle_url("/local/assessmentcard/subcardindex.php?id=$parent"));
} else if ($data = $mform->get_data()) {
    // Handle form submission (only updates, no new entries)
    $assessmentcard = new stdClass();
    $assessmentcard->id = $assessmentcardid; // Use the provided ID for the update

    // Handle file upload
    $new_name = $mform->get_new_filename('badgefile');
    if ($new_name) {
        // Save the file to a specific directory
        $path = 'badgesimg/' . $new_name;
        $fullpath = "$CFG->dirroot/local/assessmentcard/" . $path;
        $success = $mform->save_file('badgefile', $fullpath, true);

        if ($success) {
            // Update the image path in the database
            $assessmentcard->imgpath = $path;
        }
    }

    // Update other fields
    $assessmentcard->name = $data->name;
    $assessmentcard->rang1 = $data->rang1;
    $assessmentcard->rang2 = $data->rang2;
    $assessmentcard->timemodified = time();
    $assessmentcard->parentid = $data->parentid;
   

    // Update the record in the database
    $DB->update_record('assessmentcard', $assessmentcard);

    // Redirect after updating
    redirect(new moodle_url("/local/assessmentcard/subcardindex.php?id=$parent"));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();