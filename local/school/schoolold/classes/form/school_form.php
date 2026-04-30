<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
require_once("$CFG->dirroot/local/school/lib.php");
$PAGE->requires->js(new moodle_url("$CFG->wwwroot/local/school/amd/src/numeric_validation.js"));
class school_form extends moodleform
{
    public function definition()
    {
        $mform = $this->_form;

        // Add elements to your form.
        $mform->addElement('hidden', 'schoolid');
        $mform->setType('schoolid', PARAM_INT);

        $heading_text = "Add New School";
        $heading = html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-new-school'));
        $mform->addElement('html', $heading);

        $mform->addElement('text', 'school_name', get_string('school_name', 'local_school'));
        $mform->setType('school_name', PARAM_TEXT);

        $mform->addElement('text', 'school_sortname', get_string('school_sortname', 'local_school'));
        $mform->setType('school_sortname', PARAM_TEXT);

        $mform->addElement('text', 'school_address', get_string('school_address', 'local_school'));
        $mform->setType('school_address', PARAM_TEXT);

        $mform->addElement('text', 'principal_name', get_string('principal_name', 'local_school'));
        $mform->setType('principal_name', PARAM_TEXT);

        $mform->addElement('text', 'principal_email', get_string('principal_email', 'local_school'));
        $mform->setType('principal_email', PARAM_EMAIL);
        $mform->addRule('principal_email', null, 'required', null, 'client');
        $mform->addRule('principal_email', 'Enter valid email', 'email', null, 'client');
        $mform->addRule('principal_email', 'Enter valid email', 'maxlength', 100, 'client');

        $mform->addElement('text', 'principal_contact', get_string('principal_contact', 'local_school'), array('size' => '10'));
        $mform->setType('principal_contact', PARAM_INT);
        $mform->addRule('principal_contact', null, 'required', null, 'client');
        $mform->addRule('principal_contact', 'Phone number should have 10 digits', 'minlength', 10, 'client');
        $mform->addRule('principal_contact', 'Invalid phone number format', 'regex', '/^\+?\d{1,4}?[-.\s]?\(?\d{1,4}?\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9}$/i', 'client');

        $mform->addElement('select', 'state_name', get_string('state_name', 'local_school'), state());
        $mform->setType('state_name', PARAM_TEXT);

        // Syllabus and fees.
        $mform->addElement('text', 'syllabus', get_string('syllabus', 'local_school'));
        $mform->setType('syllabus', PARAM_TEXT);

        $mform->addElement('text', 'aerobay_fees', get_string('aerobay_fees', 'local_school'));
        $mform->setType('aerobay_fees', PARAM_FLOAT);




        $this->add_coordinator_names($mform, 1);
        $this->add_coordinator_names($mform, 2);
        $this->add_coordinator_names($mform, 3);
        $this->add_coordinator_names($mform, 4);
        // Apply rules for required fields.
        $mform->addRule('school_name', get_string('required'), 'required', null, 'client');
        $mform->addRule('school_sortname', get_string('required'), 'required', null, 'client');
        $mform->addRule('school_address', get_string('required'), 'required', null, 'client');
        $mform->addRule('principal_name', get_string('required'), 'required', null, 'client');
        $mform->addRule('state_name', get_string('required'), 'required', null, 'client');
        $mform->addRule('syllabus', get_string('required'), 'required', null, 'client');
        $mform->addRule('aerobay_fees', get_string('required'), 'required', null, 'client');
        $mform->addRule("coordinator_name1", get_string('required'), 'required', null, 'client');
        $mform->addRule('coordinator_email1', null, 'required', null, 'client');
        $mform->addRule('coordinator_email1', 'Enter valid email', 'email', null, 'client');
        $mform->addRule('coordinator_email1', 'Enter valid email', 'maxlength', 100, 'client');
        $mform->addRule('coordinator_contact1', null, 'required', null, 'client');
        $mform->addRule('coordinator_contact1', 'Phone number should have 10 digits', 'minlength', 10, 'client');
        $mform->addRule('coordinator_contact1', 'Invalid phone number format', 'regex', '/^\+?\d{1,4}?[-.\s]?\(?\d{1,4}?\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9}$/i', 'client');

        $mform->addRule('coordinator_email2', 'Enter valid email', 'email', null, 'client');
        $mform->addRule('coordinator_email2', 'Enter valid email', 'maxlength', 100, 'client');
        $mform->addRule('coordinator_contact2', 'Phone number should have 10 digits', 'minlength', 10, 'client');
        $mform->addRule('coordinator_contact2', 'Invalid phone number format', 'regex', '/^\+?\d{1,4}?[-.\s]?\(?\d{1,4}?\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9}$/i', 'client');

        $mform->addRule('coordinator_email3', 'Enter valid email', 'email', null, 'client');
        $mform->addRule('coordinator_email3', 'Enter valid email', 'maxlength', 100, 'client');
        $mform->addRule('coordinator_contact3', 'Phone number should have 10 digits', 'minlength', 10, 'client');
        $mform->addRule('coordinator_contact3', 'Invalid phone number format', 'regex', '/^\+?\d{1,4}?[-.\s]?\(?\d{1,4}?\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9}$/i', 'client');

        $mform->addRule('coordinator_email4', 'Enter valid email', 'email', null, 'client');
        $mform->addRule('coordinator_email4', 'Enter valid email', 'maxlength', 100, 'client');
        $mform->addRule('coordinator_contact4', 'Phone number should have 10 digits', 'minlength', 10, 'client');
        $mform->addRule('coordinator_contact4', 'Invalid phone number format', 'regex', '/^\+?\d{1,4}?[-.\s]?\(?\d{1,4}?\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9}$/i', 'client');

        $this->add_action_buttons(true, get_string('savechanges', 'local_school'));
    }

    private function add_coordinator_names(&$mform, $index)
    {

        $mform->addElement('header', "coordinator_name_header$index", get_string("coordinator_name$index", 'local_school'));
        $mform->setExpanded("coordinator_name_header$index", false);

        $mform->addElement('text', "coordinator_name$index", get_string("coordinator_name$index", 'local_school'));
        $mform->setType("coordinator_name$index", PARAM_TEXT);

        $mform->addElement('text', "coordinator_email$index", get_string("coordinator_email$index", 'local_school'));
        $mform->setType("coordinator_email$index", PARAM_EMAIL);

        $mform->addElement('text', "coordinator_contact$index", get_string("coordinator_contact$index", 'local_school'));
        $mform->setType("coordinator_contact$index", PARAM_TEXT);
    }
}
