<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class assign_permission_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        // Add hidden user ID field (not shown in the form but used for processing)
        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        // Capabilities array
        $capabilities = [
            'course_management' => get_string('course_management', 'local_regionalpoc'),
            'student_management' => get_string('student_management', 'local_regionalpoc'),
            'activity_resource_management' => get_string('activity_resource_management', 'local_regionalpoc'),
            'trainer_management' => get_string('trainer_management', 'local_regionalpoc')
        ];

        // Checkboxes for capabilities
        $mform->addElement('checkboxes', 'capabilities', get_string('capabilities', 'local_regionalpoc'), $capabilities);
        $mform->addRule('capabilities', get_string('required'), 'required', null, 'client');

        // Select dropdown for actions
        $actions = [
            'assign' => get_string('assign', 'local_regionalpoc'),
            'remove' => get_string('remove', 'local_regionalpoc')
        ];
        $mform->addElement('button', 'action', get_string('action', 'local_regionalpoc'), $actions);
        $mform->addRule('action', get_string('required'), 'required', null, 'client');

        // Add submit and cancel buttons
        $this->add_action_buttons(false, get_string('savechanges'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate capabilities selection
        if (empty($data['capabilities'])) {
            $errors['capabilities'] = get_string('required');
        }

        return $errors;
    }
}
