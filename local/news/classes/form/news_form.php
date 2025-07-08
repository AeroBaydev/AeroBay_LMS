<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
require_once("$CFG->dirroot/local/news/lib.php");
// $PAGE->requires->js(new moodle_url("$CFG->wwwroot/local/news/style/main.js"));
$PAGE->requires->css('/local/news/style/styles.css');

class news_form extends moodleform
{
    public function definition()
    {
        global $DB;

        $mform = $this->_form;

        // Hidden news ID
        $mform->addElement('hidden', 'newsid');
        $mform->setType('newsid', PARAM_INT);

        // Heading
        $heading_text = "Add News";
        $heading = html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-new-school'));
        $mform->addElement('html', $heading);

        // News text field
        $mform->addElement('textarea', 'news', get_string('news', 'local_news'), 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('news', PARAM_TEXT);
        $mform->addRule('news', get_string('required'), 'required', null, 'client');


        $school_options=[];
        $school_options = $DB->get_records_sql_menu(
            "SELECT cc.id, cc.name
             FROM {schoolassign} sa
             JOIN {course_categories} cc ON sa.schoolid = cc.id"
        );
        $school_options = array(0 => get_string('pleaseselectschool', 'local_students')) + $school_options;

        $mform->addElement('select', 'school', get_string('school', 'local_students'), $school_options, array('multiple' => 'multiple','size' => 10,));
        $mform->setType('school', PARAM_INT);
        $mform->addRule('school', get_string('required'), 'required', null, 'client');

        //   echo $_POST['school'];
        
         $grade_options=[];
        // if($_POST['school']){
        //     // die;
        //    $schoolid=$_POST['school'];
        //    $grade_options = $DB->get_records_sql_menu(
        //     "SELECT cc.id, cc.name
        //      from {course_categories} cc where cc.parent= $schoolid"
        // );
        //  }
        // Add Grade dropdown
        // $grade_options = array(0 => get_string('pleaseselectgrade', 'local_news'));
        $grade_options = array(0 => get_string('pleaseselectgrade', 'local_news'));

            for ($i = 1; $i <= 12; $i++) {
                $grade_options[$i] = 'Grade ' . $i;
            }

            // Add multi-select for grades
            $mform->addElement('select', 'grade', get_string('grade', 'local_news'), $grade_options, array(
                'multiple' => 'multiple',
                'size' => 10
            ));

            // Set the grade field type to array of integers
            $mform->setType('grade', PARAM_INT);

        

        // $mform->addRule('grade', get_string('required'), 'required', null, 'client');


        // Hidden field for time created
        $mform->addElement('hidden', 'timecreated', time());
        $mform->setType('timecreated', PARAM_INT);

        // Submit button
        $this->add_action_buttons(true, get_string('savechanges', 'local_news'));
    }

    public function validation($data, $files)
    {
        global $DB;
        $errors = parent::validation($data, $files);

        // Validate if the school exists
        // if (!$DB->record_exists('school', array('id' => $data['schoolid']))) {
        //     $errors['schoolid'] = get_string('invalidschool', 'local_news');
        // }

        // Validate if the grade exists
        // if (!$DB->record_exists('grade', array('id' => $data['gradeid']))) {
        //     $errors['gradeid'] = get_string('invalidgrade', 'local_news');
        // }

        return $errors;
    }
}
