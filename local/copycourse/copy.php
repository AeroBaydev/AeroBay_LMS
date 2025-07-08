<?php
require_once "../../config.php";
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->libdir . '/cronlib.php');

 $categoryid = $_GET['CatId']; //grade id sub categoryid
$schoolid= $_GET['schoolid']; //parent categoryid id
$courseid= $_GET['option'];
header('Content-Type: application/json');
global $DB,$USER;
// $category = \core_course_category::get($categoryid);
// $categorymain = $DB->get_record('course_categories', ['name' => $category->name ,'parent'=>171]);
// $category_schoolid = \core_course_category::get($categorymain->id);
// $maincourses = $category_schoolid->get_courses();
if (isset($_SESSION['userIdPoc'])) {
    $userid=$_SESSION['userIdPoc'];
  
 }
 else{
    $userid= $USER->id;
 }

// $poc_session_date_id = $DB->get_record('poc_session_date', ['pocid' => $userid,'status'=>1], '*', MUST_EXIST);

// $checkCourseCopy = $DB->record_exists('poc_copy_course',  ['pocid' => $userid,'status'=>1,'gradeid'=>$categoryid,'sessionid'=>$poc_session_date_id->id]);

//  $checkCourse = $DB->get_record('course', ['category' => $categoryid]);


if (!empty($courseid )) {

    $checkCourse = $DB->get_records('course', ['category' => $categoryid]);

    $course_exit = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    
        foreach ($checkCourse as $key => $value) {
            if($value->fullname==$course_exit->fullname){
                   
                $response = [
                    'status' => 'success', // or 'error'
                    'message' => ' Same Course not copy .' // or an error message
                ];
                
                echo json_encode($response);
            
                    exit();




            }     

        }


                $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
                $copyCourse = (object) [
                    'courseid' => $course->id,
                    'fullname' => $course->fullname,
                    'shortname' => $course->shortname,
                    'category' => $categoryid,
                    'visible' => $course->visible,
                    'startdate' => $course->startdate,
                    'enddate' => $course->enddate,
                    'idnumber' => $course->idnumber,
                    'timecreated'=> time(),
                    'userdata' => '0',
                    'keptroles' => []
                ];
               $success = \copy_helper::create_copy($copyCourse);
                
            
             

               if (isset($success)) {
                // $coursie = $DB->get_record('backup_controllers', ['backupid' => $success['restoreid']]);
                // $poc_session_date_id = $DB->get_record('poc_session_date', ['pocid' => $userid,'status'=>1], '*', MUST_EXIST);
                // $record = new stdClass();
                // $record->schoolid = $schoolid;
                // $record->gradeid = $categoryid;
                // $record->courseid = $coursie->itemid;
                // $record->sessionid = $poc_session_date_id->id;
                // $record->pocid = $userid;
                // $record->status = 1;
                // $insertedId = $DB->insert_record('poc_copy_course', $record);
            }

                ignore_user_abort(true);
                $cron_command = 'php ' . escapeshellarg($CFG->dirroot . '/admin/cli/cron.php') . ' > /dev/null 2>&1 &';
                
                exec($cron_command);
                if (!$success) {
                    $OUTPUT->notification('Failed to copy the course to category: ' . $newCategory->name, \core\output\notification::NOTIFY_ERROR);
                }
                else{
                    $response['status'] = 'success';
                    $response['message'] = 'Course copy action performed successfully.';
                    echo json_encode($response);
    
                        exit();
                }
               
          
}
else{
    $response = [
        'status' => 'success', // or 'error'
        'message' => 'you have already copy course current session.' // or an error message
    ];
    
    echo json_encode($response);

        exit();
}


