<?php
define('CLI_SCRIPT', true);
require_once('config.php');
require_once('local/mydashboard/lib.php');

$student = new stdClass();
$student->id = 2; // admin or dummy user
$context = local_mydashboard_get_student_timetable_context($student);

global $OUTPUT;
echo $OUTPUT->render_from_template('local_mydashboard/studentdashboard', ['timetable' => $context]);
