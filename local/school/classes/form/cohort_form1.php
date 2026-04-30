<?php
// cohort_select_form.php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class cohort_form extends moodleform {
   
    public function definition() {
        global $DB; 
        
        $mform = $this->_form;
       
        $cohorts = $DB->get_records_menu('cohort', null, '', 'id, name');
       
        // $mform->addElement('select', 'cohortid', get_string('selectcohort', 'local_school'), $cohorts);
        $mform->addElement('select', 'cohortid', get_string('selectcohort', 'local_school'), array('' => get_string('selectcohort', 'local_school')) + $cohorts);

        $mform->setType('cohortid', PARAM_INT);
      
        $this->add_action_buttons(true, get_string('submit'));
    }

}
?>
