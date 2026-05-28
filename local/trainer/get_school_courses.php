<?php
require_once(__DIR__ . '/../../config.php');

require_login();
require_sesskey();

global $DB, $USER;

$schoolid = required_param('schoolid', PARAM_INT);
$requestedpocuserid = optional_param('pocuserid', 0, PARAM_INT);
$pocuserid = $USER->id;
if (isset($_SESSION['userIdPoc'])) {
    $pocuserid = $_SESSION['userIdPoc'];
}
if (!empty($requestedpocuserid) && (is_siteadmin() || $requestedpocuserid == $USER->id || (isset($_SESSION['userIdPoc']) && $requestedpocuserid == $_SESSION['userIdPoc']))) {
    $pocuserid = $requestedpocuserid;
}

header('Content-Type: application/json');

if (!$DB->record_exists('schoolassign', ['userid' => $pocuserid, 'schoolid' => $schoolid])) {
    echo json_encode(['html' => get_string('invalidschool', 'local_trainer')]);
    exit;
}

$records = $DB->get_records_sql(
    "SELECT pcc.id,
            pcc.gradeid,
            pcc.courseid,
            grade.name AS gradename,
            course.fullname AS coursename
       FROM {poc_copy_course} pcc
       JOIN {course_categories} grade ON grade.id = pcc.gradeid
  LEFT JOIN {course} course ON course.id = pcc.courseid
      WHERE pcc.pocid = :pocid
        AND pcc.schoolid = :schoolid
        AND pcc.status = 1
   ORDER BY grade.sortorder, grade.name, course.fullname",
    ['pocid' => $pocuserid, 'schoolid' => $schoolid]
);

if (empty($records)) {
    echo json_encode(['html' => get_string('nomappedcourses', 'local_trainer')]);
    exit;
}

$grades = [];
foreach ($records as $record) {
    if (!isset($grades[$record->gradeid])) {
        $grades[$record->gradeid] = [
            'name' => format_string($record->gradename),
            'courses' => [],
        ];
    }

    if (!empty($record->courseid) && !empty($record->coursename)) {
        $grades[$record->gradeid]['courses'][] = format_string($record->coursename);
    }
}

$html = html_writer::start_tag('div', ['class' => 'trainer-mapped-courses']);
foreach ($grades as $grade) {
    $html .= html_writer::tag('strong', $grade['name']);
    if (!empty($grade['courses'])) {
        $items = '';
        foreach ($grade['courses'] as $course) {
            $items .= html_writer::tag('li', $course);
        }
        $html .= html_writer::tag('ul', $items, ['class' => 'mb-2']);
    } else {
        $html .= html_writer::tag('div', get_string('nomappedcourses', 'local_trainer'), ['class' => 'mb-2']);
    }
}
$html .= html_writer::end_tag('div');

echo json_encode(['html' => $html]);
