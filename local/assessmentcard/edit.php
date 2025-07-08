<?php
require_once('../../config.php');
require_once('classes/form/card_form.php');

global $DB, $PAGE, $OUTPUT;

echo $assessmentcardid = optional_param('id', 0, PARAM_INT); // Get the ID from the URL
$actionurl = new moodle_url('/local/assessmentcard/edit.php', array('assessmentcardid' => $assessmentcardid));
die;
$PAGE->set_url($actionurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('editassessmentcard', 'local_assessmentcard'));
$PAGE->set_heading(get_string('editassessmentcard', 'local_assessmentcard'));

// Instantiate the form with the assessmentcard ID
// $mform = new card_form($actionurl, $assessmentcardid);

if ($mform->is_cancelled()) {
    // Handle form cancellation
    redirect(new moodle_url('/local/assessmentcard/index.php'));
} else if ($data = $mform->get_data()) {
    // Handle form submission
    $data->timemodified = time();

    if ($assessmentcardid) {
        // Update existing record
        $assessmentcard = new stdClass();
        $assessmentcard->id = $assessmentcardid;

        $new_name = $mform->get_new_filename('badgefile');

        $path= 'badgesimg/'.$new_name;
        $fullpath = "$CFG->httpswwwroot/local/assessmentcard/". $path;
        $success = $mform->save_file('badgefile', $path, true);

        // Save record to DB
        
        $assessmentcard->name = $data->name;
    
        $assessmentcard->imgpath = $fullpath;
        $assessmentcard->rang1 = $data->rang1; 
        $assessmentcard->rang2 = $data->rang2; 
      print_r($assessmentcard);
      //   $DB->update_record('assessmentcard', $assessmentcard);
    } else {
        // Insert new record
        $DB->insert_record('assessmentcard', $data);
    }

    redirect(new moodle_url('/local/assessmentcard/index.php'));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();