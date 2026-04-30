<?php
require_once "../../../config.php";
require_login();

global $DB, $OUTPUT, $PAGE;

// --- STEP 1: Get Grade and Course Info ---
$gradeid = required_param('id', PARAM_INT); 

$poc_course = $DB->get_record('poc_copy_course', array('gradeid' => $gradeid, 'status' => 1));
if (!$poc_course) {
    throw new moodle_exception('invalidcourse', 'error');
}
$course = $DB->get_record('course', ['id' => $poc_course->courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id);

// As requested, the security check has been removed.
// require_capability('local/pocstudnetenroll:manage', $context);

// --- STEP 2: Page Setup ---
// $PAGE->set_context($context);
$PAGE->set_url('/local/pocschool/enrolluser/studentlist.php', ['id' => $gradeid]);
// CHANGED: Page layout is set back to 'course'.
 $PAGE->set_pagelayout('course');
$PAGE->set_title('Enroll Student');
$PAGE->set_heading('Enroll Student in ' . $course->fullname);
$PAGE->navbar->add("Enroll Student");

echo $OUTPUT->header();

// NEW: Added the "Back to Courses" button.
$backurl = new moodle_url('/local/pocschool/viewcourse.php', ['catId' => $gradeid]);
echo html_writer::div(html_writer::link($backurl, '<< Back to Courses'), 'mb-3');


// --- STEP 3: Get Enrolled Users for the LEFT LIST ---
$enrolled_user_ids = $DB->get_fieldset_select(
    'pocstudnetenroll_queue', 
    'userid', 
    'gradeid = :gradeid AND action = :action', 
    ['gradeid' => $gradeid, 'action' => 'enroll']
);

$existing_select_options = '';
if (!empty($enrolled_user_ids)) {
    list($user_sql, $user_params) = $DB->get_in_or_equal($enrolled_user_ids, SQL_PARAMS_QM);
    $users_for_list = $DB->get_records_sql("SELECT id, firstname, lastname, username, email FROM {user} WHERE id $user_sql ORDER BY firstname, lastname", $user_params);

    foreach ($users_for_list as $user) {
        $displaytext = fullname($user) . " ({$user->username}, {$user->email})";
        $existing_select_options .= html_writer::tag('option', $displaytext, ['value' => $user->id]);
    }
} else {
    $existing_select_options = html_writer::tag('option', 'No students have been added', ['disabled' => 'disabled']);
}


// --- STEP 4: Get Available Users for the RIGHT LIST ---
$all_students_in_grade = $DB->get_fieldset_select(
    'student',
    'userid',
    'gradeid = :gradeid',
    ['gradeid' => $gradeid]
);
$available_user_ids = array_diff($all_students_in_grade, $enrolled_user_ids);
// echo "<pre>";
// echo "All students in grade: ";
// print_r($all_students_in_grade);
// echo "Enrolled user IDs: ";
// print_r($enrolled_user_ids);
// echo "Available user IDs: ";
// print_r($available_user_ids);
// echo "</pre>";
// die;
$potential_select_options = '';
if (!empty($available_user_ids)) {
    list($user_sql, $user_params) = $DB->get_in_or_equal($available_user_ids, SQL_PARAMS_QM);
    $potential_users = $DB->get_records_sql("SELECT id, firstname, lastname, username, email FROM {user} WHERE id $user_sql AND deleted = 0 ORDER BY firstname, lastname", $user_params);
    
    foreach ($potential_users as $user) {
        $displaytext = fullname($user) . " ({$user->username}, {$user->email})";
        $potential_select_options .= html_writer::tag('option', $displaytext, ['value' => $user->id]);
    }
} else {
    $potential_select_options = html_writer::tag('option', 'All students in grade have been added', ['disabled' => 'disabled']);
}


// --- STEP 5: Display the Form ---
?>
<form id="Enrollform" method="post" action="form_process.php">
    <div>
        <input type="hidden" name="id" value="<?php echo $course->id; ?>">
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

        <table summary="" class="roleEnrolltable generaltable generalbox boxaligncenter" cellspacing="0">
            <tr>
                <td id="existingcell">
                    <p><label for="existing_select">Added Students (Left)</label></p>
                    <select name="existing_select[]" id="existing_select" multiple="multiple" size="15" class="form-control">
                        <?php echo $existing_select_options; ?>
                    </select>
                    <div class="mt-2">
                        <input type="text" id="left-search" class="form-control" placeholder="Search added students...">
                    </div>
                </td>
                <td id="buttonscell" class="align-middle">
                    <div id="addcontrols">
                        <input name="add" id="add" type="submit" value="<?php echo $OUTPUT->larrow().'&nbsp;'.get_string('add'); ?>" title="<?php print_string('add'); ?>" class="btn btn-primary" /><br />
                    </div>
                    <div id="removecontrols" class="mt-1">
                        <input name="remove" id="remove" type="submit" value="<?php echo get_string('remove').'&nbsp;'.$OUTPUT->rarrow(); ?>" title="<?php print_string('remove'); ?>" class="btn btn-secondary" />
                    </div>
                </td>
                <td id="potentialcell">
                    <p><label for="potential_select">Available Students (Right)</label></p>
                    <select name="potential_select[]" id="potential_select" multiple="multiple" size="15" class="form-control">
                        <?php echo $potential_select_options; ?>
                    </select>
                    <div class="mt-2">
                        <input type="text" id="right-search" class="form-control" placeholder="Search available students...">
                    </div>
                </td>
            </tr>
        </table>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to handle the search filtering
    const setupSearch = (inputId, selectId) => {
        const searchInput = document.getElementById(inputId);
        const selectElement = document.getElementById(selectId);

        if (!searchInput || !selectElement) {
            return;
        }

        searchInput.addEventListener('keyup', function() {
            const filter = searchInput.value.toLowerCase();
            const options = selectElement.getElementsByTagName('option');

            for (let i = 0; i < options.length; i++) {
                const optionText = options[i].textContent || options[i].innerText;
                if (optionText.toLowerCase().indexOf(filter) > -1) {
                    options[i].style.display = '';
                } else {
                    options[i].style.display = 'none';
                }
            }
        });
    };

    // Activate search for both lists
    setupSearch('left-search', 'existing_select');
    setupSearch('right-search', 'potential_select');
});
</script>

<?php
echo $OUTPUT->footer();
?>