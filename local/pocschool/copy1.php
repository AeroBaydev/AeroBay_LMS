<?php
require_once "../../config.php";
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->libdir . '/cronlib.php');

$categoryid = $_GET['CatId'];
$schoolid= $_GET['schoolid'];
header('Content-Type: application/json');
$category = \core_course_category::get($categoryid);
//$categoriesSchoolChild = $DB->get_record('course_categories', ['id' => $categoryid]);
$categorymain = $DB->get_record('course_categories', ['name' => $category->name ,'parent'=>171]);
$category_schoolid = \core_course_category::get($categorymain->id);
$maincourses = $category_schoolid->get_courses();

        //school grade
        $category_grade_school = \core_course_category::get($categoryid);
        $maincourses_grade = $category_grade_school->get_courses();
        $schoolGrade=[]; 
    foreach ($maincourses_grade as $key => $value) {
        $schoolGrade[]= $value->fullname;
    }



if (!empty($maincourses)) {
    // print_r($maincourses);
    // echo "/<br>";
   
            foreach ($maincourses as $key => $value) {
                if(!in_array($value->fullname,$schoolGrade)){
                $course = $DB->get_record('course', ['id' => $value->id], '*', MUST_EXIST);
                $copyCourse = (object) [
                    'courseid' => $course->id,
                    'fullname' => $course->fullname,
                    'shortname' => $course->shortname,
                    'category' => $categoryid,
                    'visible' => $course->visible,
                    'startdate' => $course->startdate,
                    'enddate' => $course->enddate,
                    'idnumber' => $course->idnumber,
                    'userdata' => '0',
                    'keptroles' => []
                ];
                $success = \copy_helper::create_copy($copyCourse);
                  }
                  else{
                    $response['status'] = 'success';
                    $response['message'] = 'Course found and action performed successfully.';
                    echo json_encode($response);
    
                        exit();


                  }
                if (!$success) {
                    $OUTPUT->notification('Failed to copy the course to category: ' . $newCategory->name, \core\output\notification::NOTIFY_ERROR);
                }

                ignore_user_abort(true);
                $cron_command = 'php ' . escapeshellarg($CFG->dirroot . '/admin/cli/cron.php') . ' > /dev/null 2>&1 &';
                
                exec($cron_command);
               
                

  
            }
            $response['status'] = 'success';
            $response['message'] = 'Course found and action performed successfully.';
            echo json_encode($response);

                exit();

}
else{
    $response['status'] = 'error';
    $response['message'] = 'Course not found and action performed unsuccessfully.';
    echo json_encode($response);
exit();
                
}

