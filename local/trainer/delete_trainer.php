<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/user/lib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Delete School');
$PAGE->set_heading('Delete School');
require_login();

global $CFG, $DB;
    
$id = optional_param('id', 0, PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$returnurl = $returnurl ? new moodle_url($returnurl) : new moodle_url('/local/trainer/trainer_manage.php');
// $userid = optional_param('userid', 0, PARAM_INT);

if (optional_param('confirm', 0, PARAM_INT)) {


    if ($user = $DB->get_record('user', array('id' => $id))) {
        $deleted1 = user_delete_user($user);
        $deleted = $DB->delete_records('trainer', array('userid' => $id));
        }

    if ($deleted !== false) {
        redirect($returnurl, get_string('deletesuccess', 'local_trainer'), 2);
    } else {
        print_error('deletion_failed', 'local_trainer', "$CFG->wwwroot/my/");
    }
} else {
    echo $OUTPUT->header();
    $confirmurl = new moodle_url('/local/trainer/delete_trainer.php', [
        'confirm' => 1,
        'id' => $id,
        'returnurl' => $returnurl->out_as_local_url(false),
    ]);
    echo $OUTPUT->confirm(get_string('deleteconfirm', 'local_trainer'), 
                         $confirmurl,
                         $returnurl);
    echo $OUTPUT->footer();
}
