<?php

require_once('../../config.php');

$categoryid = optional_param('categoryid', 0, PARAM_INT);
global $DB;
$empty = new stdClass();
$empty->id = 0;
$empty->name = 'Select Class';
$res = ['0' => $empty];
if ($categoryid != 0) {
    $subcategories = $DB->get_records_sql("select id,name from {course_categories} cc where cc.parent=$categoryid");
    foreach ($subcategories as $subcategory) {
        $obj = new stdClass();
        $obj->id = $subcategory->id;
        $obj->name = $subcategory->name;
        $res[] = $obj;
    }
    $response = ['data' => $res];
} else {
    $response = false;
}
echo json_encode($response);
exit();
