<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class edit_regionalpoc_form extends moodleform {

    public function definition() {
        $mform = $this->_form; // Don't forget the underscore!

        // Add elements to your form.
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'userid', $this->_customdata['userid']);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 'usertype', 'usertype');
        $mform->setType('usertype', PARAM_TEXT);

        $mform->addElement('text', 'username', get_string('username', 'local_regionalpoc'));
        $mform->setType('username', PARAM_TEXT);
        $mform->addRule('username', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'firstname', get_string('firstname', 'local_regionalpoc'));
        $mform->setType('firstname', PARAM_TEXT);
        $mform->addRule('firstname', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'lastname', get_string('lastname', 'local_regionalpoc'));
        $mform->setType('lastname', PARAM_TEXT);
        $mform->addRule('lastname', get_string('required'), 'required', null, 'client');
        

        $mform->addElement('passwordunmask', 'password', get_string('password', 'local_regionalpoc'));
        $mform->setType('password', PARAM_TEXT);

        $mform->addElement('date_selector', 'dob', get_string('dob', 'local_regionalpoc'));
        $mform->setType('dob', PARAM_INT);
        
        // Add true/false checkbox element below the date selector.
        $mform->addElement('advcheckbox', 'confirm',"", "Enable");
        $mform->setType('confirm', PARAM_BOOL);
        $mform->setDefault('confirm', 1);
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

        $mform->addElement('text', 'blood_group', get_string('bloodgroup', 'local_regionalpoc'));
        $mform->setType('blood_group', PARAM_TEXT);

        $mform->addElement('text', 'email', get_string('email', 'local_regionalpoc'));
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', null, 'required', null, 'client');
        $mform->addRule('email', 'Enter valid email', 'email', null, 'client');
        $mform->addRule('email', 'Enter valid email', 'maxlength', 100, 'client');

        $mform->addElement('text', 'contact_number', get_string('contactnumber', 'local_regionalpoc'));
        $mform->setType('contact_number', PARAM_TEXT);
        $mform->addRule('contact_number', null, 'required', null, 'client');
        $mform->addRule('contact_number', 'Phone number should have 10 digits', 'minlength', 10, 'client');
        $mform->addRule('contact_number', 'Invalid phone number format', 'regex', '/^\+?\d{1,4}?[-.\s]?\(?\d{1,4}?\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9}$/i', 'client');

        $mform->addElement('text', 'permanent_address', get_string('permanentaddress', 'local_regionalpoc'));
        $mform->setType('permanent_address', PARAM_TEXT);

        $mform->addElement('text', 'current_address', get_string('currentaddress', 'local_regionalpoc'));
        $mform->setType('current_address', PARAM_TEXT);

        $mform->addElement('text', 'alternative_address', get_string('alternativeaddress', 'local_regionalpoc'));
        $mform->setType('alternative_address', PARAM_TEXT);

        $mform->addElement('text', 'experience', get_string('experience', 'local_regionalpoc'));
        $mform->setType('experience', PARAM_TEXT);
        $mform->addRule('experience', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'ctc', get_string('ctc', 'local_regionalpoc'));
        $mform->setType('ctc', PARAM_TEXT);

        $mform->addElement('date_selector', 'date_of_joining', get_string('dateofjoining', 'local_regionalpoc'));
        $mform->setType('date_of_joining', PARAM_INT);
        // $mform->addRule('date_of_joining', get_string('required'), 'required', null, 'client');
        $mform->addElement('advcheckbox', 'confirmdoj',"", "Enable");
        $mform->setType('confirmdoj', PARAM_BOOL);
        $mform->setDefault('confirmdoj', 1);
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

        $mform->addElement('text', 'designation', get_string('designation', 'local_regionalpoc'));
        $mform->setType('designation', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('savechanges', 'local_regionalpoc'));
    }

    function validation($data, $files) {
        $errors = [];

       
        if (empty(trim($data['firstname']))) {
            $errors['firstname'] = get_string('required', 'local_regionalpoc');
        }
        if (empty(trim($data['lastname']))) {
            $errors['lastname'] = get_string('required', 'local_regionalpoc');
        }
        if (empty(trim($data['dob']))) {
            $errors['dob'] = get_string('required', 'local_regionalpoc');
        }
        if (empty(trim($data['blood_group']))) {
            $errors['blood_group'] = get_string('required', 'local_regionalpoc');
        }
        if (empty(trim($data['email']))) {
            $errors['email'] = get_string('required', 'local_regionalpoc');
        }
        if (empty(trim($data['contact_number']))) {
            $errors['contact_number'] = get_string('required', 'local_regionalpoc');
        }
        
        if (empty(trim($data['experience']))) {
            $errors['experience'] = get_string('required', 'local_regionalpoc');
        }
       
        if (empty(trim($data['date_of_joining']))) {
            $errors['date_of_joining'] = get_string('required', 'local_regionalpoc');
        }
        if (empty(trim($data['designation']))) {
            $errors['designation'] = get_string('required', 'local_regionalpoc');
        }


        if (!empty($data['password']) && !check_password_policy($data['password'], $errmsg, $tempuser)) {
            $errors['password'] = $errmsg;
        }
        global $DB,$CFG;
        $user = $DB->get_record('user', array('id' => $data['userid']));

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


        if ( (isset($data['email']) && $user->email !== $data['email'])) {
            if (!validate_email($data['email'])) {
                $errors['email'] = get_string('invalidemail');
            } 
                // If there are other user(s) that already have the same email, show an error.
                if ($DB->record_exists('user', ['email'=>$data['email']])) {
                    $errors['email'] = get_string('emailexists');
                }
            
        }



        return $errors;
    }
}

