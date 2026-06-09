<?php

require_once('../config.php');
require_once($CFG->dirroot . '/local/timetable/lib.php');
require_once($CFG->dirroot . '/local/dashboard/lib.php');
require_once($CFG->dirroot . '/local/mydashboard/lib.php');


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
    $gradename = $course_categories ? format_string($course_categories->name) : '';
    $school_number=$student_records->schoolid;
    $school_category = $DB->get_record('course_categories', ['id' => $school_number]);
    $schoolname = $school_category ? format_string($school_category->name) : '';

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
	    'jsondata' => json_encode($quizzes), // JSON format for JS
        'gradename' => $gradename,
        'schoolname' => $schoolname
		];

$data = array_merge($data, local_mydashboard_get_student_progress_context($student_records));

$data['sessionname'] = '';
if (!empty($data['learningpath']['active_session']) && $data['learningpath']['active_session'] !== '—') {
    $data['sessionname'] = $data['learningpath']['active_session'];
}


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
}
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

// ── TRAINER AVATAR ──
$data['trainerimageurl'] = '';
$data['trainersessionstaught'] = 0;
$data['trainerstatus'] = 'Offline';
$data['trainercardschoolname'] = 'School Not Assigned';
$data['trainerfullname'] = '';
$data['hastrainer'] = false;
$data['trainerid'] = 0;
$data['schoolid'] = $school_number;
$data['gradeid'] = $gradeid;
$data['sesskey'] = sesskey();
$data['trainer_avg_rating'] = '0.0';
$data['trainer_count_rating'] = 0;

if (!empty($school_number)) {
    // Resolve trainer card school name
    if ($DB->get_manager()->table_exists('trainer_course_mapping')) {
        $trainercourseschools = $DB->get_fieldset_sql(
            "SELECT DISTINCT cc.name
               FROM {trainer_course_mapping} tcm
               JOIN {course_categories} cc ON cc.id = tcm.schoolid
              WHERE tcm.schoolid = :schoolid
                AND tcm.status = 1",
            ['schoolid' => $school_number]
        );
        if (!empty($trainercourseschools)) {
            $data['trainercardschoolname'] = count($trainercourseschools) === 1
                ? format_string(reset($trainercourseschools))
                : 'Multiple Schools';
        }
    }
    if ($data['trainercardschoolname'] === 'School Not Assigned') {
        $trainerrec = $DB->get_record('trainer', ['schoolid' => $school_number], 'id, schoolid', IGNORE_MULTIPLE);
        if ($trainerrec && !empty($trainerrec->schoolid)) {
            $trainercat = $DB->get_record('course_categories', ['id' => $trainerrec->schoolid], 'name');
            if ($trainercat) {
                $data['trainercardschoolname'] = format_string($trainercat->name);
            }
        }
    }

    // 1. Try trainer table
    $traineruserid = $DB->get_field('trainer', 'userid', ['schoolid' => $school_number], IGNORE_MULTIPLE);
    if (!$traineruserid && !empty($courseid) && $DB->get_manager()->table_exists('trainer_course_mapping')) {
        // 2. Try trainer_course_mapping joined with poc_copy_course
        $sql = "SELECT t.userid 
                  FROM {trainer_course_mapping} tcm 
                  JOIN {trainer} t ON t.userid = tcm.traineruserid 
                 WHERE tcm.courseid = :courseid
                   AND tcm.status = 1";
        $traineruserid = $DB->get_field_sql($sql, ['courseid' => $courseid], IGNORE_MULTIPLE);
    }
    
    if ($traineruserid) {
        if ($traineruser = core_user::get_user($traineruserid)) {
            $data['hastrainer'] = true;
            $data['trainerfullname'] = fullname($traineruser);
            $userpicture = new user_picture($traineruser);
            $userpicture->size = 100;
            $data['trainerimageurl'] = $userpicture->get_url($PAGE)->out(false);
            
            $data['istraineronline'] = false;
            $data['trainerstatus'] = 'Offline';
            $lastaccess = isset($traineruser->lastaccess) ? (int)$traineruser->lastaccess : 0;
            if ($lastaccess > 0) {
                $diff = time() - $lastaccess;
                if ($diff <= 3600) {
                    $data['istraineronline'] = true;
                    $data['trainerstatus'] = 'Online';
                }
            }
        }
        
        if ($DB->get_manager()->table_exists('local_session_progress')) {
            $data['trainersessionstaught'] = (int) $DB->count_records('local_session_progress', [
                'trainerid' => $traineruserid,
                'schoolid' => $school_number,
                'gradeid' => $gradeid,
                'courseid' => $courseid,
                'status' => 'completed'
            ]);
        }
        
        $data['trainerid'] = $traineruserid;
        $data['schoolid'] = $school_number;
        $data['gradeid'] = $gradeid;
        $data['sesskey'] = sesskey();

        if ($DB->get_manager()->table_exists('local_trainer_rating')) {
            $sql = "SELECT AVG(rating) AS avgrating, COUNT(rating) AS countrating FROM {local_trainer_rating} WHERE trainerid = :trainerid";
            $stats = $DB->get_record_sql($sql, ['trainerid' => $traineruserid]);
            $data['trainer_avg_rating'] = $stats->avgrating ? round($stats->avgrating, 1) : '0.0';
            $data['trainer_count_rating'] = (int)$stats->countrating;
        } else {
            $data['trainer_avg_rating'] = '0.0';
            $data['trainer_count_rating'] = 0;
        }
    }
}

