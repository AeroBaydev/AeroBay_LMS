<?php
require_once('../../config.php');
require_once('classes/form/copy_course_form.php');
global $DB, $PAGE, $OUTPUT, $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->libdir . '/cronlib.php');
require_login();
require_capability('moodle/course:create', context_system::instance());

$PAGE->set_url(new moodle_url('/local/coursecopy/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('pluginname', 'local_coursecopy'));
$PAGE->set_heading(get_string('pluginname', 'local_coursecopy'));
$PAGE->navbar->add('Course Copy', new moodle_url('/index.php'));

$mform = new copy_course_form();
// $sql = "UPDATE {course} SET fullname = TRIM(SUBSTRING_INDEX(fullname, 'copy', 1)) WHERE fullname LIKE '%copy%'";
// $DB->execute($sql);
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/'));
} else if ($data = $mform->get_data()) {
    $courseid = $_POST['courseid'];
    $destCategoryIds = isset($_POST['destSubcategory']) ? $_POST['destSubcategory'] : [];
    
    if ($courseid && !empty($destCategoryIds)) {
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        
        foreach ($destCategoryIds as $destCategoryId) {
            $category = $DB->get_record('course_categories', ['id' => $destCategoryId], '*', MUST_EXIST);

            $copydata = (object) [
                'courseid' => $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'category' => $category->id,
                'visible' => $course->visible,
                'startdate' => $course->startdate,
                'enddate' => $course->enddate,
                'idnumber' => $course->idnumber,
                'userdata' => '0',
                'keptroles' => []
            ];
            
            $success = \copy_helper::create_copy($copydata);

            if (!$success) {
                echo $OUTPUT->notification('Failed to copy the course to category: ' . $category->name, \core\output\notification::NOTIFY_ERROR);
            }
        }

        ignore_user_abort(true);
        $cron_command = 'php ' . escapeshellarg($CFG->dirroot . '/admin/cli/cron.php') . ' > /dev/null 2>&1 &';
        exec($cron_command);

        redirect(new moodle_url('/local/coursecopy/index.php'), 'Courses copied successfully!', null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        echo $OUTPUT->notification('Please select a course and at least one destination category.', \core\output\notification::NOTIFY_ERROR);
    }
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
?>
