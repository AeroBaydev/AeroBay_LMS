<?php
require_once('../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once('classes/table/student_table.php');

require_login();

$search = optional_param('search', '', PARAM_TEXT);
$schoolid = optional_param('schoolid', '', PARAM_TEXT);
$download = optional_param('download', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);



$PAGE->set_url('/local/studentadmin/index.php');
$PAGE->set_pagelayout('course');
$PAGE->set_title('Student Management');
$PAGE->navbar->add('Student Management', $PAGE->url);

$table = new student_table('uniqueid');
$table->is_downloading($download, 'student', 'student_data');

if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    echo html_writer::tag('h2', 'Student Management', array('class' => 'custom-heading add-student-user'));

    echo html_writer::start_div('action-button-container');
    echo html_writer::start_tag('form', array('method' => 'post', 'class' => 'd-flex', 'action' => $PAGE->url));
    
    // Link to add a new student
    echo html_writer::link(new moodle_url('/local/studentadmin/add.php'), 'Add New Student', array('class' => 'btn btn-primary'));
    
    // Search input field
    echo html_writer::empty_tag('input', array('type' => 'search', 'class' => 'ml-auto form-control rounded mr-2', 'name' => 'search', 'placeholder' => 'Search...', 'value' => s($search)));
    
    // First dropdown option (example: filter by status)
    $school_options=[];
    $school_options = $DB->get_records_sql_menu(
        "SELECT cc.id, cc.name
         FROM {schoolassign} sa
         JOIN {course_categories} cc ON sa.schoolid = cc.id"
    );

    $school_options = array(0 => get_string('pleaseselectschool', 'local_students')) + $school_options;

    echo html_writer::start_tag('select', array('name' => 'schoolid', 'class' => 'ml-2 form-control'));
    foreach ($school_options as $id => $name) {
        $attributes = array('value' => $id);
        // Check if the current $id matches the selected school ID
        if (isset($schoolid) && $schoolid == $id) {
            $attributes['selected'] = 'selected';
        }
        echo html_writer::tag('option', s($name), $attributes);
    }
    echo html_writer::end_tag('select');
    
    // Second dropdown option (example: filter by role)
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
}

$fields = "(@row_number := @row_number + 1) as serial, u.id as id,s.student_id as studentid ,u.username as username, u.firstname as firstname, u.lastname as lastname, u.email as email";
$from = "{user} u join {student} s on s.userid=u.id";
$where = "u.deleted = 0";
$params = [];

if ($search) {
    $where .= " AND (u.username LIKE :search1 OR u.firstname LIKE :search2 OR u.lastname LIKE :search3 OR u.email LIKE :search4 OR s.schoolid LIKE :search5 OR s.gradeid LIKE :search6)";
    $params = [
        'search1' => "%$search%",
        'search2' => "%$search%",
        'search3' => "%$search%",
        'search4' => "%$search%",
        'search5' => "%$search%",
        'search6' => "%$search%"

    ];
   
}

elseif ($schoolid) {
     $where .= " AND (s.schoolid LIKE :search1 )";
    $params = [
        'search1' => "%$schoolid%",
    
    ];


}

if($search && $schoolid){

    // $where .= " AND (s.schoolid LIKE :search OR u.username LIKE :search1 OR u.firstname LIKE :search2 OR u.lastname LIKE :search3 OR u.email LIKE :search4 )";
    // $params = [
  
    //     'search1' => "%$search%",
    //     'search2' => "%$search%",
    //     'search3' => "%$search%",
    //     'search4' => "%$search%",
    
    // ];


}

$perpage = 10;
$DB->execute('SET @row_number := ' . ($perpage * $page));
$table->set_sql($fields, $from, $where, $params);
// $table->define_baseurl($PAGE->url->out(true) . '&page=' . $page);
$table->define_baseurl("$CFG->wwwroot/local/studentadmin/index.php?page=$page");

if ($table->is_downloading()) {
    $table->out($perpage, true);
    exit;
} else {
    $table->out($perpage, true);
    echo $OUTPUT->footer();
}
?>
