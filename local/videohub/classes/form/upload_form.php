<?php
namespace local_videohub\form;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class upload_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'title', get_string('title', 'local_videohub'), ['size'=>64]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', get_string('required'), 'required', null, 'client');

        $mform->addElement('editor', 'description', get_string('description','local_videohub'));
        $mform->setType('description', PARAM_RAW);

        $accepted = get_config('local_videohub', 'allowedmimetypes');
        $accepted = $accepted ? array_map('trim', explode(',', $accepted)) : ['video'];
        $mform->addElement('filepicker', 'videofile', get_string('videofile','local_videohub'), null, [
            'maxbytes' => get_max_upload_file_size(),
            'accepted_types' => $accepted,
        ]);
        $mform->addRule('videofile', get_string('required'), 'required', null, 'client');

        $mform->addElement('select', 'visibility', get_string('visibility','local_videohub'), [
            0 => get_string('visibility:private','local_videohub'),
            1 => get_string('visibility:class','local_videohub'),
            2 => get_string('visibility:site','local_videohub'),
        ]);
        $mform->setDefault('visibility', 1);
        $mform->getElement('visibility')->setHiddenLabel(true);
        $mform->getElement('visibility')->updateAttributes(['style' => 'display:none;']);

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}
