<?php
require_once('../../config.php');
global $DB;

$category = required_param('category', PARAM_INT);

$courses = $DB->get_records('course', ['category' => $category]);

$result = [];
foreach ($courses as $course) {
    $result[$course->id] = $course->fullname;
}

echo json_encode($result);
header('Content-Type: application/json');
