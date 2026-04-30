<?php
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
$PAGE->requires->js(new moodle_url("$CFG->wwwroot/local/poc/amd/src/numeric_validation.js"));
class poc_form extends moodleform
{
    public function definition()
    {
        global $USER, $CFG, $COURSE;
        $mform = $this->_form; // Don't forget the underscore!
        $mform->addElement('hidden', 'pocid', $this->_customdata['pocid']);
        $mform->setType('pocid', PARAM_INT);

        $heading_text = "Add New POC";
        $heading = html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-new-poc'));
        $mform->addElement('html', $heading);

        // Add elements to your form.
        $mform->addElement('text', 'username', get_string('username', 'local_poc'));
        $mform->setType('username', PARAM_TEXT);
        $mform->addRule('username', get_string('required'), 'required', null, 'client');

        $js = <<<JS
    document.addEventListener('DOMContentLoaded', function() {
        var usernameField = document.getElementById('id_username');
        var errorMessage = document.getElementById('id_error_username');
        var form = document.querySelector('form'); // Assuming there's only one form or you can use an ID to select the specific form
    
        usernameField.addEventListener('input', function(e) {
            if (/[A-Z]/.test(usernameField.value)) {
                errorMessage.textContent = 'Please enter lower case characters only';
                errorMessage.style.display = 'block';
            } else {
                errorMessage.style.display = 'none';
            }
        });
    
        form.addEventListener('submit', function(e) {
            if (/[A-Z]/.test(usernameField.value)) {
                e.preventDefault(); // Prevent form submission
                errorMessage.textContent = 'Please enter lower case characters only';
                errorMessage.style.display = 'block';
            }
        });
    });
    JS;

        $mform->addElement('html', '<script type="text/javascript">' . $js . '</script>');


        // Add other form elements as needed
        $mform->addElement('text', 'firstname', get_string('firstname', 'local_poc'));
        $mform->setType('firstname', PARAM_TEXT);
        $mform->addRule('firstname', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'lastname', get_string('lastname', 'local_poc'));
        $mform->setType('lastname', PARAM_TEXT);
        $mform->addRule('lastname', get_string('required'), 'required', null, 'client');
        
        if (!empty($CFG->passwordpolicy)) {
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }
        $mform->addElement('passwordunmask', 'password', get_string('password', 'local_poc'));
        $mform->setType('password', PARAM_TEXT);
        $mform->addRule('password', get_string('required'), 'required', null, 'client');

        // $mform->addElement('date_selector', 'dob', get_string('dob', 'local_poc'));
        // $mform->setType('dob', PARAM_INT);
        $mform->addElement('date_selector', 'dob', get_string('dob', 'local_trainer'));
        $mform->setType('dob', PARAM_INT);
        $mform->addRule('dob', null, 'required', null, 'client');

        // Add true/false checkbox element below the date selector.
        $mform->addElement('advcheckbox', 'confirm',"", "Enable");
        $mform->setType('confirm', PARAM_BOOL);
        // Add JavaScript to handle enabling/disabling date selector.
        $mform->addElement('html', '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                var checkbox = document.getElementById("id_confirm");
                var dateSelector = document.getElementById("id_dob");

                function toggleDateSelector() {
                    dateSelector.disabled = !checkbox.checked;
                }

                checkbox.addEventListener("change", toggleDateSelector);

                // Initial check
                toggleDateSelector();
            });
        </script>');




        $mform->addElement('text', 'blood_group', get_string('bloodgroup', 'local_poc'));
        $mform->setType('blood_group', PARAM_TEXT);

        $mform->addElement('text', 'email', get_string('email', 'local_poc'));
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', null, 'required', null, 'client');
        $mform->addRule('email', 'Enter valid email', 'email', null, 'client');
        $mform->addRule('email', 'Enter valid email', 'maxlength', 100, 'client');

        $mform->addElement('text', 'contact_number', get_string('contactnumber', 'local_trainer'));
        $mform->setType('contact_number', PARAM_INT);
        $mform->addRule('contact_number', null, 'required', null, 'client');
        $mform->addRule('contact_number', 'Phone number should have 10 digits', 'minlength', 10, 'maxlength', 10, 'client');
        $mform->addRule('contact_number', 'Invalid phone number format', 'regex', '/^\+?\d{1,4}?[-.\s]?\(?\d{1,4}?\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9}$/i', 'client');

        $mform->addElement('text', 'permanent_address', get_string('permanentaddress', 'local_poc'));
        $mform->setType('permanent_address', PARAM_TEXT);

        $mform->addElement('text', 'current_address', get_string('currentaddress', 'local_poc'));
        $mform->setType('current_address', PARAM_TEXT);

        $mform->addElement('text', 'alternative_address', get_string('alternativeaddress', 'local_poc'));
        $mform->setType('alternative_address', PARAM_TEXT);

        $mform->addElement('text', 'experience', get_string('experience', 'local_poc'));
        $mform->setType('experience', PARAM_TEXT);
        $mform->addRule('experience', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'ctc', get_string('ctc', 'local_poc'));
        $mform->setType('ctc', PARAM_INT);

        $mform->addElement('date_selector', 'date_of_joining', get_string('dateofjoining', 'local_poc'));
        $mform->setType('date_of_joining', PARAM_INT);
        $mform->addRule('date_of_joining', get_string('required'), 'required', null, 'client');
        $mform->addElement('advcheckbox', 'confirmdoj',"", "Enable");
        $mform->setType('confirmdoj', PARAM_BOOL);
        // Add JavaScript to handle enabling/disabling date selector.
        $mform->addElement('html', '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                var checkbox = document.getElementById("id_confirmdoj");
                var dateSelector = document.getElementById("id_date_of_joining");

                function toggleDateSelector() {
                    dateSelector.disabled = !checkbox.checked;
                }

                checkbox.addEventListener("change", toggleDateSelector);

                // Initial check
                toggleDateSelector();
            });
        </script>');


        

        $mform->addElement('text', 'designation', get_string('designation', 'local_poc'), ['value' => 'Operations and Execution', 'readonly' => 'true']);
        $mform->setType('designation', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('savechanges', 'local_poc'));
    }

    function validation($data, $files)
    {
        global $DB;

        $errors = [];

        // Check for uppercase letters in the username
        if (preg_match('/[A-Z]/', $data['username'])) {
            $errors['username'] = 'Please enter small letters only';
        }

        if ($DB->record_exists('user', ['username' => $data['username']])) {
            $errors['username'] = get_string('userexists', 'local_poc');
        }
        if (empty(trim($data['firstname']))) {
            $errors['firstname'] = get_string('required');
        }
        if (empty(trim($data['lastname']))) {
            $errors['lastname'] = get_string('required');
        }
        if (empty(trim($data['dob']))) {
            $errors['dob'] = get_string('required');
        }
        if (empty(trim($data['email']))) {
            $errors['email'] = get_string('required');
        }
        if (empty(trim($data['contact_number']))) {
            $errors['contact_number'] = get_string('required');
        }
        if (empty(trim($data['experience']))) {
            $errors['experience'] = get_string('required');
        }
        if (empty(trim($data['date_of_joining']))) {
            $errors['date_of_joining'] = get_string('required');
        }
        if (empty(trim($data['designation']))) {
            $errors['designation'] = get_string('required');
        }
        if (empty(trim($data['password']))) {
            $errors['password'] = get_string('required');
        }

        if (!empty($data['password'])) {
            $errmsg="";

            if (!check_password_policy($data['password'], $errmsg, $tempuser)) {
                $errors['password'] = $errmsg;
            }
        }

        return $errors;
    }
}
