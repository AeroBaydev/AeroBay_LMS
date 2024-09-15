<?php
require_once('../../config.php');

if (isset($_POST['gradeid'])) {
    $gradeid = $_POST['gradeid'];
    $courses=$DB->get_records_sql("SELECT c.fullname,c.id FROM {course} as c WHERE c.category = $gradeid");

    $html3='<option>Select course</option>';
    foreach($courses as $course) {
        $html3 .= "<option value='$course->id' >$course->fullname</option>";
    }
    $schoolarr=[];
    $schoolarr['html3']= $html3;

    echo json_encode($schoolarr);
    exit;
}