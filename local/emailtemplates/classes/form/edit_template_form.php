<?php
namespace local_emailtemplates\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class edit_template_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id', get_string('templatename', 'local_emailtemplates'));
        $mform->addElement('text', 'name', get_string('templatename', 'local_emailtemplates'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('text', 'subject', get_string('subject', 'local_emailtemplates'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'client');

        $mform->addElement('editor', 'body', get_string('body', 'local_emailtemplates'));
        $mform->setType('body', PARAM_RAW);
        $mform->addRule('body', null, 'required', null, 'client');

        $this->add_action_buttons();
    }

    // Custom validation method
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        // Check if the template name already exists, but only for new templates or if the name has changed
        $template = $DB->get_record('local_emailtemplates', ['name' => $data['name']]);
        
        if ($template && (!isset($data['id']) || $template->id != $data['id'])) {
            $errors['name'] = get_string('templatenameexists', 'local_emailtemplates');
        }

        return $errors;
    }
}
