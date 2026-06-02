<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');
require_login();

if (isset($_POST['gradeid'])) {
    $gradeid = clean_param($_POST['gradeid'], PARAM_INT);
    if (local_pocschool_is_trainer_user()) {
        // removed trainer grade dependency
        $schoolid = (int) $DB->get_field('course_categories', 'parent', ['id' => $gradeid]);
        if (empty($schoolid) || !local_pocschool_user_can_access_school($schoolid)) {
            echo json_encode(['html3' => '<option>Select course</option>']);
            exit;
        }
    }

    $where = "c.category = :gradeid";
    $params = ['gradeid' => $gradeid];
    $trainercourses = local_pocschool_get_trainer_course_ids();
    if (local_pocschool_is_trainer_user()) {
        if (empty($trainercourses)) {
            echo json_encode(['html3' => '<option>Select course</option>']);
            exit;
        }

        // trainer visibility by school mapping
        list($coursesql, $courseparams) = $DB->get_in_or_equal($trainercourses, SQL_PARAMS_NAMED, 'fetchcourse');
        $where .= " AND c.id {$coursesql}";
        $params += $courseparams;
    }

    $courses = $DB->get_records_sql("SELECT c.fullname, c.id FROM {course} c WHERE {$where}", $params);

    $html3='<option>Select course</option>';
    foreach($courses as $course) {
        $html3 .= "<option value='$course->id' >$course->fullname</option>";
    }
    $schoolarr=[];
    $schoolarr['html3']= $html3;

    echo json_encode($schoolarr);
    exit;
}
