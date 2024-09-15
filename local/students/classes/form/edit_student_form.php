<?php
defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");
$PAGE->requires->js(new moodle_url("$CFG->wwwroot/local/students/amd/src/numeric_validation.js"));
$PAGE->requires->js(new moodle_url("$CFG->wwwroot/local/students/amd/form.js"));
class edit_student_form extends moodleform
{

    public function definition()
    {
        global $DB ,$USER;

        if (isset($_SESSION['userIdPoc'])) {
            $userid=$_SESSION['userIdPoc'];
          
       }
       else{
      $userid=  $USER->id;
       }

        $customdata = $this->_customdata;
        $id = $customdata['id'];
        $schoolid = $customdata['schoolid'];
        $gradeid = $customdata['gradeid'];
        $courseid = $customdata['courseid'];

        $school = $DB->get_records_sql_menu("SELECT cc.id, cc.name FROM {schoolassign} sa JOIN {course_categories} cc ON sa.schoolid = cc.id 
        where sa.userid=$userid");
        $grade = $DB->get_records_sql_menu("SELECT cc.id,cc.name FROM {course_categories} cc WHERE cc.parent = $schoolid");
       // $course = $DB->get_records_sql_menu("SELECT c.id,c.fullname FROM {course} c WHERE c.category = $gradeid");
     //   $group = $DB->get_records_sql_menu("SELECT g.id,g.name FROM {groups} g WHERE g.courseid = $courseid");

        $mform = $this->_form;

        $mform->addElement('hidden', 'id', $id);

        // Adding custom heading
        // $heading_text = "Edit Student Detail";
        // $heading = html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-student d-flex justify-content-center mb-5'));
        // $mform->addElement('html', $heading);
        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'custom-form-class')));

        // Adding form elements
        $mform->addElement('hidden', 'studentid', $this->_customdata['studentid']);
        $mform->setType('studentid', PARAM_INT);

        $mform->addElement('text', 'username', get_string('username', 'local_students'));
        $mform->setType('username', PARAM_TEXT);
        $mform->addRule('username', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'firstname', get_string('firstname', 'local_students'));
        $mform->setType('firstname', PARAM_TEXT);
        $mform->addRule('firstname', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'lastname', get_string('lastname', 'local_students'));
        $mform->setType('lastname', PARAM_TEXT);
        $mform->addRule('lastname', get_string('required'), 'required', null, 'client');

        // $mform->addElement('passwordunmask', 'password', get_string('password', 'local_students'));
        // $mform->setType('password', PARAM_RAW);
        // $mform->addRule('password', get_string('required'), 'required', null, 'client');

        $mform->addElement('date_selector', 'dob', get_string('dob', 'local_students'), array('optional' => false));
        $mform->setType('dob', PARAM_INT);
        $mform->addRule('dob', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'parent', get_string('parent', 'local_students'));
        $mform->setType('parent', PARAM_TEXT);
        $mform->addRule('parent', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'email', get_string('email', 'local_students'));
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', get_string('required'), 'required', null, 'client');
        $mform->addRule('email', get_string('validemail'), 'email', null, 'client');
        $mform->addRule('email', get_string('maxchar', 'local_students', 100), 'maxlength', 100, 'client');

        $mform->addElement('select', 'schoolid', get_string('selectschool', 'local_students'), array('' => get_string('selectschool', 'local_students')) + $school);
        $mform->setType('schoolid', PARAM_INT);
        $mform->addRule('schoolid', get_string('required'), 'required', null, 'client');

        $mform->addElement('select', 'gradeid', get_string('selectgrade', 'local_students'), array('' => get_string('selectgrade', 'local_students')) + $grade);
        $mform->setType('gradeid', PARAM_INT);
        $mform->addRule('gradeid', get_string('required'), 'required', null, 'client');


        // $mform->addElement('select', 'courseid', get_string('selectcourse', 'local_students'), array('' => get_string('selectcourse', 'local_students')) + $course);
        // $mform->setType('courseid', PARAM_INT);
        // $mform->addRule('courseid', get_string('required'), 'required', null, 'client');

        // $mform->addElement('select', 'sectionid', get_string('selectsection', 'local_students'), array('' => get_string('selectsection', 'local_students')) + $group);
        // $mform->setType('sectionid', PARAM_INT);
        // $mform->addRule('sectionid', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'contact_number', get_string('contactnumber', 'local_students'));
        $mform->setType('contact_number', PARAM_TEXT);
        $mform->addRule('contact_number', get_string('required'), 'required', null, 'client');
        $mform->addRule('contact_number', get_string('phonenumberlength', 'local_students', 10), 'minlength', 10, 'client');
        $mform->addRule('contact_number', get_string('invalidphone', 'local_students'), 'regex', '/^\+?\d{1,4}?[-.\s]?\(?\d{1,4}?\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9}$/i', 'client');

        $mform->addElement('text', 'hobbies', get_string('hobbies', 'local_students'));
        $mform->setType('hobbies', PARAM_TEXT);
        $mform->addRule('hobbies', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'interest', get_string('interest', 'local_students'));
        $mform->setType('interest', PARAM_TEXT);
        $mform->addRule('interest', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'address', get_string('permanentaddress', 'local_students'));
        $mform->setType('address', PARAM_TEXT);

        // $mform->addElement('text', 'address', get_string('permanentaddress', 'local_students'));
        // $mform->setType('address', PARAM_TEXT);
        $mform->addElement('text', 'section', get_string('section', 'local_students'));
        $mform->addElement('html', html_writer::end_tag('div'));

        $mform->addElement('html', html_writer::start_tag('div', array('class' => 'd-flex justify-content-center button-group-center')));
        $this->add_action_buttons(true, get_string('save', 'local_students'));
        $mform->addElement('html', html_writer::end_tag('div'));
    }

    function validation($data, $files)
    {

        global $DB,$CFG;
        $user = $DB->get_record('user', array('id' => $this->_customdata['id']));

        $errors = parent::validation($data, $files);

        if ( (isset($data['email']) && $user->email !== $data['email'])) {
            if (!validate_email($data['email'])) {
                $errors['email'] = get_string('invalidemail');
            } else if (empty($CFG->allowaccountssameemail)) {
                // Make a case-insensitive query for the given email address.
                $select = $DB->sql_equal('email', ':email', false) . ' AND mnethostid = :mnethostid AND id <> :userid';
                $params = array(
                    'email' => $data['email'],
                    'mnethostid' => $CFG->mnet_localhost_id,
                    'userid' => $this->_customdata['id']
                );
                // If there are other user(s) that already have the same email, show an error.
                if ($DB->record_exists_select('user', $select, $params)) {
                    $errors['email'] = get_string('emailexists');
                }
            }
        }


        if (empty($data['username'])) {
            // Might be only whitespace.
            $errors['username'] = get_string('required');
        } else if ( $user->username !== $data['username']) {

            // Check new username does not exist.
            if ($DB->record_exists('user', array('username' => $data['username'], 'mnethostid' => $CFG->mnet_localhost_id))) {
                $errors['username'] = get_string('usernameexists');
            }
            // Check allowed characters.
            if ($data['username'] !== core_text::strtolower($data['username'])) {
                $errors['username'] = get_string('usernamelowercase');
            } else {
                if ($data['username']!== core_user::clean_field($data['username'], 'username')) {
                    $errors['username'] = get_string('invalidusername');
                }
            }
        }
        if ($data['password']) {
            $errmsg = '';
            $tempuser = new stdClass();
            $tempuser->password = $data['password'];
            if (!check_password_policy($data['password'], $errmsg, $tempuser)) {
                $errors['password'] = $errmsg;
            }
        }

        return $errors;
    }
}
