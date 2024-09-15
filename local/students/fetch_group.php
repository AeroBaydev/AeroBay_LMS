<?php
require_once('../../config.php');

if (isset($_POST['courseid'])) {
    $courseid = $_POST['courseid'];
    $sections=$DB->get_records_sql("SELECT s.name,s.id FROM {groups} as s WHERE s.courseid = $courseid");

    $html2='<option>Select Section</option>';
    foreach($sections as $section) {
        $html2 .= "<option value='$section->id' >$section->name</option>";
    }
    $schoolarr=[];
    $schoolarr['html2']= $html2;

    echo json_encode($schoolarr);
    exit;
}