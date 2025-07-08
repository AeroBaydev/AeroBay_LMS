<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
require_once("$CFG->dirroot/local/sessioncard/lib.php");
 $PAGE->requires->js(new moodle_url("$CFG->wwwroot/local/sessioncard/style/main.js"));


class card_form extends moodleform
{
    public function definition()
    {
        global $DB;

        $mform = $this->_form;

        // Hidden sessioncard ID
        $mform->addElement('hidden', 'sessioncardid');
        $mform->setType('sessioncardid', PARAM_INT);

        // Heading
        // $heading_text = "Add sessioncard";
        // $heading = html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-new-school'));
        // $mform->addElement('html', $heading);

        // sessioncard text field
       

        $mform->addElement('textarea', 'name', get_string('name', 'local_sessioncard'), 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // File upload field
        $mform->addElement('filepicker', 'badgefile', get_string('uploadbadge', 'local_sessioncard'), null, 
            array('accepted_types' => array('.png', '.jpg', '.jpeg')));
        $mform->addRule('badgefile', null, 'required', null, 'client');
      
        
         $completion = array(25 => 25, 50 => 50, 75 => 75, 100 => 100);
$completion_options = array(0 => get_string('pleaseselectCompletion', 'local_sessioncard')) + $completion; // Add "Please select" as the first option

$mform->addElement('select', 'completion', get_string('completion', 'local_sessioncard'), $completion_options); // Use $grade_options to include the new options in the dropdown
$mform->addRule('completion', get_string('required'), 'required', null, 'client');

        // Hidden field for time created
        $mform->addElement('hidden', 'timecreated', time());
        $mform->setType('timecreated', PARAM_INT);

        // Submit button
        $this->add_action_buttons(true, get_string('savechanges', 'local_sessioncard'));
    }

    public function validation($data, $files)
    {
        global $DB;
        $errors = parent::validation($data, $files);

        // Validate if the school exists
        // if (!$DB->record_exists('school', array('id' => $data['schoolid']))) {
        //     $errors['schoolid'] = get_string('invalidschool', 'local_sessioncard');
        // }

        //Validate if the grade exists
        if ($DB->record_exists('sessioncard', array('percentages' => $data['completion']))) {
            $errors['completion'] = get_string('invalidcompletion', 'local_sessioncard');
        }

        return $errors;
    }
}
