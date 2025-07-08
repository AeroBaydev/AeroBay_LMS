<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
require_once("$CFG->dirroot/local/assessmentcard/lib.php");
 $PAGE->requires->js(new moodle_url("$CFG->wwwroot/local/assessmentcard/style/main.js"));
class subcard_form extends moodleform
{
    private $assessmentcardid;

    public function __construct($actionurl, $assessmentcardid = null,$parent=null)
    {
        $this->assessmentcardid = $assessmentcardid;
        $this->parent = $parent;
        parent::__construct($actionurl);
    }

    public function definition()
    {
        global $DB;

        $mform = $this->_form;

        $mform->addElement('hidden', 'parentid', $this->parent);
        

        // Hidden assessmentcard ID
        $mform->addElement('hidden', 'assessmentcardid');
        $mform->setType('assessmentcardid', PARAM_INT);

        // Heading
        $heading_text = $this->assessmentcardid ? "Edit assessmentcard" : "Add assessmentcard";
        $heading = html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-new-school'));
        $mform->addElement('html', $heading);

        // assessmentcard text field
        $mform->addElement('textarea', 'name', get_string('name', 'local_assessmentcard'), 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // File upload field
        $mform->addElement('filepicker', 'badgefile', get_string('uploadbadge', 'local_assessmentcard'), null, 
            array('accepted_types' => array('.png', '.jpg', '.jpeg')));
        $mform->addRule('badgefile', null, 'required', null, 'client');


        // Hidden field for time created
        $mform->addElement('hidden', 'timecreated', time());
        $mform->setType('timecreated', PARAM_INT);

        // Submit button
        $this->add_action_buttons(true, get_string('savechanges', 'local_assessmentcard'));

        // Load existing data if editing
        // if ($this->assessmentcardid) {
        //     $assessmentcard = $DB->get_record('assessmentcard', array('parentid' => $this->parent));
        //     if ($assessmentcard) {
        //         $this->set_data($assessmentcard);
        //     }
        // }
    }

    public function validation($data, $files)
    {
        global $DB;
        $errors = parent::validation($data, $files);

        // Ensure rang1 is not greater than rang2
        // if ($data['rang1'] >= $data['rang2']) {
        //     $errors['completion_group'] = get_string('assessmentcard', 'local_assessmentcard');
        // }

        return $errors;
    }
}