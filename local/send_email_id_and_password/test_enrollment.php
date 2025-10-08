<?php
require_once(__DIR__ . '/../../config.php'); 
require_once($CFG->dirroot . '/local/emailtemplates/email_sender.php');
require_once($CFG->libdir . '/moodlelib.php');
/**
 * Finds all pending students and processes them for password reset, email, and enrollment.
 */
function local_send_email_id_and_password_run_process() {
    global $DB;

    //mtrace("--- Starting the student processing function ---");

    $sql = "SELECT * FROM {student} WHERE status = :status AND email_status = :emailstatus";
    $students_rs = $DB->get_recordset_sql($sql, ['status' => 1, 'emailstatus' => 0]);

    foreach ($students_rs as $student) {
        try {
            //mtrace("... Processing User ID: {$student->userid}");

            // Fetch user details.
            $user = $DB->get_record('user', ['id' => $student->userid, 'deleted' => 0]);
            if (!$user) {
                //mtrace("...... FAILED: User not found or deleted. Marking as failed.");
                $DB->set_field('student', 'email_status', 2, ['id' => $student->id]);
                continue;
            }

            // Check for the course.
            $poc_course = $DB->get_record('poc_copy_course', [
                'schoolid' => $student->schoolid,
                'gradeid'  => $student->gradeid,
                'status'   => 1
            ]);

            if (!$poc_course || empty($poc_course->courseid)) {
                //mtrace("...... FAILED: No course found. Marking as permanent failure.");
                $DB->set_field('student', 'email_status', 2, ['id' => $student->id]);
                continue;
            }
            //mtrace("...... Found course (ID: {$poc_course->courseid}).");

            // Generate and update password.
            $newpassword = generate_password();
            if (!update_internal_user_password($user, $newpassword)) {
                //mtrace("...... FAILED: Could not update Moodle password. Will retry later.");
                continue;
            }
            //mtrace("...... Password updated successfully.");

            // Send email.
            $email_sent = \local_emailtemplates\email_sender::send_email("student", $user->id, $newpassword, 0);
            if (!$email_sent) {
                //mtrace("...... FAILED: Email could not be sent. Will retry on the next run.");
                $DB->set_field('student', 'email_status', 0, ['id' => $student->id]);
                continue;
            }
            //mtrace("...... Email sent successfully.");

            // --- NEW LOGIC: Instead of direct enrollment, create a queue entry and trigger an event ---
            $courseid = (int)$poc_course->courseid;
            $context = \context_course::instance($courseid);

            // Check if an 'enroll' action already exists in the queue for this user and course to avoid duplicates.
            $existing_enroll = $DB->get_record('pocstudnetenroll_queue', [
                'userid'   => $user->id,
                'courseid' => $courseid,
                'action'   => 'enroll'
            ]);

            if (!$existing_enroll) {
                // If no record exists, create one and trigger the event.
                $queueitem = new \stdClass();
                $queueitem->userid = $user->id;
                $queueitem->courseid = $courseid;
                $queueitem->gradeid = $student->gradeid;
                $queueitem->pocid = $poc_course->pocid; // Assuming pocid is in the poc_course table
                $queueitem->action = 'enroll';
                $queueitem->status = 'pending';
                $queueitem->timecreated = time();
                $queueitem->timemodified = $queueitem->timecreated;
                $queueid = $DB->insert_record('pocstudnetenroll_queue', $queueitem);

                \local_pocstudnetenroll\event\student_enroll_action_triggered::create([
                    'context'  => $context,
                    'objectid' => $queueid,
                    'other'    => ['userid' => $user->id, 'action' => 'enroll']
                ])->trigger();

                //mtrace("...... Created 'enroll' request in queue for user ID {$user->id}.");
            } else {
                //mtrace("...... An 'enroll' request already exists in the queue for user ID {$user->id}. Skipping.");
            }
            // --- END OF NEW LOGIC ---

            // If everything worked up to this point, update the status to 1 (Success).
            $DB->set_field('student', 'email_status', 1, ['id' => $student->id]);
            //mtrace("...... SUCCESS: User ID {$student->userid} processed completely.");

        } catch (\Exception $e) {
            //mtrace("...... CRITICAL ERROR processing User ID {$student->userid}: " . $e->getMessage());
            $DB->set_field('student', 'email_status', 2, ['id' => $student->id]);
        }
    }

    $students_rs->close();
    //mtrace("--- Finished processing all students ---");
}
local_send_email_id_and_password_run_process();
