<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
$PAGE->requires->css('/local/news/style/styles.css');

// $PAGE->requires->js(new moodle_url("$CFG->wwwroot/local/news/style/main.js"));

class edit_news_form extends moodleform
{
    public function __construct($newsid = null,$schoolid=null)
    {
        $this->newsid = $newsid;
        $this->schoolid = $schoolid;
        parent::__construct(null, ['newsid' => $newsid]);
    }

    public function definition()
    {
        global $DB;

        $mform = $this->_form;
        $newsid = $this->_customdata['newsid'] ?? null;

        // Hidden ID field
        $mform->addElement('hidden', 'id', $this->newsid);
        $mform->setType('id', PARAM_INT);

        // Heading
        // $heading_text = "Add News";
        // $heading = html_writer::tag('h2', $heading_text, ['class' => 'custom-heading add-new-school']);
        // $mform->addElement('html', $heading);

        // News textarea
        $mform->addElement('textarea', 'news', get_string('news', 'local_news'), 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('news', PARAM_TEXT);
        $mform->addRule('news', get_string('required'), 'required', null, 'client');

        // Fetch schools
        $school_options=[];
        $school_options = array(0 => get_string('pleaseselectschool', 'local_students'));
        $school_options1 = $DB->get_records_sql_menu(
            "SELECT cc.id, cc.name
             FROM {schoolassign} sa
             JOIN {course_categories} cc ON sa.schoolid = cc.id"
        );
        $school_options = array(0 => get_string('pleaseselectschool', 'local_students'))+ $school_options1;

        $mform->addElement('select', 'school', get_string('school', 'local_students'), $school_options, array('multiple' => 'multiple', 'size' => 10));
        $mform->setType('school', PARAM_INT);
        $mform->addRule('school', get_string('required'), 'required', null, 'client');

        // Fetch grades if a school is selected
        $grade_options=[];
        $grade_options = array(0 => get_string('pleaseselectgrade', 'local_news'));

            for ($i = 1; $i <= 12; $i++) {
                $grade_options[$i] = 'Grade ' . $i;
            }
            // Add multi-select for grades
            $mform->addElement('select', 'grade', get_string('grade', 'local_news'), $grade_options, array('multiple' => 'multiple', 'size' => 10));

            // Set the grade field type to array 

        // Hidden field for time created
        $mform->addElement('hidden', 'timecreated', time());
        $mform->setType('timecreated', PARAM_INT);

        // Submit button
        $this->add_action_buttons(true, get_string('updatechanges', 'local_news'));
    }
}
