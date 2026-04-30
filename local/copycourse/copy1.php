<?php
require_once "../../config.php";
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->libdir . '/cronlib.php');

$categoryid = $_GET['CatId'];
$schoolid= $_GET['schoolid'];

header('Content-Type: application/json');
$category = \core_course_category::get($categoryid);
$category_schoolid = \core_course_category::get($schoolid);

$categoriesSchoolChild = $DB->get_record('copyschool', ['categoryid' => $categoryid ,'schoolid'=>$schoolid]);
$courses = $category->get_courses();
if (!empty($courses)) {

    
    if(!$categoriesSchoolChild){
   
        $categoryData = [
            'name' => $category->name,
            'parent' => $schoolid,
            'idnumber' => '', 
            'description' => '', 
            'descriptionformat' => FORMAT_HTML, 
            'visible' => 1 
        ];

    
         $newCategory = \core_course_category::create($categoryData);
        
         if($newCategory->id){
            $copyschool = [
                'categoryid' => $category->id,
                'schoolid' => $schoolid,
                'schoolchildid' => $newCategory->id
            ];
           $getCopyschoolId = $DB->insert_record('copyschool', $copyschool);
          
            foreach ($courses as $key => $value) {
                $course = $DB->get_record('course', ['id' => $value->id], '*', MUST_EXIST);

                $copyCourse = (object) [
                    'courseid' => $course->id,
                    'fullname' => $course->fullname,
                    'shortname' => $course->shortname,
                    'category' => $newCategory->id,
                    'visible' => $course->visible,
                    'startdate' => $course->startdate,
                    'enddate' => $course->enddate,
                    'idnumber' => $course->idnumber,
                    'userdata' => '0',
                    'keptroles' => []
                ];
                $success = \copy_helper::create_copy($copyCourse);
                if (!$success) {
                    $OUTPUT->notification('Failed to copy the course to category: ' . $newCategory->name, \core\output\notification::NOTIFY_ERROR);
                }

                ignore_user_abort(true);
                $cron_command = 'php ' . escapeshellarg($CFG->dirroot . '/admin/cli/cron.php') . ' > /dev/null 2>&1 &';
                exec($cron_command);
                $copycourse = [
                    'copyschoolid' => $newCategory->id,
                    'courseid' => $value->id];
                 $DB->insert_record('copycourse', $copycourse);


  
            }


         }



        }
        else{
// echo "hello";
// echo $categoriesSchoolChild->schoolid;
// die;
                
               $childCategories = $DB->get_record('copyschool', ['categoryid' => $categoryid,'schoolid'=>$schoolid]);
                $copyCourseAllIds = $DB->get_records('copycourse', ['copyschoolid' => $childCategories->schoolchildid]);

   
                // Initialize an empty array to store course IDs
                $courseArray = [];

                // Loop through each record and collect course IDs
                foreach ($copyCourseAllIds as $value) {
                    $courseArray[] = $value->courseid;
                }
            $allCourseid=[];
            foreach ($courses as $key => $value) {
                $allCourseid[]=$value->id;
            }
             $uniqueValues = array_diff($allCourseid, $courseArray);
            // print_r($allCourseid);
            // echo "</br>";
            // print_r($courseArray);
            if($uniqueValues){
            //   echo "asd";
                foreach ($uniqueValues as $key => $value) {
                    $course = $DB->get_record('course', ['id' => $value], '*', MUST_EXIST);
                 
                    $copyCourse = (object) [
                        'courseid' => $course->id,
                        'fullname' => $course->fullname,
                        'shortname' => $course->shortname,
                        'category' =>  $childCategories->schoolchildid,
                        'visible' => $course->visible,
                        'startdate' => $course->startdate,
                        'enddate' => $course->enddate,
                        'idnumber' => $course->idnumber,
                        'userdata' => '0',
                        'keptroles' => []
                    ];
                    $success = \copy_helper::create_copy($copyCourse);
                    if (!$success) {
                        echo $OUTPUT->notification('Failed to copy the course to category: ' . $category->name, \core\output\notification::NOTIFY_ERROR);
                    }
    
                    ignore_user_abort(true);
                    $cron_command = 'php ' . escapeshellarg($CFG->dirroot . '/admin/cli/cron.php') . ' > /dev/null 2>&1 &';
                    exec($cron_command);
            
                    $copycourse = [
                        'copyschoolid' => $childCategories->schoolchildid,
                        'courseid' => $value,
                    ];
                     $DB->insert_record('copycourse', $copycourse);

                }
                $sql = "UPDATE {course} SET fullname = TRIM(SUBSTRING_INDEX(fullname, 'copy', 1)) WHERE fullname LIKE '%copy%'";
$DB->execute($sql);

            }


        }

}


// else{

//     echo "no course found";
// }