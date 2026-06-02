<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
global $DB;
require_login();
require_admin();
$name = required_param('name', PARAM_TEXT);

// $category = $DB->get_record('course_categories', array('id' => $categoryid), '*', MUST_EXIST);
$sql = 'SELECT * FROM {course_categories} cc WHERE cc.name = :name';
$params = array('name' => $name);
$category = $DB->get_record_sql($sql, $params);
$category_instance = \core_course_category::get($category->id);

// Determine the action based on current status
if ($category->visible) {
    \core_course\management\helper::action_category_hide($category_instance);
    $new_status = 0;
} else {
    \core_course\management\helper::action_category_show($category_instance);
    $new_status = 1;
}
 
redirect($CFG->wwwroot . '/local/school/school_manage.php');
