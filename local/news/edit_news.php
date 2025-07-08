<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once('classes/form/edit_news_form.php');
global $PAGE, $CFG, $DB;

$newsid = optional_param('id', 0, PARAM_INT);
require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
// $PAGE->set_title('News Registration');
$PAGE->navbar->add('News Management', "$CFG->wwwroot/local/news/index.php");
$PAGE->navbar->add('Update News Details', "$CFG->wwwroot/local/news/edit_school.php?id=$newsid");
$PAGE->set_heading('Update News Details');

        $newsid_record = $DB->get_record('news', ['id' => $newsid]);
        $news = new stdClass();
        $news->id = $newsid;
        $news->news = $newsid_record->news;
        $news->school = $newsid_record->schoolid;
        $news->grade = $newsid_record->gradeid;
        $news->timecreated = $newsid_record->timecreated;

        $form = new edit_news_form($newsid,$newsid_record->schoolid);
        $form->set_data($news);


if ($form->is_cancelled()) {
    redirect("$CFG->wwwroot/local/news/");
} 
elseif ($data = $form->get_data()) {
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
        $DB->insert_record('news', $news);
    }
    redirect("$CFG->wwwroot/local/news/", get_string('updatechangesnotify', 'local_news'), 2);
} 

else {

    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();
}
