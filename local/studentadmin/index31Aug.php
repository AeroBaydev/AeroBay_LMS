<?php
require_once('../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once('classes/table/student_table.php');

require_login();

// Get URL parameters
$search = optional_param('search', '', PARAM_TEXT);
$schoolid = optional_param('schoolid', '', PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);

$PAGE->set_url('/local/studentadmin/index.php', array('search' => $search, 'schoolid' => $schoolid, 'page' => $page));
$PAGE->set_pagelayout('course');
$PAGE->set_title('Student Management');
$PAGE->navbar->add('Student Management', $PAGE->url);

$table = new student_table('uniqueid');

// Handle download requests
$table->is_downloading($download, 'student', 'student_data');

// Prepare SQL fields, from, where, and params
$fields = "(@row_number := @row_number + 1) as serial, u.id as id, s.student_id as studentid, u.username as username, u.firstname as firstname, u.lastname as lastname, u.email as email";
$from = "{user} u JOIN {student} s ON s.userid = u.id";
$where = "u.deleted = 0";
$params = [];

// Apply filters to the SQL query
if ($search) {
    $where .= " AND (u.username LIKE :search1 OR u.firstname LIKE :search2 OR u.lastname LIKE :search3 OR u.email LIKE :search4)";
    $params['search1'] = "%$search%";
    $params['search2'] = "%$search%";
    $params['search3'] = "%$search%";
    $params['search4'] = "%$search%";
}

if ($schoolid !== '' && $schoolid != 0) {
    $where .= " AND s.schoolid = :schoolid";
    $params['schoolid'] = $schoolid;
}

// Set SQL for table and output configuration
$perpage = 10;
$DB->execute('SET @row_number := ' . ($perpage * $page));
$table->set_sql($fields, $from, $where, $params);
$table->define_baseurl(new moodle_url('/local/studentadmin/index.php', array('search' => $search, 'schoolid' => $schoolid)));

// Check if table is downloading
if ($table->is_downloading()) {
    // Output the table for download
    $table->out($perpage, true);
    exit; // End script execution after download
}

// Only display header and forms if not downloading
echo $OUTPUT->header();
echo html_writer::tag('h2', 'Student Management', array('class' => 'custom-heading add-student-user'));

echo html_writer::start_div('action-button-container');
echo html_writer::start_tag('form', array('method' => 'post', 'class' => 'd-flex', 'action' => $PAGE->url));

// Link to add a new student
echo html_writer::link(new moodle_url('/local/studentadmin/add.php'), 'Add New Student', array('class' => 'btn btn-primary'));

// Search input field
echo html_writer::empty_tag('input', array('type' => 'search', 'class' => 'ml-auto form-control rounded mr-2', 'name' => 'search', 'placeholder' => 'Search...', 'value' => s($search)));

// School dropdown options
$school_options = $DB->get_records_sql_menu(
    "SELECT cc.id, cc.name
     FROM {schoolassign} sa
     JOIN {course_categories} cc ON sa.schoolid = cc.id"
);
$school_options = array(0 => get_string('pleaseselectschool', 'local_students')) + $school_options;

echo html_writer::start_tag('select', array('name' => 'schoolid', 'class' => 'ml-2 form-control'));
foreach ($school_options as $id => $name) {
    $attributes = array('value' => $id);
    if ($schoolid !== '' && $schoolid == $id) {
        $attributes['selected'] = 'selected';
    }
    echo html_writer::tag('option', s($name), $attributes);
}
echo html_writer::end_tag('select');

// Role dropdown options
$options2 = array(
    '' => 'Select Role',
    'student' => 'Student',
    'graduate' => 'Graduate'
);
echo html_writer::start_tag('select', array('name' => 'role', 'class' => 'ml-2 form-control'));
foreach ($options2 as $value => $label) {
    echo html_writer::tag('option', $label, array('value' => $value));
}
echo html_writer::end_tag('select');

// Submit button
echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => 'Search', 'class' => 'btn btn-primary mr-2'));

// Clear button
echo html_writer::link(new moodle_url('/local/studentadmin/index.php'), 'Clear', array('class' => 'btn btn-secondary mr-2'));

echo html_writer::end_tag('form');
echo html_writer::end_div();

// Display the table
$table->out($perpage, true);
echo $OUTPUT->footer();
?>
