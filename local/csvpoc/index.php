<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once('forms/upload_form.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/uploaduser/locallib.php');

global $DB, $OUTPUT, $PAGE;

require_login(); // Ensure the user is logged in
$context = context_system::instance();
$PAGE->set_context($context); // Set the context for the page
$PAGE->set_url(new moodle_url('/local/csvpoc/index.php'));
$PAGE->set_title(get_string('pluginnametitl', 'local_csvpoc'));
//$PAGE->set_heading(get_string('pluginname', 'local_csvpoc'));



$schoolid = required_param('schoolid', PARAM_INT);

$gradeid = required_param('gradeid', PARAM_INT);
function get_category_details($gradeid) {
    global $DB;
    $getcourseid=$DB->get_record('poc_copy_course', array('gradeid' => $gradeid ,'status'=>1));
    $category = $DB->get_record('course', array('id' => $getcourseid->courseid ,'visible'=>1));
    return $category;
  }

  if($getcourseid=get_category_details($gradeid)){
    $courseid=$getcourseid->id;
  }
  else{
   
    
    echo $OUTPUT->header();
    echo $OUTPUT->notification('The following required course id no course found'); 
    $continueurl1 = new moodle_url('/local/pocschool/index.php', array('parent' => $schoolid));
    echo $OUTPUT->single_button($continueurl1, get_string('continue'),'get');
    echo $OUTPUT->footer();
    die;   
  }
// Form for file upload
$mform = new upload_form();

function generate_code_from_name($firstname, $lastname, $iteration) {
    global $DB;
    // Remove non-alphabetic characters
    $firstname_name = preg_replace('/[^a-zA-Z]/', '', $firstname);
    $lastname_name = preg_replace('/[^a-zA-Z]/', '', $lastname);
    $lastNumber = $DB->get_field_sql('SELECT MAX(id) FROM {user}', null);
    $newLastNumber = $lastNumber + $iteration;
    // Get the first two characters
    $firstname_name_prefix = substr($firstname_name, 0, 2);
    $lastname_name_prefix = substr($lastname_name, 0, 2);
    
    // Convert to lowercase
    $prefix = strtolower($firstname_name_prefix . $lastname_name_prefix);
    
    // Append "pocs" to the prefix
    $code = $prefix . 'pocs' . $newLastNumber;
    
    return $code;
}

if ($mform->is_cancelled()) {
    redirect(new moodle_url("/local/pocschool/index.php?parent=$schoolid"));
} else if ($data = $mform->get_data()) {
    $content = $mform->get_file_content('userfile');
    if ($content === false) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('fileuploaderror', 'local_csvpoc'), 'notifyproblem');
        echo $OUTPUT->footer();
        die();
    }

    // Initialize csv_import_reader
    $iid = csv_import_reader::get_new_iid('uploaduser');
    $cir = new csv_import_reader($iid, 'uploaduser');
    $content = $mform->get_file_content('userfile');
    $readcount = $cir->load_csv_content($content, 'utf-8', 'comma');
    $required_headers = array('firstname', 'lastname', 'email','section'); // Add 'sequestion' as required header

    if (!$readcount) {
        $errors[] = $cir->get_error();
    }
    $headers = $cir->get_columns();
    if (!$headers) {
        $errors[] = 'Cannot parse submitted CSV file.';
    }
    if (!empty($errors)) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification(implode('<br>', $errors), 'notifyproblem');
        echo $OUTPUT->footer();
        die();
    }

    $missing_headers = array_diff($required_headers, $headers);
    if (!empty($missing_headers)) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification('The following required headers are missing: ' . implode(', ', $missing_headers), 'notifyproblem');
        $continueurl1 = new moodle_url('/local/pocschool/index.php', array('parent' => $schoolid));
    echo $OUTPUT->single_button($continueurl1, get_string('continue'),'get');
    echo $OUTPUT->footer();
    }

    if ($required_headers !== $headers) {
        echo $OUTPUT->header();
        echo $OUTPUT->notification('do not have the same values or the same sequence. missing  in csv file please cheack studentExpamle.csv' . implode(', ', $missing_headers), 'notifyproblem');
        $continueurl1 = new moodle_url('/local/pocschool/index.php', array('parent' => $schoolid));
        echo $OUTPUT->single_button($continueurl1, get_string('continue'),'get');
        echo $OUTPUT->footer();
        die();


    }

    $iteration = 0;
    $users = array();

    $cir->init();
    while ($line = $cir->next()) {
        $iteration++;

        $user = new stdClass();
        $user->username = generate_code_from_name($line[0], $line[1], $iteration);
        $user->firstname = trim($line[0]);
        $user->lastname = trim($line[1]);
        $user->email = trim($line[2]);
  
        $user->section = trim($line[3]);
        if (!validate_email($user->email)) {
            $user->exists = true;
            $user->find ="not valide email";
        }
        else{
            if (!$DB->record_exists('user', array('email' => $user->email))) {
                $user->exists = false;
                $user->find="email not exists";
            } 
            else {
                $user->exists = true;
                $user->find="email already exists";
                }
        
        }
        $users[] = $user;
    }

    $cir->close();

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('uploadusersresult', 'local_csvpoc'));

    echo html_writer::start_tag('table', array('class' => 'generaltable'));
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('username'));
    echo html_writer::tag('th', get_string('firstname'));
    echo html_writer::tag('th', get_string('lastname'));
    echo html_writer::tag('th', get_string('email'));
    echo html_writer::tag('th', get_string('section','local_csvpoc'));
    echo html_writer::tag('th', get_string('exists', 'local_csvpoc'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($users as $user) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $user->username );
        echo html_writer::tag('td', $user->firstname);
        echo html_writer::tag('td', $user->lastname);
        echo html_writer::tag('td', $user->email);
        echo html_writer::tag('td', $user->section);
        // echo html_writer::tag('td', $user->exists ? get_string('yes') : get_string('no'));
        echo html_writer::tag('td', $user->exists ? '<span style="color:red">'.$user->find.'</span>' : '<span style="color:green;">'.$user->find.'</span>');
        echo html_writer::end_tag('tr');
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    // Add a continue button
    $continueurl = new moodle_url('/local/csvpoc/continue.php', array('users' => json_encode($users),'schoolid'=>$_POST['schoolid'],'gradeid'=>$_POST['gradeid'],'courseid'=>$_POST['courseid']));
    echo $OUTPUT->single_button($continueurl, get_string('continue'));
    $continueurl1 = new moodle_url('/local/pocschool/index.php', array('parent' => $schoolid));
    echo $OUTPUT->single_button($continueurl1, get_string('cancel'),'get');
    echo $OUTPUT->footer();
    echo $OUTPUT->footer();
} else {
    echo $OUTPUT->header();
    $student_data = [
        'schoolid' => $schoolid,
        'gradeid' => $gradeid,
       
    ];
    
    $form = new upload_form(null, $student_data);
    $form->set_data($student_data);
    $form->display();
    echo $OUTPUT->footer();
}
?>
