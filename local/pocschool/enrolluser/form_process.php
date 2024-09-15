<?php

require_once(__DIR__ . '/../../../config.php');

$courseid = required_param('id', PARAM_INT);
global $DB, $OUTPUT, $PAGE, $USER;

// Ensure the user has the required capabilities to manage enrollments.
// require_capability('enrol/manual:unenrol', context_course::instance($courseid));

if (isset($_POST['add'])) { // If the user has selected 'Add'
    if (isset($_POST["potential_select"])) { // If something is selected
        $context = context_course::instance($courseid);
        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        foreach ($_POST['potential_select'] as $student) { // Select a user
            if (!is_enrolled($context, $student)) {
                // Not already enrolled, so try enrolling them.
                if (!enrol_try_internal_enrol($courseid, $student, $studentroleid, time())) {
                    // There's a problem.
                    throw new moodle_exception('unabletoenrolerrormessage', 'langsourcefile');
                }
            }
        }
    }
}

if (isset($_POST['remove'])) { // If the user has selected 'Remove'
    if (isset($_POST["existing_select"])) { // If something is selected
        $context = context_course::instance($courseid);

        // Get the enrol instance
        $enrol = enrol_get_plugin('manual');
        $instances = enrol_get_instances($courseid, true);
        $manualinstance = null;
        foreach ($instances as $instance) {
            if ($instance->enrol == 'manual') {
                $manualinstance = $instance;
                break;
            }
        }

        if (!$manualinstance) {
            throw new moodle_exception('Manual enrolment instance not found for this course.');
        }

        foreach ($_POST['existing_select'] as $student) { // Select a user
            // Unenroll the user from the course.
            $enrol->unenrol_user($manualinstance, $student);
        }
    }
}

?>
