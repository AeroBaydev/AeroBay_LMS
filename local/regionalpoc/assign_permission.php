<?php
require_once('../../config.php');
require_once('classes/form/assign_permission_form.php');

// $context = context_system::instance();
// require_capability('local/regionalpoc:assign', $context);

$PAGE->set_url(new moodle_url('/local/regionalpoc/assign_permission.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_regionalpoc'));
$PAGE->set_heading(get_string('pluginname', 'local_regionalpoc'));

// Create form instance
$form = new assign_permission_form();

// If form is canceled, redirect
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/regionalpoc/assign_permission.php'));
}

// If form is submitted and validated
if ($formdata = $form->get_data()) {
    $userid = $formdata->userid;
    $capabilities = $formdata->capabilities;
    $action = $formdata->action;

    // Validate user ID
    if (!$user = $DB->get_record('user', ['id' => $userid])) {
        echo $OUTPUT->notification('Invalid user ID', 'notifyproblem');
    } else {
        // Process each selected capability
        foreach ($capabilities as $capability) {
            switch ($capability) {
                case 'course_management':
                    $capabilityname = 'moodle/course:manageactivities';
                    break;
                case 'student_management':
                    $capabilityname = 'moodle/user:manage';
                    break;
                case 'activity_resource_management':
                    $capabilityname = 'moodle/course:managefiles';
                    break;
                case 'trainer_management':
                    $capabilityname = 'moodle/site:approvecourse';
                    break;
                default:
                    echo $OUTPUT->notification('Invalid capability', 'notifyproblem');
                    continue 2; // Skip to next iteration of outer loop
            }

            // Assign or remove capability based on action
            if ($action === 'assign') {
                assign_capability($capabilityname, CAP_ALLOW, $userid, $context->id);
            } elseif ($action === 'remove') {
                assign_capability($capabilityname, CAP_PREVENT, $userid, $context->id);
            }
        }

        // Show success message
        echo $OUTPUT->notification(get_string('success', 'local_regionalpoc'), 'notifysuccess');
    }
}

// Display the form
echo $OUTPUT->header();
$form->set_data(['userid' => optional_param('userId', 0, PARAM_INT)]);
$form->display();
echo $OUTPUT->footer();
