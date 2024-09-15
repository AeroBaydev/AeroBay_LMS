<?php

require_once("$CFG->libdir/formslib.php");

class upload_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'schoolid', get_string('schoolid', 'local_csvpoc'));
        $mform->setType('schoolid', PARAM_INT);

        $mform->addElement('hidden', 'gradeid', get_string('gradeid', 'local_csvpoc'));
        $mform->setType('gradeid', PARAM_INT);

        $mform->addElement('hidden', 'courseid', get_string('courseid', 'local_csvpoc'));
        $mform->setType('courseid', PARAM_INT);

        // $mform->addElement('text', 'sectionid', get_string('sectionid', 'local_csvpoc'));
        // $mform->setType('sectionid', PARAM_INT);


        $url = new moodle_url('studentExample.csv');
        $link = html_writer::link($url, 'studentExample.csv');
        $mform->addElement('static', 'examplecsv', get_string('examplecsv', 'tool_uploaduser'), $link);
        $mform->addHelpButton('examplecsv', 'examplecsv', 'tool_uploaduser');

        $mform->addElement('filepicker', 'userfile', get_string('file'), null, array('maxbytes' => 50000, 'accepted_types' => '.csv'));
        $mform->addRule('userfile', null, 'required', null, 'client');
        // $mform->addElement('submit', 'submitbutton', get_string('upload'));

        // $mform->addElement('cancel', 'cancelbutton', get_string('cancel'));
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('upload'));
        $buttonarray[] = $mform->createElement('cancel', 'cancelbutton', get_string('cancel'));

        // Add the button array to the form
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
        
    }
}
?>
