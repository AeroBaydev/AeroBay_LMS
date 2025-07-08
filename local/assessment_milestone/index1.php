<?php
require_once "../../config.php";

global $DB, $USER;

$courseid = 276;

// Fetch all quiz attempts by the user in a specific course
$quiz_attempts = $DB->get_records_sql("
    SELECT qa.id, qa.quiz, qa.userid, qa.timemodified
    FROM {quiz_attempts} qa
    JOIN {quiz} q ON qa.quiz = q.id
    WHERE qa.userid = ? 
    AND q.course = ?
    AND qa.state = 'finished'
    ORDER BY qa.timemodified ASC
", [$USER->id, $courseid]);

// Convert to an indexed array
// $attempts = array_values($quiz_attempts);
// $total_attempts = count($attempts);

$milestones = [6, 12, 18, 24]; // Milestone checkpoints

foreach ($quiz_attempts as $key => $value) {
   $o= get_quiz_result($value->quiz,$USER->id,);
  
}




function get_quiz_result($quizid, $userid) {
    global $DB;

    // Fetch user's latest quiz attempt result
    $result = $DB->get_record_sql("
        SELECT gg.finalgrade
        FROM {grade_grades} gg
        JOIN {grade_items} gi ON gg.itemid = gi.id
        WHERE gg.userid = ? 
        AND gi.iteminstance = ? 
        AND gi.itemmodule = 'quiz'

        ORDER BY gg.timemodified DESC
        LIMIT 1
    ", [$userid, $quizid]);

    // Return result or 0 if no grade found
    return $result ? $result->finalgrade : 0;
}


// foreach ($milestones as $milestone) {
//     if ($total_attempts >= $milestone) {
//       echo  $offset = $milestone - 6; // Ye ensure karega ki starting 1-6 aayega

//         // Check if this milestone is already recorded
//         $existing = $DB->get_record('assessment_milestone', [
//             'userid' => $USER->id,
//             'milestone' => $milestone,
//             'courseid' => $courseid
//         ]);

//         if (!$existing) {
//             // Get only the 6 quiz grades for this milestone range
//             $grades = $DB->get_records_sql("
//                 SELECT gg.finalgrade
//                 FROM {grade_grades} gg
//                 JOIN {grade_items} gi ON gg.itemid = gi.id
//                 JOIN {quiz} q ON gi.iteminstance = q.id
//                 WHERE gg.userid = ?
//                 AND q.course = ?
//                 AND gi.itemmodule = 'quiz'
//                 AND gg.finalgrade IS NOT NULL
//                 ORDER BY gg.timemodified ASC
//                 LIMIT 6 OFFSET $offset
//             ", [$USER->id, $courseid]);

//            // $grades = array_column($grades, 'finalgrade');

//             // Debugging - Check fetched grades
         
//             print_r($grades);
//             die;
        
//             // Agar exactly 6 grades mile to hi percentage calculate kare
//             if (count($grades) == 6) {
//                 $totalScore = array_sum($grades);
//                 $maxScore = 6 * 10; // Assuming max score per quiz is 10
//                 $percentage = ($totalScore / $maxScore) * 100;

//                 // Store in database
//                 $record = new stdClass();
//                 $record->userid = $USER->id;
//                 $record->courseid = $courseid;
//                 $record->milestone = $milestone;
//                 $record->percentage = round($percentage, 2);
//                 $record->timecreated = time();
//                 $DB->insert_record('assessment_milestone', $record);

//                 echo "Stored: " . round($percentage, 2) . "% for milestone $milestone in course $courseid<br>";
//             }
//         }
//     }
// }
