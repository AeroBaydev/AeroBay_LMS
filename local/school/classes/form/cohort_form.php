<?php
// cohort_select_form.php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class cohort_form extends moodleform {
    // Define the form
    public function definition() {
        global $DB; // Make sure to include this line
        
        $mform = $this->_form;
        
        // Query to select cohorts
        $cohorts = $DB->get_records_menu('cohort', null, '', 'id, name');
        
        // Add cohort select field
        $mform->addElement('select', 'cohortid', get_string('selectcohort', 'local_school'), $cohorts);
        $mform->setType('cohortid', PARAM_INT);
        
        // Query to select users
        $users = $DB->get_records('user');
        
        // Add multiple select dropdown field for users
        $user_options = array();
        foreach ($users as $user) {
            $user_options[$user->id] = fullname($user);
        }
        $mform->addElement('select', 'userids', get_string('selectuser', 'local_school'), $user_options, array('multiple' => true));
        $mform->setType('userids', PARAM_INT);
        
        // Add submit button
        $this->add_action_buttons(true, get_string('submit'));
    }

    // Define validation rules if needed
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        // Add validation rules if necessary
        return $errors;
    }
}
?>
