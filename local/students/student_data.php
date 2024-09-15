<?php
require_once('../../config.php');
if (isset($_POST['id'])) {
    $id = optional_param('id', 0, PARAM_INT);

    $schoolid = $_POST['id'];

    $cateogry = $DB->get_records_sql("SELECT c.name,c.id FROM {course_categories} c WHERE c.parent = $schoolid");

    $html = '<option>Select Grade</option>';
    $schoolarr = [];


    foreach ($cateogry as $cat) {
        $html .= "<option value='$cat->id' >$cat->name</option>";
    }

    $schoolarr['html'] = $html;

    echo json_encode($schoolarr);
    exit;
}
