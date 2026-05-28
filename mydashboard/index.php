<?php

require_once('../config.php');
require_once($CFG->dirroot . '/local/timetable/lib.php');
require_once($CFG->dirroot . '/local/dashboard/lib.php');


global $DB, $USER;
require_login();

// Set up the page layout and metadata.
$PAGE->set_pagelayout('course');
$PAGE->set_url('/mydashboard/index.php');
$PAGE->set_title('My Dashboard');
$PAGE->set_heading('');
$somdata = [];
$data = [];
// Role ID for 'Student' (default is 5, verify in mdl_role table).
$studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
$user = core_user::get_user($USER->id);
$data['username']=$user->username;
// Initialize template data.

// Fetch all courses where the user is enrolled as a Student.
$sql = "
    SELECT DISTINCT ctx.id AS contextid
    FROM {context} ctx
    JOIN {role_assignments} ra ON ra.contextid = ctx.id
    WHERE ra.userid = :userid
      AND ra.roleid = :roleid
      AND ctx.contextlevel = :contextlevel
";
$params = [
    'userid' => $USER->id,
    'roleid' => $studentroleid,
    'contextlevel' => CONTEXT_COURSE, // Course-level context.
];

$courses = $DB->get_records_sql($sql, $params);

// Check if the user is a Student in any course.
if (!empty($courses)) {
    // Render the student dashboard.
      echo $OUTPUT->header();
 

    $student_records = $DB->get_record('student', ['userid' => $USER->id]);
     $student_Course = $DB->get_record('poc_copy_course', ['gradeid' => $student_records->gradeid,'schoolid'=>$student_records->schoolid]);
    $courseid = $student_Course->courseid; 
    $coursename = get_course_name($courseid);

    $gradeid=$student_records->gradeid;
    $course_categories = $DB->get_record('course_categories', ['id' => $gradeid]);
    $grade_number = preg_replace('/[^0-9]/', '', $course_categories->name); 
    $school_number=$student_records->schoolid;

    $sql = "SELECT * FROM mdl_news 
        WHERE FIND_IN_SET(:school_id, schoolid) > 0 
        AND FIND_IN_SET(:grade_id, gradeid) 
        AND status = 1 
        ORDER BY id DESC";

$params = [
    'school_id' => $school_number,
    'grade_id' => $grade_number
];
// echo "SELECT * FROM mdl_news 
//         WHERE FIND_IN_SET(:school_id, schoolid) > 0 
//         AND FIND_IN_SET(:grade_id, gradeid) ";
//         die;
 $news_records = $DB->get_records_sql($sql, $params);


// print_r($data);
// die;
    
    $timetable_records = $DB->get_records('timetable', ['schoolid' => $student_records->schoolid, 'gradeid' => $student_records->gradeid]);

    // Process the timetable records into an associative array
    $checked_timetable = [];
    foreach ($timetable_records as $record) {
        $checked_timetable[$record->day][$record->period] = true;
    }
    
    // Define days and periods
    $days = [
        'M' => 'Monday',
        'T' => 'Tuesday',
        'W' => 'Wednesday',
        'Th' => 'Thursday',
        'F' => 'Friday',
        'S' => 'Saturday'
    ];
  

    $periods = ['1', '2', '3', '4', '5','6','7','8','9'];
    
    
    foreach ($days as $short => $dayname) {
        $row = ['day' => $short, 'periods' => []];
        
        foreach ($periods as $period) {
            $checked = isset($checked_timetable[$dayname][$period]) ? $coursename : '--';
            $row['periods'][] = ['period' => $period, 'checked' => $checked];
        }
        
        $data['days'][] = $row;
    }
    
    // $correct_timestamp = strtotime('2025-04-01 00:00:00 UTC');  
    // echo $correct_timestamp;  // Should output 1743465600
    

    // Compute the current Indian fiscal year (Apr 1 – Mar 31) dynamically.
    $now          = new DateTime('now', core_date::get_user_timezone_object());
    $currentYear  = (int) $now->format('Y');
    $currentMonth = (int) $now->format('n');
    $fy_start_year = ($currentMonth >= 4) ? $currentYear : $currentYear - 1;
    $fy_end_year   = $fy_start_year + 1;

    $start_timestamp = mktime(0,  0,  0,  4,  1,  $fy_start_year);
    $end_timestamp   = mktime(23, 59, 59, 3,  31, $fy_end_year);

$sql = "SELECT ast.id, att.date, ast.status
          FROM {attendance_student} ast
          JOIN {attendance} att ON att.id = ast.attendanceid
         WHERE ast.studentid = ?
           AND att.date BETWEEN ? AND ?
         ORDER BY att.date ASC";

$result = $DB->get_records_sql($sql, [$USER->id, $start_timestamp, $end_timestamp]);
// Define all months
$allMonths = [
    'April', 'May', 'June', 'July', 'August', 'September', 
    'October', 'November', 'December', 'January', 'February', 'March'
];





// Initialize arrays with zero values
// Initialize arrays with zero values
$offlineValues = array_combine($allMonths, array_fill(0, count($allMonths), 0));
$onlineValues = array_combine($allMonths, array_fill(0, count($allMonths), 0));


foreach ($result as $row) {
    // Create a DateTime object and set the timestamp from the record
    $date = new DateTime();
    $date->setTimestamp($row->date);
    
    // Get the month name (e.g., 'March', 'April')
    $month = $date->format('F');
    
    // Get the attendance status (e.g., 'P', 'L')
    $status = trim($row->status);
    
    // Ensure the month exists in the predefined month list
    if (isset($offlineValues[$month])) {
        // Accumulate the counts based on the status
        if ($status === 'A') {
            $offlineValues[$month] += 1;  // Increment the count for 'L' (Late)
        } elseif ($status === 'P') {
            $onlineValues[$month] += 1;  // Increment the count for 'P' (Present)
        }
    }
}

// Debugging Output:
// echo "<pre>";
// print_r($offlineValues);
// print_r($onlineValues);
// echo "</pre>";
// die();

// Prepare Mustache data
// $data = ['news' => !empty($news_records) ? array_values($news_records) : []];
// $news_records = $DB->get_records_sql($sql, $params) ?? [];

// print_r($data['attendance']);

// echo  $OUTPUT->render_from_template('local_mydashboard/welcome_student', $somdata);

// $courseid = required_param('courseid', PARAM_INT);
$userid = $USER->id; // Or use required_param('userid', PARAM_INT) if needed

$sql = "SELECT q.name AS quiz_name, qg.grade AS score
        FROM {quiz_grades} qg
        JOIN {quiz} q ON qg.quiz = q.id
        WHERE q.course = :courseid AND qg.userid = :userid";

$params = ['courseid' => $courseid, 'userid' => $userid];
$results = $DB->get_records_sql($sql, $params);



$quizzes = [];
foreach ($results as $result) {
    $quizzes[] = [
        'name' => $result->quiz_name,
        'score' => $result->score
    ];
}

// Prepare data for Mustache
// $templatecontext = [
   
// ];





$data = [
    'timetable' => [
        'days' => $data['days'] // Assuming $data['days'] is already populated
    ],
    'attendance' => [
        'offlineValues' => json_encode(array_values($offlineValues)),
        'onlineValues' => json_encode(array_values($onlineValues)),
        'months' => json_encode($allMonths)
    ],
    'newslist' => array_values((array) $news_records),
    'milestones' => [],  // Ensures it's always an array
    'quizzes' => $quizzes,
    'jsondata' => json_encode($quizzes) // JSON format for JS
];

// print_r($data);
// die;
//assessmentcard code




$milestones = $DB->get_records('assessment_milestone', ['userid' => $USER->id, 'courseid' => $courseid]);

$matched_image = null;
$matched_id=[];
foreach ($milestones as $milestone) {
   $percentage = isset($milestone->percentage) ? floatval($milestone->percentage) : 0; // Ensure float conversion

    // if ($percentage >= 95) {
    //     // Get the first matching image for 95 and above
    //     $above_95_image = $DB->get_record_sql("
    //         SELECT imgpath 
    //         FROM {assessmentcard}
    //         WHERE rang1 >= 95
    //         ORDER BY rang1 ASC 
    //         LIMIT 1
    //     ");
    
    //     if ($above_95_image) {
    //         $matched_image = $above_95_image->imgpath;
    //     } else {
    //         $matched_image = "badgesimg/default_95_above.png"; // Default image if no match found
    //     }
        
    //     break;
    // }
    


    // Get the best matching assessment card (Fix: Pass 2 parameters for SQL)
    $assessment = $DB->get_record_sql("
        SELECT * 
        FROM {assessmentcard}
        WHERE rang1 <= :percentage1 AND rang2 > :percentage2
        ORDER BY (rang2 - rang1) ASC, id ASC 
       
    ", ['percentage1' => $percentage, 'percentage2' => $percentage]);

   

    if ($assessment) {
        $matched_id[] = $assessment->id;
    // come Array ( [0] => 35 [1] => 35 ) this are same same so 
   // i want get in  mdl_assessmentcard where is parent id 35 and get image path
    }
}
$newArray = array_count_values($matched_id);
$student_fullname=fullname($USER);
foreach ($newArray as $key => $value) {
    switch ($value) {
        case 1:
            $milestones_card_data = $DB->get_record('assessmentcard', ['id' => $key]);
            $data['milestones'][] = [
                'imgpath' => $milestones_card_data->imgpath,
                'text' => $milestones_card_data->name // Assuming 'name' is the text you want
            ];
            break;
        case 2:
            $text="Dear $student_fullname, twice is amazing! Your consistent efforts are truly inspiring.";          
            $milestones_card_data = $DB->get_record('assessmentcard', ['parentid' => $key]);
            $data['milestones'][] = [
                'imgpath' => $milestones_card_data->imgpath,
                'text' => $text // Assuming 'name' is the text you want
            ];
            break;
        case 3:
            $text="Dear $student_fullname, three times the brilliance! Your dedication shines through."; 
            $milestones_card_data = $DB->get_record('assessmentcard', ['parentid' => $key]);
            $data['milestones'][] = [
                'imgpath' => $milestones_card_data->imgpath,
                'text' => $text // Assuming 'name' is the text you want
            ];
            break;
        case 4:
            $text="Dear $student_fullname, four in a row! Your excellence is unparalleled.";
            $milestones_card_data = $DB->get_record('assessmentcard', ['parentid' => $key]);
            $data['milestones'][] = [
                'imgpath' => $milestones_card_data->imgpath,
                'text' => $text // Assuming 'name' is the text you want
            ];
            break;
        case 5:
            $text="Dear $student_fullname,  five in a row! Your excellence is unparalleled.";
            $milestones_card_data = $DB->get_record('assessmentcard', ['parentid' => $key]);
            $data['milestones'][] = [
                'imgpath' => $milestones_card_data->imgpath,
                'text' => $text // Assuming 'name' is the text you want
            ];
            break;
        case 6:
            $text="Dear $student_fullname, six in a row! Your excellence is unparalleled.";
            $milestones_card_data = $DB->get_record('assessmentcard', ['parentid' => $key]);
            $data['milestones'][] = [
                'imgpath' => $milestones_card_data->imgpath,
                'text' => $text // Assuming 'name' is the text you want
            ];
            break;
        default:
             $text="";
            $milestones_card_data = $DB->get_record('assessmentcard', ['id' => $key]);
            break;
    }

    
}
//course completed

$course = $DB->get_record('course', ['id' => $courseid]);
$progress = \core_completion\progress::get_course_progress_percentage($course, $USER->id);


if ($progress >= 100) { 
    
    $sessioncard_card_data = $DB->get_record('sessioncard', ['percentages' =>$progress]);
    $data['milestones'][] = [
        'imgpath' => $sessioncard_card_data->imgpath,
        'text' => $sessioncard_card_data->name 
    ];

} elseif ($progress >= 75) {
    $sessioncard_card_data = $DB->get_record('sessioncard', ['percentages' =>$progress]);
    $data['milestones'][] = [
        'imgpath' => $sessioncard_card_data->imgpath,
        'text' => $sessioncard_card_data->name 
    ];
} elseif ($progress >= 50) {
    $sessioncard_card_data = $DB->get_record('sessioncard', ['percentages' =>$progress]);
    $data['milestones'][] = [
        'imgpath' => $sessioncard_card_data->imgpath,
        'text' => $sessioncard_card_data->name 
    ];
} elseif ($progress >= 25) {
    $sessioncard_card_data = $DB->get_record('sessioncard', ['percentages' =>$progress]);
    $data['milestones'][] = [
        'imgpath' => $sessioncard_card_data->imgpath,
        'text' => $sessioncard_card_data->name 
    ];
} else {
    $data['milestones'][] = [
        'imgpath' => "",
        'text' => "" 
    ];
}
$data['username'] = $user->username;
$data['userfullname'] = $user->firstname." ".$user->lastname;

if (empty($user->lastlogin))  {
    // If 'firstaccess' is empty, it means the user hasn't accessed before, thus it's their first login.
    $data['first_login'] = 1; // Set to 1 (true) for first login
} else {
    // If 'firstaccess' has a value, they've accessed before.
    $data['first_login'] = 0; // Set to 0 (false) for not first login
}

// echo $user->firstaccess;
// //echo '<pre>'; print_r($data); echo '</pre>';
//  print_r($user);
//   die;
   // echo  $OUTPUT->render_from_template('local_mydashboard/welcome_student', $somdata);
    echo $OUTPUT->render_from_template('local_mydashboard/studentdashboard', $data);
    echo $OUTPUT->footer();
} else {
    // Render the shared admin dashboard for admins and school-scoped POCs.
    echo $OUTPUT->header();
    if (is_siteadmin()) {
        echo $OUTPUT->render_from_template('local_mydashboard/admindashboard', array_merge($somdata, local_dashboard_get_admin_stats_context()));
    } else if (local_dashboard_is_pocschool_user((int) $USER->id)) {
        $scope = local_dashboard_get_pocschool_scope((int) $USER->id);
        echo $OUTPUT->render_from_template('local_mydashboard/admindashboard', array_merge($somdata, local_dashboard_get_admin_stats_context($scope)));
    } else {
        echo $OUTPUT->render_from_template('local_mydashboard/mydashboard', $somdata);
    }
    echo $OUTPUT->footer();
}
