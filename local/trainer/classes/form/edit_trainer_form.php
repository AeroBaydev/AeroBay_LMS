<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class edit_trainer_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER;

        $mform = $this->_form;
        $pocuserid = $this->_customdata['pocuserid'] ?? $USER->id;
        $schools = $DB->get_records_sql_menu(
            "SELECT cc.id, COALESCE(sc.school_name, cc.name) AS schoolname
               FROM {schoolassign} sa
               JOIN {course_categories} cc ON cc.id = sa.schoolid
          LEFT JOIN {school} sc ON sc.course_cat_id = cc.id
              WHERE sa.userid = :userid
           ORDER BY schoolname",
            ['userid' => $pocuserid]
        );

                if(is_siteadmin()){
                    $siteadminarray = array(
                        'type' => 'hidden',
                        'name' => 'custom_hidden_input',
                        'id' => 'siteadmin',
                        'value' => "true",
                        'class' => 'custom-hidden'
                    );
                } else {
                    $siteadminarray = array(
                        'type' => 'hidden',
                        'name' => 'custom_hidden_input',
                        'id' => 'siteadmin',
                        'value' => "false",
                        'class' => 'custom-hidden'
                    );
                }
                // $heading_text = "Edit Trainer Details";
                // $heading1 = html_writer::tag('input', '', $siteadminarray);
                // $heading = html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-trainer'));
                $heading = '';
                $heading1 = html_writer::tag('input', '', $siteadminarray);
                $mform->addElement('html', $heading);
                $mform->addElement('html', $heading1);
                
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);

        $usernameElement=$mform->addElement('text', 'username', get_string('username', 'local_trainer'));
        $mform->setType('username', PARAM_TEXT);
        $mform->addRule('username', get_string('required'), 'required', null, 'client');

        $firstnameElement=$mform->addElement('text', 'firstname', get_string('firstname', 'local_trainer'));
        $mform->setType('firstname', PARAM_TEXT);
        $mform->addRule('firstname', get_string('required'), 'required', null, 'client');

        $lastnameElement=$mform->addElement('text', 'lastname', get_string('lastname', 'local_trainer'));
        $mform->setType('lastname', PARAM_TEXT);
        $mform->addRule('lastname', get_string('required'), 'required', null, 'client');

        $mform->addElement('select', 'schoolid', get_string('assignedschool', 'local_trainer'), ['' => get_string('selectschool', 'local_trainer')] + $schools);
        $mform->setType('schoolid', PARAM_INT);
        $mform->addRule('schoolid', get_string('required'), 'required', null, 'client');

        $mform->addElement('html', '<div id="trainer-school-mapped-courses" class="form-group row fitem"><div class="col-md-3 col-form-label d-flex pb-0 pr-md-0"><label>' . get_string('mappedgradescourses', 'local_trainer') . '</label></div><div class="col-md-9 form-inline align-items-start felement" id="trainer-school-mapped-courses-content">' . get_string('selectschoolfirst', 'local_trainer') . '</div></div>');
        $ajaxurl = new moodle_url('/local/trainer/get_school_courses.php');
        $sesskey = sesskey();
        $selectschoolfirst = $this->js_string(get_string('selectschoolfirst', 'local_trainer'));
        $loading = $this->js_string(get_string('loading', 'local_trainer'));
        $nomappedcourses = $this->js_string(get_string('nomappedcourses', 'local_trainer'));
        $unabletoloadcourses = $this->js_string(get_string('unabletoloadcourses', 'local_trainer'));
        $js = <<<JS
        document.addEventListener('DOMContentLoaded', function() {
            var schoolField = document.getElementById('id_schoolid');
            var courseContainer = document.getElementById('trainer-school-mapped-courses-content');

            function loadMappedCourses() {
                if (!schoolField || !courseContainer) {
                    return;
                }

                var schoolid = schoolField.value;
                if (!schoolid) {
                    courseContainer.innerHTML = '$selectschoolfirst';
                    return;
                }

                courseContainer.innerHTML = '$loading';
                var formData = new FormData();
                formData.append('schoolid', schoolid);
                formData.append('sesskey', '$sesskey');

                fetch('$ajaxurl', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    courseContainer.innerHTML = data.html || '$nomappedcourses';
                })
                .catch(function() {
                    courseContainer.innerHTML = '$unabletoloadcourses';
                });
            }

            if (schoolField) {
                schoolField.addEventListener('change', loadMappedCourses);
                loadMappedCourses();
            }
        });
        JS;
        $mform->addElement('html', '<script type="text/javascript">' . $js . '</script>');

        $mform->addElement('passwordunmask', 'password', get_string('password', 'local_trainer'));
        $mform->setType('password', PARAM_TEXT);
    

        $mform->addElement('date_selector', 'dob', get_string('dob', 'local_poc'));
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

        $mform->addElement('text', 'blood_group', get_string('bloodgroup', 'local_trainer'));
        $mform->setType('blood_group', PARAM_TEXT);

        $mform->addElement('text', 'email', get_string('email', 'local_trainer'));
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', null, 'required', null, 'client');
        $mform->addRule('email', 'Enter valid email', 'email', null, 'client');
        $mform->addRule('email', 'Enter valid email', 'maxlength', 100, 'client');

        $mform->addElement('text', 'contact_number', get_string('contactnumber', 'local_trainer'));
        $mform->setType('contact_number', PARAM_TEXT);
        $mform->addRule('contact_number', null, 'required', null, 'client');
        $mform->addRule('contact_number', 'Phone number should have 10 digits', 'minlength', 10, 'client');
        $mform->addRule('contact_number', 'Invalid phone number format', 'regex', '/^\+?\d{1,4}?[-.\s]?\(?\d{1,4}?\)?[-.\s]?\d{1,4}[-.\s]?\d{1,9}$/i', 'client');

        $mform->addElement('text', 'permanent_address', get_string('permanentaddress', 'local_trainer'));
        $mform->setType('permanent_address', PARAM_TEXT);

        $mform->addElement('text', 'current_address', get_string('currentaddress', 'local_trainer'));
        $mform->setType('current_address', PARAM_TEXT);

        $mform->addElement('text', 'alternative_address', get_string('alternativeaddress', 'local_trainer'));
        $mform->setType('alternative_address', PARAM_TEXT);

        $mform->addElement('text', 'experience', get_string('experience', 'local_trainer'));
        $mform->setType('experience', PARAM_TEXT);
        $mform->addRule('experience', get_string('required'), 'required', null, 'client');

        $ctcElement=$mform->addElement('text', 'ctc', get_string('ctc', 'local_trainer'));
        $mform->setType('ctc', PARAM_TEXT);

        $mform->addElement('date_selector', 'date_of_joining', get_string('dateofjoining', 'local_poc')); 
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

        $mform->addElement('text', 'designation', get_string('designation', 'local_trainer'),['value'=>'Training & Development','readonly'=>'true']);
        $mform->setType('designation', PARAM_TEXT);

  
        $this->add_action_buttons(true, get_string('savechanges', 'local_trainer'));
    }

    function validation($data, $files) {
        $errors = [];
        global $DB, $CFG, $USER;
        $user = $DB->get_record('user', array('id' => $this->_customdata['id']));
        if (empty(trim($data['firstname']))) {
            $errors['firstname'] = get_string('required', 'local_trainer');
        }
        if (empty(trim($data['lastname']))) {
            $errors['lastname'] = get_string('required', 'local_trainer');
        }
        if (empty($data['schoolid'])) {
            $errors['schoolid'] = get_string('required');
        } else {
            $pocuserid = $this->_customdata['pocuserid'] ?? $USER->id;
            if (!$DB->record_exists('schoolassign', ['userid' => $pocuserid, 'schoolid' => $data['schoolid']])) {
                $errors['schoolid'] = get_string('invalidschool', 'local_trainer');
            }
        }
        if (empty(trim($data['dob']))) {
            $errors['dob'] = get_string('required', 'local_trainer');
        }
        // if (empty(trim($data['blood_group']))) {
        //     $errors['blood_group'] = get_string('required', 'local_trainer');
        // }
        if (empty(trim($data['email']))) {
            $errors['email'] = get_string('required', 'local_trainer');
        }
        if (empty(trim($data['contact_number']))) {
            $errors['contact_number'] = get_string('required', 'local_trainer');
        }
        if (empty(trim($data['experience']))) {
            $errors['experience'] = get_string('required', 'local_trainer');
        }
       
        if (empty(trim($data['date_of_joining']))) {
            $errors['date_of_joining'] = get_string('required', 'local_trainer');
        }
        if (empty(trim($data['designation']))) {
            $errors['designation'] = get_string('required', 'local_trainer');
        }

         if (!empty($data['password'])) {
            $errmsg = '';
            $tempuser = new stdClass();
            $tempuser->password = $data['password'];
        }
         if (!empty($data['password']) && !check_password_policy($data['password'], $errmsg, $tempuser)) {
            $errors['password'] = $errmsg;
        }

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


        return $errors;
    }

    private function js_string($string)
    {
        return addslashes($string);
    }
}

?>


<!-- <script>
document.addEventListener('DOMContentLoaded', function() {
    const userName = document.getElementById('id_username');
    const firstName = document.getElementById('id_firstname');
    const lastName = document.getElementById('id_lastname');
    const ctcName = document.getElementById('id_ctc');
    const siteAdmin = document.getElementById('siteadmin').value;
    const dateOfJoiningName = document.getElementById('id_date_of_joining');

    if (siteAdmin == "false") {
        userName.disabled = true;
        firstName.disabled = true;
        lastName.disabled = true;
        ctcName.disabled = true;
        dateOfJoiningName.disabled = true;
    }
});
</script> -->
