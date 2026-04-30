<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class assign_arm extends moodleform {
   
    public function definition() {
  
        global $DB;

        $mform = $this->_form; 


        // $rm = $DB->get_records_menu('regionalpoc', array('role' => 'rm'), '', 'id, username');

       
        // // $mform->addElement('select', 'cohortid', get_string('selectcohort', 'local_school'), $cohorts);
        // $mform->addElement('select', 'role', get_string('selectrole', 'local_regionalpoc'), array('0' => get_string('selectrole', 'local_regionalpoc')) + $rm);

        // $mform->setType('roleid', PARAM_INT);
        // $mform->addRule('role', get_string('required'), 'required', null, 'client');
      
        // $this->add_action_buttons(true, get_string('submit'));

    }

    function validation($data, $files) {
        return [];
    }
}