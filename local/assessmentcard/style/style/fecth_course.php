<?php
require_once('../../../config.php');

if (isset($_POST['gradeid']) && $_POST['gradeid']!=0) {
    $gradeid = $_POST['gradeid'];
    $courses=$DB->get_records_sql("SELECT c.id, c.fullname FROM {course} as c WHERE c.visible=1 and c.category = $gradeid");

    $html3='<option>Select a course</option>';
    foreach($courses as $course) {
        $html3 .= "<option value='$course->id' >$course->fullname</option>";
    }
    $schoolarr=[];
    $schoolarr['html3']= $html3;

    echo json_encode($schoolarr);
    exit;
}