// echo $user->firstaccess;
// //echo '<pre>'; print_r($data); echo '</pre>';
//  print_r($user);
//   die;
	   // echo  $OUTPUT->render_from_template('local_mydashboard/welcome_student', $somdata);
    $PAGE->requires->js_call_amd('local_mydashboard/studentdashboard', 'init', [[
        'submiturl' => (new moodle_url('/local/mydashboard/ajax_submit_doubt.php'))->out(false),
        'maxchars' => 1000,
    ]]);
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
    } else if ($trainer = $DB->get_record('trainer', ['userid' => $USER->id])) {
        // Trainer dashboard — isolated, UI demo only.
        $somdata['config'] = ['wwwroot' => $CFG->wwwroot];
        $somdata['loggedinuserfullname'] = fullname($USER);
        
        $initials = '';
        if (!empty($USER->firstname)) { $initials .= mb_substr(trim($USER->firstname), 0, 1); }
        if (!empty($USER->lastname)) { $initials .= mb_substr(trim($USER->lastname), 0, 1); }
        $somdata['loggedinuserinitials'] = !empty($initials) ? mb_strtoupper($initials) : 'TR';
        
        $rolename = 'Trainer';
        if ($roles = $DB->get_records_sql("SELECT r.name, r.shortname FROM {role_assignments} ra JOIN {role} r ON ra.roleid = r.id WHERE ra.userid = :userid ORDER BY r.sortorder ASC", ['userid' => $USER->id], 0, 1)) {
            $role = reset($roles);
            $rolename = !empty($role->name) ? $role->name : ucfirst($role->shortname);
            if (stripos($rolename, 'teacher') !== false) {
                $rolename = 'Trainer';
            }
        }
        $somdata['loggedinuserrole'] = strtoupper($rolename);

        $somdata['hastrainerschool'] = false;
        
        $trainerschoolid = !empty($trainer->schoolid) ? $trainer->schoolid : $DB->get_field('schoolassign', 'schoolid', ['userid' => $USER->id]);
        if (!empty($trainerschoolid)) {
            $schoolcat = $DB->get_record('course_categories', ['id' => $trainerschoolid], 'id, name');
            if ($schoolcat) {
                $somdata['trainerschoolname'] = format_string($schoolcat->name);
                $somdata['hastrainerschool'] = true;
            }
        }
        
        $somdata['trainerstudentcount'] = 0;
        $somdata['studentsaddedthismonth'] = 0;
        if (!empty($trainerschoolid)) {
            $somdata['trainerstudentcount'] = $DB->count_records('student', ['schoolid' => $trainerschoolid]);
            $start_of_month = strtotime('first day of this month 00:00:00');
            $sql_new_studs = "SELECT COUNT(s.id) FROM {student} s JOIN {user} u ON u.id = s.userid WHERE s.schoolid = ? AND u.timecreated >= ?";
            $somdata['studentsaddedthismonth'] = $DB->count_records_sql($sql_new_studs, [$trainerschoolid, $start_of_month]);
        }

        $somdata['todayclassescount'] = 0;
        $somdata['todaycompletedcount'] = 0;
        $trainerassignedschoolid = !empty($trainer->schoolid) ? (int) $trainer->schoolid : 0;
        $somdata['trainerschoolid'] = $trainerassignedschoolid;
        
        if (!empty($trainerassignedschoolid) && $DB->get_manager()->table_exists('timetable')) {
            $timetablecolumns = $DB->get_columns('timetable');
            $todayclasseswhere = "tt.schoolid = :trainerschoolid
                    AND tt.day = :todayday
                    AND tt.period IS NOT NULL
                    AND tt.period <> ''
                    AND EXISTS (
                        SELECT 1
                          FROM {course_categories} gradecat
                         WHERE gradecat.id = tt.gradeid
                           AND gradecat.parent = :trainergradeschoolid
                    )";
            $todayclassesparams = [
                'trainerschoolid' => $trainerassignedschoolid,
                'todayday' => local_dashboard_get_weekday_name(usergetmidnight(time())),
                'trainergradeschoolid' => $trainerassignedschoolid,
            ];

            $somdata['todayclassescount'] = (int) $DB->count_records_sql(
                "SELECT COUNT(DISTINCT tt.id)
                   FROM {timetable} tt
                  WHERE {$todayclasseswhere}",
                $todayclassesparams
            );
        }
        
        // ── Full-week timetable JSON for schedule modal ───────────────────────────
        // Schema: timetable(schoolid INT, gradeid INT, period VARCHAR, day VARCHAR)
        // Grade name  : course_categories.name via gradeid
        // Course name : poc_copy_course (VARCHAR schoolid/gradeid) → course.fullname
        // Period      : stored as '1'–'9' → displayed as Roman numeral
        $somdata['timetablejson'] = '[]';
        if (!empty($trainerassignedschoolid) && $DB->get_manager()->table_exists('timetable')) {
            $period_roman = [
                '1' => 'I',  '2' => 'II',  '3' => 'III', '4' => 'IV',
                '5' => 'V',  '6' => 'VI',  '7' => 'VII', '8' => 'VIII',
                '9' => 'IX', '10' => 'X',
            ];
            $week_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            // Fetch all rows for this school across all days, joined to grade name and course name.
            $all_rows = $DB->get_records_sql(
                'SELECT t.id, t.day, t.period,
                        t.schoolid, t.gradeid,
                        pcc.courseid,
                        cc.name AS gradename,
                        c.fullname AS coursename
                   FROM {timetable} t
              LEFT JOIN {course_categories} cc ON cc.id = t.gradeid
              LEFT JOIN {poc_copy_course} pcc
                     ON CAST(pcc.schoolid AS UNSIGNED) = t.schoolid
                    AND CAST(pcc.gradeid  AS UNSIGNED) = t.gradeid
                    AND pcc.status = 1
              LEFT JOIN {course} c ON c.id = pcc.courseid
                  WHERE t.schoolid = :schoolid
               ORDER BY CAST(t.period AS UNSIGNED)',
                ['schoolid' => $trainerassignedschoolid]
            );

            $courseids = [];
            foreach ($all_rows as $row) {
                $courseid = (int) ($row->courseid ?? 0);
                if ($courseid > 0) {
                    $courseids[$courseid] = $courseid;
                }
            }

            $sectionsbycourse = [];
            if (!empty($courseids)) {
                list($coursesql, $courseparams) = $DB->get_in_or_equal(array_values($courseids), SQL_PARAMS_NAMED, 'mydashcourse');
                $sectionrecords = $DB->get_records_sql(
                    "SELECT cs.id, cs.course, cs.section, cs.name, cs.visible, COUNT(cm.id) AS modulecount
                       FROM {course_sections} cs
                       JOIN {course_modules} cm ON cm.course = cs.course
                        AND cm.section = cs.id
                        AND FIND_IN_SET(cm.id, cs.sequence)
                      WHERE cs.course {$coursesql}
                        AND cs.section > 0
                   GROUP BY cs.id, cs.course, cs.section, cs.name, cs.visible
                   ORDER BY cs.course, cs.section",
                    $courseparams
                );
                foreach ($sectionrecords as $sectionrecord) {
                    $sectioncourseid = (int) $sectionrecord->course;
                    if (!isset($sectionsbycourse[$sectioncourseid])) {
                        $sectionsbycourse[$sectioncourseid] = [];
                    }
                    $sectionnumber = (int) $sectionrecord->section;
                    $sectionsbycourse[$sectioncourseid][] = [
                        'sectionid' => (int) $sectionrecord->id,
                        'sectionnumber' => $sectionnumber,
                        'sectionname' => !empty($sectionrecord->name) ? format_string($sectionrecord->name) : 'Session ' . $sectionnumber,
                        'visible' => (int) $sectionrecord->visible,
                    ];
                }
            }

            $progressbysection = [];
            if (!empty($courseids) && $DB->get_manager()->table_exists('local_session_progress')) {
                list($progresscoursesql, $progresscourseparams) = $DB->get_in_or_equal(array_values($courseids), SQL_PARAMS_NAMED, 'mydashprogresscourse');
                $progressrecords = $DB->get_records_sql(
                    "SELECT sectionid, schoolid, gradeid, courseid, trainerid, status, completeddays, timecompleted
                       FROM {local_session_progress}
                      WHERE schoolid = :progressschoolid
                        AND courseid {$progresscoursesql}",
                    ['progressschoolid' => $trainerassignedschoolid] + $progresscourseparams
                );

                foreach ($progressrecords as $progressrecord) {
                    $progresskey = implode(':', [
                        (int) $progressrecord->schoolid,
                        (int) $progressrecord->gradeid,
                        (int) $progressrecord->courseid,
                        (int) $progressrecord->sectionid,
                    ]);
                    $progressbysection[$progresskey] = [
                        'trainerid' => (int) $progressrecord->trainerid,
                        'status' => !empty($progressrecord->status) ? strtolower(trim((string) $progressrecord->status)) : 'pending',
                        'completeddays' => (int) $progressrecord->completeddays,
                        'timecompleted' => (int) $progressrecord->timecompleted,
                    ];
                }
            }

            // Build day-keyed array.
            $timetable_all = [];
            foreach ($week_days as $wd) {
                $timetable_all[$wd] = [];
            }
            foreach ($all_rows as $row) {
                $day_key = $row->day;
                if (!isset($timetable_all[$day_key])) {
                    $timetable_all[$day_key] = [];
                }
                $period_num = trim((string) $row->period);
                $courseid = (int) ($row->courseid ?? 0);
                $sessions = [];
                foreach ($sectionsbycourse[$courseid] ?? [] as $section) {
                    $progresskey = implode(':', [
                        (int) ($row->schoolid ?? 0),
                        (int) ($row->gradeid ?? 0),
                        $courseid,
                        (int) $section['sectionid'],
                    ]);
                    $progress = $progressbysection[$progresskey] ?? null;
                    $sessions[] = $section + [
                        'status' => $progress['status'] ?? 'pending',
                        'completeddays' => $progress['completeddays'] ?? 0,
                        'timecompleted' => $progress['timecompleted'] ?? 0,
                        'trainerid' => $progress['trainerid'] ?? 0,
                    ];
                }
                $selectedsession = null;
                foreach ($sessions as $session) {
                    if ((int) $session['visible'] === 1 && (int) $session['sectionnumber'] > 0) {
                        $selectedsession = $session;
                        break;
                    }
                }
                $timetable_all[$day_key][] = [
                    'id'         => $row->id,
                    'period'     => isset($period_roman[$period_num])
                                        ? 'Period ' . $period_roman[$period_num]
                                        : 'Period ' . $period_num,
                    'gradename'  => !empty($row->gradename)  ? $row->gradename  : '—',
                    'coursename' => !empty($row->coursename) ? $row->coursename : '—',
                    'gradeid'    => (int) ($row->gradeid ?? 0),
                    'courseid'   => $courseid,
                    'sessions'   => $sessions,
                    'iscompleted'=> $selectedsession && ($selectedsession['status'] ?? 'pending') === 'completed'
                ];
            }

            // Re-align todayclassescount from this dataset (single source of truth).
            $today_day = local_dashboard_get_weekday_name(usergetmidnight(time()));
            $somdata['todayclassescount'] = count($timetable_all[$today_day] ?? []);
            
            // Re-align todaycompletedcount from this dataset for today only.
            $somdata['todaycompletedcount'] = 0;
            if (isset($timetable_all[$today_day])) {
                foreach ($timetable_all[$today_day] as $tt) {
                    if ($tt['iscompleted']) {
                        $somdata['todaycompletedcount']++;
                    }
                }
            }

            // Embed as JSON — all special chars escaped for safe HTML/JS embedding.
            $somdata['timetablejson'] = json_encode(
                $timetable_all,
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            );
        }
        // ── End timetable block ──────────────────────────────────────────────────
        
        // ── Today's Attendance ────────────────────────────────────────────────────────
        $somdata['todayattendancepercent'] = 0;
        $somdata['todaypresentcount']      = 0;
        $somdata['todaytotalcount']        = 0;
        
        if (!empty($trainerassignedschoolid) && $DB->get_manager()->table_exists('attendance') && $DB->get_manager()->table_exists('attendance_student')) {
            $today_midnight = usergetmidnight(time());
            $next_midnight  = $today_midnight + 86400;
            
            $sql = "SELECT
                        SUM(CASE WHEN ast.status = 'P' THEN 1 ELSE 0 END) AS present
                    FROM {attendance} att
                    JOIN {attendance_student} ast ON ast.attendanceid = att.id
                    WHERE att.schoolid = :schoolid
                      AND att.date >= :midnight
                      AND att.date < :nextmidnight";
                      
            $params = [
                'schoolid' => $trainerassignedschoolid,
                'midnight' => $today_midnight,
                'nextmidnight' => $next_midnight
            ];
            
            $att_record = $DB->get_record_sql($sql, $params);
            
            // Total students in the entire school across all grades
            $total = isset($somdata['trainerstudentcount']) ? (int) $somdata['trainerstudentcount'] : 0;
            $present = ($att_record && !empty($att_record->present)) ? (int) $att_record->present : 0;
            
            $somdata['todaytotalcount'] = $total;
            $somdata['todaypresentcount'] = $present;
            $somdata['todayattendancepercent'] = ($total > 0) ? round(($present / $total) * 100) : 0;
        }
        // ── Grade-wise Attendance (This Month) ────────────────────────────────────────
        $somdata['gradewiseattendance'] = [];
        if (!empty($trainerassignedschoolid) && $DB->get_manager()->table_exists('attendance') && $DB->get_manager()->table_exists('attendance_student')) {
            $start_of_month = strtotime('first day of this month 00:00:00');
            $start_of_next_month = strtotime('first day of next month 00:00:00');
            
            $sql = "SELECT 
                        cc.id, 
                        cc.name as gradename,
                        COUNT(ast.id) as total,
                        SUM(CASE WHEN ast.status = 'P' THEN 1 ELSE 0 END) as present
                    FROM {course_categories} cc
                    LEFT JOIN {attendance} att ON att.gradeid = cc.id 
                          AND att.schoolid = :schoolid 
                          AND att.date >= :start_of_month 
                          AND att.date < :start_of_next_month
                    LEFT JOIN {attendance_student} ast ON ast.attendanceid = att.id
                    WHERE cc.parent = :parent_schoolid
                    GROUP BY cc.id, cc.name
                    ORDER BY (SUM(CASE WHEN ast.status = 'P' THEN 1 ELSE 0 END) / NULLIF(COUNT(ast.id), 0)) DESC, cc.name ASC
                    LIMIT 4";
            
            $params = [
                'schoolid' => $trainerassignedschoolid,
                'parent_schoolid' => $trainerassignedschoolid,
                'start_of_month' => $start_of_month,
                'start_of_next_month' => $start_of_next_month
            ];
            
            $records = $DB->get_records_sql($sql, $params);
            
            $colors = ['primary', 'cyan', 'green', 'amber', 'rose', 'indigo'];
            $i = 0;
            foreach ($records as $r) {
                $pct = ($r->total > 0) ? round(($r->present / $r->total) * 100) : 0;
                $color = $colors[$i % count($colors)];
                $somdata['gradewiseattendance'][] = [
                    'gradename' => format_string($r->gradename),
                    'percent' => $pct,
                    'colorclass' => $color
                ];
                $i++;
            }
        }

        // ── Trainer Ratings ───────────────────────────────────────────────────────────
        $somdata['hasratings'] = false;
        if ($DB->get_manager()->table_exists('local_trainer_rating')) {
            $overall = $DB->get_record_sql("SELECT AVG(rating) as avg, COUNT(*) as count FROM {local_trainer_rating} WHERE trainerid = :tid", ['tid' => $USER->id]);
            if ($overall && $overall->count > 0) {
                $somdata['hasratings'] = true;
                $somdata['rating_avg'] = round($overall->avg, 1);
                $somdata['rating_count'] = $overall->count;
                
                $star_counts = [5=>0, 4=>0, 3=>0, 2=>0, 1=>0];
                $stars_sql = $DB->get_records_sql("SELECT rating, COUNT(*) as count FROM {local_trainer_rating} WHERE trainerid = :tid GROUP BY rating", ['tid' => $USER->id]);
                foreach ($stars_sql as $st) {
                    $star_counts[$st->rating] = $st->count;
                }
                $somdata['star_5'] = $star_counts[5];
                $somdata['star_4'] = $star_counts[4];
                $somdata['star_3'] = $star_counts[3];
                $somdata['star_2'] = $star_counts[2];
                $somdata['star_1'] = $star_counts[1];

                $grade_sql = "
                    SELECT r.gradeid, c.name as gradename, AVG(r.rating) as avg, COUNT(*) as count
                    FROM {local_trainer_rating} r
                    JOIN {course_categories} c ON c.id = r.gradeid
                    WHERE r.trainerid = :tid
                    GROUP BY r.gradeid, c.name
                    ORDER BY c.name ASC
                ";
                $grades = $DB->get_records_sql($grade_sql, ['tid' => $USER->id]);
                $somdata['rating_grades'] = [];
                foreach ($grades as $g) {
                    $somdata['rating_grades'][] = [
                        'gradename' => format_string($g->gradename),
                        'avg' => round($g->avg, 1),
                        'count' => $g->count
                    ];
                }

                $recent_sql = "
                    SELECT r.id, r.rating, r.feedback, r.timecreated, u.firstname, u.lastname, c.name as gradename
                    FROM {local_trainer_rating} r
                    JOIN {user} u ON u.id = r.studentid
                    JOIN {course_categories} c ON c.id = r.gradeid
                    WHERE r.trainerid = :tid
                    ORDER BY r.timecreated DESC
                    LIMIT 20
                ";
                $recent = $DB->get_records_sql($recent_sql, ['tid' => $USER->id]);
                $somdata['recent_ratings'] = [];
                foreach ($recent as $r) {
                    $rawfb = trim(strip_tags($r->feedback));
                    if (empty($rawfb)) {
                        $hasfb = false;
                        $shortfb = '<em style="color:#94a3b8;">No written feedback</em>';
                        $fullfb = '';
                    } else {
                        $hasfb = true;
                        $fullfb = htmlspecialchars($rawfb, ENT_QUOTES, 'UTF-8');
                        if (core_text::strlen($rawfb) > 85) {
                            $shortfb = htmlspecialchars(core_text::substr($rawfb, 0, 85), ENT_QUOTES, 'UTF-8') . '...';
                        } else {
                            $shortfb = htmlspecialchars($rawfb, ENT_QUOTES, 'UTF-8');
                        }
                    }

                    $somdata['recent_ratings'][] = [
                        'stars' => str_repeat('⭐', $r->rating),
                        'studentname' => fullname($r),
                        'gradename' => format_string($r->gradename),
                        'shortfeedback' => $shortfb,
                        'fullfeedback' => $fullfb,
                        'hasfeedback' => $hasfb,
                        'date' => date('d M', $r->timecreated)
                    ];
                }
            } else {
                $somdata['rating_avg'] = '0.0';
                $somdata['rating_count'] = 0;
            }
        }

        
        echo $OUTPUT->render_from_template('local_mydashboard/trainerdashboard', $somdata);
    } else {
        echo $OUTPUT->render_from_template('local_mydashboard/mydashboard', $somdata);
    }
    echo $OUTPUT->footer();
}
