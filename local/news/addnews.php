<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once('classes/form/news_form.php');
require_once($CFG->dirroot.'/local/news/lib.php');

global $PAGE, $CFG, $DB, $OUTPUT;

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title('New News');
$PAGE->navbar->add('News Management', "$CFG->wwwroot/local/news/");
$PAGE->navbar->add('Add News', "$CFG->wwwroot/local/news/addnews.php");
$PAGE->set_heading('Create New News');
 $PAGE->requires->js('/local/news/js/custom.js');
$mform = new news_form();

if ($mform->is_cancelled()) {
    redirect("$CFG->wwwroot/local/news/");
} elseif ($data = $mform->get_data()) {
    
    // Create a new news object to insert or update
    $news = new stdClass();
    
    // Set the news text
    $news->news = $data->news;
    
    // Handle the selected schools (store as a comma-separated string)
    if (isset($data->school) && !empty($data->school)) {
        $news->schoolid = implode(',', $data->school);  // Convert array of selected schools into a comma-separated string
    } else {
        $news->schoolid = '';  // If no schools are selected, set as empty
    }

    // Handle the selected grades (store as a comma-separated string)
    if (isset($data->grade) && !empty($data->grade)) {
        if ($data->grade == [0]) {
            $data->grade = range(0, 12); // Replace 0 with 1 to 12
        }
        $news->gradeid = implode(',', $data->grade);  // Convert array of selected grades into a comma-separated string
    
    } else {
        $data->grade = range(0, 12);
        $news->gradeid = implode(',', $data->grade);  // If no grades are selected, set as empty
    }

    // Set the time of creation
    $news->timecreated = time();
    
    // Check if it's an update (existing record) or insert (new record)
    if (isset($data->id) && !empty($data->id)) {
        // If ID is present, update the existing record
        $news->id = $data->id;  // Set the ID for updating the record
        // Update the record in the database
        $DB->update_record('news', $news);
    } else {
        // If no ID is present, insert a new record
        $news->id = $DB->insert_record('news', $news);
        
        // Send notifications
        local_news_send_notifications($news);
        
        redirect("$CFG->wwwroot/local/news/", get_string('newssuccess', 'local_news'), 2);
    }
}
 else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
?>
