<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once('classes/form/trainer_form.php');
require_once('../../lib/moodlelib.php');
require_once($CFG->dirroot . '/enrol/manual/lib.php');
require_once($CFG->dirroot.'/local/emailtemplates/email_sender.php');
global $PAGE, $CFG;

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('courae');
$PAGE->set_title('Trainer Registration');
$PAGE->navbar->add('Trainer Management', "$CFG->wwwroot/local/trainer/trainer_manage.php");
$PAGE->navbar->add('Add New Trainer', "$CFG->wwwroot/local/trainer/trainer_form.php");
$PAGE->set_heading('Add New Trainer');

$mform = new trainer_form();

function local_trainer_enrol_in_course($courseid, $userid) {
    global $DB;

    $courseid = (int)$courseid;
    $userid = (int)$userid;
    if (empty($courseid) || empty($userid)) {
        return false;
    }

    $coursecontext = context_course::instance($courseid, IGNORE_MISSING);
    if (!$coursecontext || is_enrolled($coursecontext, $userid)) {
        return true;
    }

    $manualplugin = enrol_get_plugin('manual');
    if (!$manualplugin) {
        return false;
    }

    $manualinstance = $DB->get_record('enrol', [
        'courseid' => $courseid,
        'enrol' => 'manual',
        'status' => ENROL_INSTANCE_ENABLED
    ]);
    if (!$manualinstance) {
        return false;
    }

    $role = $DB->get_record('role', ['shortname' => 'trainer']);
    if (!$role) {
        $role = $DB->get_record('role', ['shortname' => 'teacher']);
    }
    if (!$role) {
        $role = $DB->get_record('role', ['shortname' => 'editingteacher']);
    }
    if (!$role) {
        return false;
    }

    $manualplugin->enrol_user($manualinstance, $userid, $role->id, time());
    return true;
}

if ($mform->is_cancelled()) {
    redirect("$CFG->wwwroot/local/trainer/trainer_manage.php");
} elseif ($data = $mform->get_data()) {
    $trainer = new stdClass();

    $year = date('y');
    $trainer_id_prefix = $year . 'AAPLTD';
    $lastNumber = $DB->get_field_sql('SELECT MAX(id) FROM {trainer}', null);
    $newLastNumber = $lastNumber + 1;
    $trainer_id = $trainer_id_prefix . str_pad($newLastNumber, 3, '0', STR_PAD_LEFT);
    // Insert school record
    $trainer->trainerid = $trainer_id;
    // Update last_number field
    //$DB->set_field('trainer', 'trainerid', $trainer_id, array('id' => $lastNumber));
    if (isset($_SESSION['userIdPoc'])) {
        $userid=$_SESSION['userIdPoc'];
      
   }else{
    $userid=$USER->id;
   }

    $trainer->username = $data->username;
    $trainer->firstname = $data->firstname;
    $trainer->lastname = $data->lastname;
    $trainer->password = $data->password;
    $trainer->mnethostid = 1;
    $trainer->dob = $data->dob;
    $trainer->blood_group = $data->blood_group;
    $trainer->email = $data->email;
    $trainer->contact_number = $data->contact_number;
    $trainer->permanent_address = $data->permanent_address;
    $trainer->current_address = $data->current_address;
    $trainer->alternative_address = $data->alternative_address;
    $trainer->experience = $data->experience;
    $trainer->ctc = $data->ctc;
    $trainer->state = $data->state;
    $trainer->date_of_joining = $data->date_of_joining;
    $trainer->designation = $data->designation;
    $trainer->confirmed = 1;
    $trainer->last_number = $newLastNumber;
    $trainer->createdby = $userid;
    $user_id = user_create_user($trainer);
    // set_user_preference('auth_forcepasswordchange', 1, $user_id);
    if ($user_id !== false) {
        $trainer->userid = $user_id;
        if (array_key_exists('schoolid', $DB->get_columns('trainer'))) {
            $trainer->schoolid = $data->schoolid;
        }
        $insert=  $DB->insert_record('trainer', $trainer);
        if($insert){
            $now = time();
            if (!$DB->record_exists('schoolassign', ['userid' => $user_id, 'schoolid' => $data->schoolid])) {
                $role = $DB->get_record_sql("SELECT roleid FROM {role_assignments} WHERE userid = :userid", ['userid' => $userid], IGNORE_MULTIPLE);

                $schoolassign = new stdClass();
                $schoolassign->schoolassignee = $userid;
                $schoolassign->schoolassignedto = $user_id;
                $schoolassign->assigneeroleid = $role ? $role->roleid : 0;
                $schoolassign->userid = $user_id;
                $schoolassign->schoolid = $data->schoolid;
                $schoolassign->timecreated = $now;
                $schoolassign->timemodified = $now;
                $DB->insert_record('schoolassign', $schoolassign);
            }

            $mappedcourses = $DB->get_records('poc_copy_course', [
                'pocid' => $userid,
                'schoolid' => $data->schoolid,
                'status' => 1
            ]);
            $hasmappingtable = $DB->get_manager()->table_exists('trainer_course_mapping');
            foreach ($mappedcourses as $mappedcourse) {
                local_trainer_enrol_in_course($mappedcourse->courseid, $user_id);
                if ($hasmappingtable) {
                    $trainermapping = new stdClass();
                    $trainermapping->trainerrecordid = $insert;
                    $trainermapping->traineruserid = $user_id;
                    $trainermapping->pocid = $userid;
                    $trainermapping->schoolid = $data->schoolid;
                    $trainermapping->gradeid = $mappedcourse->gradeid;
                    $trainermapping->courseid = $mappedcourse->courseid;
                    $trainermapping->poccourseid = $mappedcourse->id;
                    $trainermapping->status = 1;
                    $trainermapping->timecreated = $now;
                    $trainermapping->timemodified = $now;
                    $DB->insert_record('trainer_course_mapping', $trainermapping);
                }
            }

            \local_emailtemplates\email_sender::send_email("trainer", $user_id, $data->password,0);
        }
      
        redirect("$CFG->wwwroot/local/trainer/trainer_manage.php", get_string('trainersuccess', 'local_trainer'), 2,0);
    } else {
        print_error('usercreationerror', 'local_trainer');
    }
} else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
