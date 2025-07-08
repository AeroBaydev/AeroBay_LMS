<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
require_once("$CFG->dirroot/local/attendancecard/lib.php");
 $PAGE->requires->js(new moodle_url("$CFG->wwwroot/local/attendancecard/style/main.js"));


class card_form extends moodleform
{
    public function definition()
    {
        global $DB;

        $mform = $this->_form;

        // Hidden attendancecard ID
        $mform->addElement('hidden', 'attendancecardid');
        $mform->setType('attendancecardid', PARAM_INT);

        // Heading
        // $heading_text = "Add attendancecard";
        // $heading = html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-new-school'));
        // $mform->addElement('html', $heading);

        // attendancecard text field
       

        $mform->addElement('textarea', 'name', get_string('name', 'local_attendancecard'), 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // File upload field
        $mform->addElement('filepicker', 'badgefile', get_string('uploadbadge', 'local_attendancecard'), null, 
            array('accepted_types' => array('.png', '.jpg', '.jpeg')));
        $mform->addRule('badgefile', null, 'required', null, 'client');
      
        
         $completion = array(1 => "80%-90%", 2 => "90%-95%", 3 => "95%");
$completion_options = array(0 => get_string('pleaseselectCompletion', 'local_attendancecard')) + $completion; // Add "Please select" as the first option

$mform->addElement('select', 'completion', get_string('completion', 'local_attendancecard'), $completion_options); // Use $grade_options to include the new options in the dropdown
$mform->addRule('completion', get_string('required'), 'required', null, 'client');

        // Hidden field for time created
        $mform->addElement('hidden', 'timecreated', time());
        $mform->setType('timecreated', PARAM_INT);

        // Submit button
        $this->add_action_buttons(true, get_string('savechanges', 'local_attendancecard'));
    }

    public function validation($data, $files)
    {
        global $DB;
        $errors = parent::validation($data, $files);

        // Validate if the school exists
        // if (!$DB->record_exists('school', array('id' => $data['schoolid']))) {
        //     $errors['schoolid'] = get_string('invalidschool', 'local_attendancecard');
        // }

        //Validate if the grade exists
        if ($DB->record_exists('attendancecard', array('percentages' => $data['completion']))) {
            $errors['completion'] = get_string('invalidcompletion', 'local_attendancecard');
        }

        return $errors;
    }
}
