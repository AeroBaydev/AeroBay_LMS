<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
require_once("$CFG->dirroot/local/assessmentcard/lib.php");
 $PAGE->requires->js(new moodle_url("$CFG->wwwroot/local/assessmentcard/style/main.js"));
class card_form extends moodleform
{
    private $assessmentcardid;

    public function __construct($actionurl, $assessmentcardid = null)
    {
        $this->assessmentcardid = $assessmentcardid;
        parent::__construct($actionurl);
    }

    public function definition()
    {
        global $DB;

        $mform = $this->_form;

        // Hidden assessmentcard ID
        $mform->addElement('hidden', 'assessmentcardid');
        $mform->setType('assessmentcardid', PARAM_INT);
        $mform->setDefault('assessmentcardid', $this->assessmentcardid);

        // Heading
        // $heading_text = $this->assessmentcardid ? "Edit assessmentcard" : "Add assessmentcard";
        // $heading = html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-new-school'));
        // $mform->addElement('html', $heading);

        // assessmentcard text field
        $mform->addElement('textarea', 'name', get_string('name', 'local_assessmentcard'), 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // File upload field
        $mform->addElement('filepicker', 'badgefile', get_string('uploadbadge', 'local_assessmentcard'), null, 
            array('accepted_types' => array('.png', '.jpg', '.jpeg')));
        $mform->addRule('badgefile', null, 'required', null, 'client');

        // Create two number input fields
        $rang1 = $mform->createElement('text', 'rang1', '', array('type' => 'number', 'size' => 5));
        $rang2 = $mform->createElement('text', 'rang2', '', array('type' => 'number', 'size' => 5));

        // Group the inputs side by side
        $mform->addElement('group', 'completion_group', get_string('completion', 'local_assessmentcard'), 
            array($rang1, $rang2), ' - ', false);

        // Add validation rules
        $mform->addRule('rang1', get_string('required'), 'required', null, 'client');
        $mform->addRule('rang2', get_string('required'), 'required', null, 'client');

        // Set the type for validation
        $mform->setType('rang1', PARAM_INT);
        $mform->setType('rang2', PARAM_INT);

        // Hidden field for time created
        $mform->addElement('hidden', 'timecreated', time());
        $mform->setType('timecreated', PARAM_INT);

        if ($this->assessmentcardid) {
            $this->add_action_buttons(true, 'update');
        }
        else{
        $this->add_action_buttons(true, get_string('savechanges', 'local_assessmentcard'));
    }
        // Load existing data if editing
        if ($this->assessmentcardid) {
           
            $assessmentcard = $DB->get_record('assessmentcard', array('id' => $this->assessmentcardid));
            if ($assessmentcard) {
                $this->set_data($assessmentcard);
            }
        }
    }

    public function validation($data, $files)
    {
        global $DB;
        $errors = parent::validation($data, $files);

        // Ensure rang1 is not greater than rang2
        if ($data['rang1'] >= $data['rang2']) {
            $errors['completion_group'] = get_string('assessmentcard', 'local_assessmentcard');
        }

        return $errors;
    }
}