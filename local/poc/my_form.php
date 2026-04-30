<?php
// File: my_form.php
require_once("$CFG->libdir/formslib.php");

class my_form extends moodleform {
    // Form definition
    public function definition() {
        $mform = $this->_form; // Don't forget the underscore!

        // Add a header
       // $mform->addElement('header', 'displayinfo', get_string('formheader', 'yourplugin'));
        $mform->addElement('header', 'displayinfo', get_string('formheader', 'yourplugin'));
        // Start a new group for the card layout
        $mform->addElement('html', '<div class="card"><div class="card-body"><div class="row">');

        // Left side content
        $mform->addElement('html', '<div class="col-md-6">');
        $mform->addElement('text', 'leftfield1', get_string('leftfield1', 'yourplugin'));
        $mform->setType('leftfield1', PARAM_NOTAGS);
        $mform->addElement('text', 'leftfield2', get_string('leftfield2', 'yourplugin'));
        $mform->setType('leftfield2', PARAM_NOTAGS);
        $mform->addElement('html', '</div>');

        // Right side content
        $mform->addElement('html', '<div class="col-md-6">');
        $mform->addElement('text', 'rightfield1', get_string('rightfield1', 'yourplugin'));
        $mform->setType('rightfield1', PARAM_NOTAGS);
        $mform->addElement('text', 'rightfield2', get_string('rightfield2', 'yourplugin'));
        $mform->setType('rightfield2', PARAM_NOTAGS);
        $mform->addElement('html', '</div>');

        // Close the row and card
        $mform->addElement('html', '</div></div></div>');

        // Add action buttons
        $this->add_action_buttons();
    }
}
