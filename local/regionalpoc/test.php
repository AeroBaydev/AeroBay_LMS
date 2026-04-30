<?php
require_once('../../config.php');

// var_dump($_POST);die;

if (isset($_POST['id'])) {
    $schoolid = $_POST['id'];
    $cateogry = $DB->get_records_sql("SELECT c.name,c.id FROM aero_course_categories c JOIN aero_course_categories p ON c.parent = p.id WHERE p.id = $schoolid");
    $html='<option>Select Class</option>';
    foreach($cateogry as $cat) {
        $html .= "<option value='$cat->id' >$cat->name</option>";
    }
    // var_dump($cateogry);
    // die;
    echo json_encode($html);
    exit;
}