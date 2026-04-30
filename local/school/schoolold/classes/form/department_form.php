<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");


class department_form extends moodleform
{
    // Add elements to form.
    public function definition()
    {

        $mform = $this->_form; // Don't forget the underscore!
        // Add elements to your form.
        $mform->addElement('text', 'school_name', get_string('school_name','local_school'));
        $mform->addElement('text', 'school_sortname', get_string('school_sortname','local_school'));
      
       

        // Set type of element.
        $mform->setType('text', PARAM_NOTAGS);
        $mform->setType('text', PARAM_NOTAGS);
       

        // Default value.
        $mform->setDefault('school_name', '');
        $mform->setDefault('school_sortname', '');
      

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges'));

        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }

}
