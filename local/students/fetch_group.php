<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');
require_login();

if (isset($_POST['courseid'])) {
    $courseid = clean_param($_POST['courseid'], PARAM_INT);
    $trainercourses = local_pocschool_get_trainer_course_ids();
    if (local_pocschool_is_trainer_user() && !empty($trainercourses) && !in_array((int)$courseid, $trainercourses, true)) {
        echo json_encode(['html2' => '<option>Select Section</option>']);
        exit;
    }

    $sections = $DB->get_records_sql(
        "SELECT s.name, s.id FROM {groups} s WHERE s.courseid = :courseid",
        ['courseid' => $courseid]
    );

    $html2='<option>Select Section</option>';
    foreach($sections as $section) {
        $html2 .= "<option value='$section->id' >$section->name</option>";
    }
    $schoolarr=[];
    $schoolarr['html2']= $html2;

    echo json_encode($schoolarr);
    exit;
}
