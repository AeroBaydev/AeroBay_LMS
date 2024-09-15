<?php
require_once('../../config.php');
global $CFG, $DB;

$parent = required_param('parent', PARAM_INT);

$subcategories = $DB->get_records('course_categories', ['parent' => $parent]);

$result = [];
foreach ($subcategories as $subcategory) {
    $result[$subcategory->id] = $subcategory->name;
}

echo json_encode($result);
header('Content-Type: application/json');

