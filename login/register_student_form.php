<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
$PAGE->requires->css(new moodle_url("$CFG->wwwroot/login/style/custom.css"));
$PAGE->requires->js(new moodle_url("$CFG->wwwroot/login/style/main.js"));
class register_student_form extends moodleform {
    public function definition() {
        global $DB;

        $mform = $this->_form; // Don't forget the underscore!

        // Add First Name field
        $mform->addElement('text', 'firstname', get_string('firstname', 'local_students'));
        $mform->setType('firstname', PARAM_TEXT);
        $mform->addRule('firstname', get_string('required', 'local_students'), 'required', null, 'client');

        // Add Last Name field
        $mform->addElement('text', 'lastname', get_string('lastname', 'local_students'));
        $mform->setType('lastname', PARAM_TEXT);
        $mform->addRule('lastname', get_string('required', 'local_students'), 'required', null, 'client');

        // Add Email field
        $mform->addElement('text', 'email', get_string('email', 'local_students'));
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', get_string('required', 'local_students'), 'required', null, 'client');
        $mform->addRule('email', get_string('emailerror', 'local_students'), 'email', null, 'client');

        // Add Mobile Number field
        $mform->addElement('text', 'mobile_number', get_string('mobilenumber', 'local_students'));
        $mform->setType('mobile_number', PARAM_TEXT);
        $mform->addRule('mobile_number', get_string('required', 'local_students'), 'required', null, 'client');
        $mform->addRule('mobile_number', get_string('mobilenumbererror', 'local_students'), 'regex', '/^\d{10}$/', 'client');

        
        // Add School dropdown
        $school_options=[];
        $school_options = $DB->get_records_sql_menu(
            "SELECT cc.id, cc.name
             FROM {schoolassign} sa
             JOIN {course_categories} cc ON sa.schoolid = cc.id"
        );
        $school_options = array(0 => get_string('pleaseselectschool', 'local_students')) + $school_options;

        $mform->addElement('select', 'school', get_string('school', 'local_students'), $school_options);
        $mform->setType('school', PARAM_INT);
        //   echo $_POST['school'];
        
        $grade_options=[];
        if($_POST['school']){
            // die;
           $schoolid=$_POST['school'];
           $grade_options = $DB->get_records_sql_menu(
            "SELECT cc.id, cc.name
             from {course_categories} cc where cc.parent= $schoolid"
        );
    }
        // Add Grade dropdown
        $grade_options = array(0 => get_string('pleaseselectgrade', 'local_students')) + $grade_options;
        $mform->addElement('select', 'grade', get_string('grade', 'local_students'), $grade_options);
        $mform->setType('grade', PARAM_TEXT);

        // Add Course dropdown
        // $courses = $DB->get_records('course'); // Adjust table name as needed
        // $course_options = array();
        // foreach ($courses as $course) {
        //     $course_options[$course->id] = $course->fullname; // Adjust fields as needed
        // }
        $course_options=[];
        if($_POST['grade']){
            $gradid=$_POST['grade'];
            $course_options = $DB->get_records_sql_menu(
                "SELECT cc.id, cc.fullname
                 from {course} cc where cc.category=$gradid");
        }

        // $course_options = array(0 => get_string('pleaseselectcourse', 'local_students')) + $course_options;
        // $mform->addElement('select', 'course', get_string('course', 'local_students'), $course_options);
        // $mform->setType('course', PARAM_INT);


       $mform->addElement('text', 'section', get_string('section', 'local_students'));
        // Add Submit button
        $this->add_action_buttons(true, get_string('register', 'local_students'));
    }

    function validation($data, $files) {
        $errors = array();
        global $DB;

        // Validate Email
        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = get_string('emailerror', 'local_students');
        } else if ($DB->record_exists('user', array('email' => $data['email']))) {
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
        // if ($data['course'] == 0) {
        //     $errors['course'] = get_string('courseerror', 'local_students');
        // }

        return $errors;
    }
}
