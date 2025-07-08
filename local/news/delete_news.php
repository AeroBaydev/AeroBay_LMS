<?php
require_once('../../config.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Delete news');
$PAGE->set_heading('Delete news');
require_login();


global $CFG, $DB;

$schoolid = optional_param('id', 0, PARAM_INT);


if (optional_param('confirm', 0, PARAM_INT)) {
    
    
 

    $deleted = $DB->delete_records('news', array('id' => $schoolid));


    if ($deleted !== false) {
       
        redirect("$CFG->wwwroot/local/news/index.php", get_string('deletesuccess', 'local_news'), 2);
    } else {
        print_error('deletion_failed', 'local_news', "$CFG->wwwroot/my/");
    }
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('deleteconfirm', 'local_news'), 
                         new moodle_url("$CFG->wwwroot/local/news/delete_news.php?confirm=1&id=$schoolid"), 
                         new moodle_url("$CFG->wwwroot/local/news/index.php"));
    echo $OUTPUT->footer();
}
