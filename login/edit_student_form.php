<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
$PAGE->requires->css(new moodle_url("$CFG->wwwroot/login/style/customupdate.css"));
$PAGE->requires->js(new moodle_url("$CFG->wwwroot/login/style/main.js"));

class edit_student_form extends moodleform {
    protected $userid;

    public function __construct($userid) {
        $this->userid = $userid;
        parent::__construct();
    }

    public function definition() {
        global $DB;

        $mform = $this->_form;

        // Get existing user data
        $user = $DB->get_record('user', array('id' => $this->userid), '*', MUST_EXIST);
        $student = $DB->get_record('student', array('userid' => $this->userid), '*', MUST_EXIST);


     
        // Add First Name field
        $mform->addElement('text', 'firstname', get_string('firstname', 'local_students'));
        $mform->setType('firstname', PARAM_TEXT);
        $mform->setDefault('firstname', $user->firstname);
        $mform->addRule('firstname', get_string('required', 'local_students'), 'required', null, 'client');

        // Add Last Name field
        $mform->addElement('text', 'lastname', get_string('lastname', 'local_students'));
        $mform->setType('lastname', PARAM_TEXT);
        $mform->setDefault('lastname', $user->lastname);
        $mform->addRule('lastname', get_string('required', 'local_students'), 'required', null, 'client');

        // Add Email field
        $mform->addElement('text', 'email', get_string('email', 'local_students'));
        $mform->setType('email', PARAM_EMAIL);
        $mform->setDefault('email', $user->email);

        // Add Mobile Number field
        $mform->addElement('text', 'mobile_number', get_string('mobilenumber', 'local_students'));
        $mform->setType('mobile_number', PARAM_TEXT);
        $mform->setDefault('mobile_number',$student->contact_number);
        $mform->addRule('mobile_number', get_string('mobilenumbererror', 'local_students'), 'regex', '/^\d{10}$/', 'client');

        // Add School dropdown
        $school_options = $DB->get_records_sql_menu(
            "SELECT cc.id, cc.name
             FROM {schoolassign} sa
             JOIN {course_categories} cc ON sa.schoolid = cc.id"
        );
        $school_options = array(0 => get_string('pleaseselectschool', 'local_students')) + $school_options;
        $mform->addElement('select', 'school', get_string('school', 'local_students'), $school_options);
        $mform->setDefault('school', $student->schoolid);

        // Add Grade dropdown
        $grade_options = [];
        if ($student->schoolid) {
            $grade_options = $DB->get_records_sql_menu(
                "SELECT cc.id, cc.name
                 FROM {course_categories} cc
                 WHERE cc.parent = ?", array($student->schoolid)
            );
        }
        $grade_options = array(0 => get_string('pleaseselectgrade', 'local_students')) + $grade_options;
        $mform->addElement('select', 'grade', get_string('grade', 'local_students'), $grade_options);
        $mform->setDefault('grade', $student->gradeid);

        // Add Course dropdown
        $course_options = [];
        if ($student->gradeid) {
            $course_options = $DB->get_records_sql_menu(
                "SELECT c.id, c.fullname
                 FROM {course} c
                 WHERE c.category = ?", array($student->gradeid)
            );
        }
        $course_options = array(0 => get_string('pleaseselectcourse', 'local_students')) + $course_options;
        $mform->addElement('select', 'course', get_string('course', 'local_students'), $course_options);
        $mform->setDefault('course', $student->courseid);

        // Add Section field
        $mform->addElement('text', 'section', get_string('section', 'local_students'));
        $mform->setDefault('section', $student->section);

        // Add Submit button
        $this->add_action_buttons(true, get_string('update', 'local_students'));
    }

    function validation($data, $files) {
        $errors = array();
        global $DB;

        // Validate Email
        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = get_string('emailerror', 'local_students');
        } else if ($DB->record_exists_select('user', "email = ? AND id != ?", array($data['email'], $this->userid))) {
            $errors['email'] = get_string('emailexists', 'local_students');
        }

        // Validate Mobile Number (Exactly 10 digits)
        if (!preg_match('/^\d{10}$/', $data['mobile_number'])) {
            $errors['mobile_number'] = get_string('mobilenumbererror', 'local_students');
        }

        if ($data['school'] == 0) {
            $errors['school'] = get_string('schoolerror', 'local_students');
        }

        // Validate Grade selection
        if ($data['grade'] == 0) {
            $errors['grade'] = get_string('gradeerror', 'local_students');
        }

        if ($data['course'] == 0) {
            $errors['course'] = get_string('courseerror', 'local_students');
        }

        return $errors;
    }
}
