<?php
namespace local_assessment_milestone;

defined('MOODLE_INTERNAL') || die();

class observer {
    public static function quiz_attempt_submitted(\mod_quiz\event\attempt_submitted $event) {
        global $DB, $USER;


        
        $total_quizzes = $DB->get_field('quiz', 'COUNT(*)', ['course' => $courseid]);
        if($total_quizzes == 12){  

            $quiz = $DB->get_record('quiz', ['id' => $event->other['quizid']]);
            $courseid = $quiz->course;
            
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
            $attempts = array_values($quiz_attempts);
            $total_attempts = count($attempts);
            
            $milestones = [6, 12, 18, 24]; // Milestone checkpoints
            
            foreach ($milestones as $milestone) {
                if ($total_attempts >= $milestone) { // Jab exactly milestone ka attempt ho tabhi chalega
                    $offset = $milestone - 6;
            
                    // Get only the 6 quizzes for this milestone range
                    $selected_attempts = array_slice($attempts, $offset, 6);
                    $quiz_ids = array_column($selected_attempts, 'quiz');
    
                    $grades = [];
                    foreach ($quiz_ids as $quizid) {
                        $grades[] = self::get_quiz_result($quizid, $USER->id);
                    }
    
                    // Ensure we have exactly 6 grades before storing
                    if (count($grades) == 6) {
                        $totalScore = array_sum($grades);
                        $maxScore = 6 * 10; // Assuming max score per quiz is 10
                        $percentage = ($totalScore / $maxScore) * 100;
                        $percentage = round($percentage, 2);
    
                        // Check if record already exists
                        $existing = $DB->get_record('assessment_milestone', [
                            'userid' => $USER->id,
                            'milestone' => $milestone,
                            'courseid' => $courseid
                        ]);
    
                        if ($existing) {
                            // **Update existing record**
                            $existing->percentage = $percentage;
                            $existing->timecreated = time();
                            $DB->update_record('assessment_milestone', $existing);
                        } else {
                            // **Insert new record**
                            $record = new \stdClass();
                            $record->userid = $USER->id;
                            $record->courseid = $courseid;
                            $record->milestone = $milestone;
                            $record->percentage = $percentage;
                            $record->timecreated = time();
                            $DB->insert_record('assessment_milestone', $record);
                        }
                    }
                }
            }
    
       
        }

        // if($total_quizzes == 30){  

        //     $quiz = $DB->get_record('quiz', ['id' => $event->other['quizid']]);
        //     $courseid = $quiz->course;
            
        //     // Fetch all quiz attempts by the user in a specific course
        //     $quiz_attempts = $DB->get_records_sql("
        //         SELECT qa.id, qa.quiz, qa.userid, qa.timemodified
        //         FROM {quiz_attempts} qa
        //         JOIN {quiz} q ON qa.quiz = q.id
        //         WHERE qa.userid = ? 
        //         AND q.course = ?
        //         AND qa.state = 'finished'
        //         ORDER BY qa.timemodified ASC
        //     ", [$USER->id, $courseid]);
            
        //     // Convert to an indexed array
        //     $attempts = array_values($quiz_attempts);
        //     $total_attempts = count($attempts);
            
        //     $milestones = [5, 10, 15, 20, 25, 30]; // Milestone checkpoints
            
        //     foreach ($milestones as $milestone) {
        //         if ($total_attempts >= $milestone) { // Jab exactly milestone ka attempt ho tabhi chalega
        //             $offset = $milestone - 5
            
        //             // Get only the 6 quizzes for this milestone range
        //             $selected_attempts = array_slice($attempts, $offset, 5);
        //             $quiz_ids = array_column($selected_attempts, 'quiz');
    
        //             $grades = [];
        //             foreach ($quiz_ids as $quizid) {
        //                 $grades[] = self::get_quiz_result($quizid, $USER->id);
        //             }
    
        //             // Ensure we have exactly 6 grades before storing
        //             if (count($grades) == 5) {
        //                 $totalScore = array_sum($grades);
        //                 $maxScore = 5 * 10; // Assuming max score per quiz is 10
        //                 $percentage = ($totalScore / $maxScore) * 100;
        //                 $percentage = round($percentage, 2);
    
        //                 // Check if record already exists
        //                 $existing = $DB->get_record('assessment_milestone', [
        //                     'userid' => $USER->id,
        //                     'milestone' => $milestone,
        //                     'courseid' => $courseid
        //                 ]);
    
        //                 if ($existing) {
        //                     // **Update existing record**
        //                     $existing->percentage = $percentage;
        //                     $existing->timecreated = time();
        //                     $DB->update_record('assessment_milestone', $existing);
        //                 } else {
        //                     // **Insert new record**
        //                     $record = new \stdClass();
        //                     $record->userid = $USER->id;
        //                     $record->courseid = $courseid;
        //                     $record->milestone = $milestone;
        //                     $record->percentage = $percentage;
        //                     $record->timecreated = time();
        //                     $DB->insert_record('assessment_milestone', $record);
        //                 }
        //             }
        //         }
        //     }
    
       
        // }




    }

    /**
     * Fetch quiz result for a specific user and quiz.
     */
    private static function get_quiz_result($quizid, $userid) {
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
}
