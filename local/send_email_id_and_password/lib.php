<?php
defined('MOODLE_INTERNAL') || die();

// --- QUICK FIX: Manually include the email_sender class file. ---
// require_once($CFG->dirroot . '/local/emailtemplates/classes/email_sender.php');

/**
 * Finds all pending students and processes them for password reset, email, and enrollment.
 */
function local_send_email_id_and_password_run_process() {
    global $DB, $CFG;

    //mtrace("--- Starting the student processing function ---");

    // Debug: Check total students in table
    $total_students = $DB->count_records('student');
    //mtrace("... Total students in table: {$total_students}");
    
    // Debug: Check students with status = 1
    $status_1_count = $DB->count_records('student', ['status' => 1]);
    //mtrace("... Students with status = 1: {$status_1_count}");
    
    // Debug: Check students with email_status = 0
    $email_0_count = $DB->count_records('student', ['email_status' => 0]);
    //mtrace("... Students with email_status = 0: {$email_0_count}");

    $sql = "SELECT * FROM {student} WHERE status = :status AND email_status = :emailstatus";
    $students_rs = $DB->get_recordset_sql($sql, ['status' => 1, 'emailstatus' => 0]);
    
    // Debug: Count matching records
    $matching_students = $DB->get_records_sql($sql, ['status' => 1, 'emailstatus' => 0]);
    //mtrace("... Students matching criteria (status=1 AND email_status=0): " . count($matching_students));

    foreach ($students_rs as $student) {
        try {
            //mtrace("... Processing User ID: {$student->userid}");

            // Step 1: Check if user exists
            $user = $DB->get_record('user', ['id' => $student->userid, 'deleted' => 0]);
            if (!$user) {
                //mtrace("...... FAILED: User not found or deleted.");
                $DB->set_field('student', 'email_status', 2, ['id' => $student->id]);
                continue;
            }
            //mtrace("...... User found: {$user->username}");

            // Step 2: Check POC course exists (MOST IMPORTANT - CHECK FIRST)
            $poc_course = $DB->get_record('poc_copy_course', [
                'schoolid' => $student->schoolid,
                'gradeid'  => $student->gradeid,
                'status'   => 1
            ]);
            if (!$poc_course || empty($poc_course->courseid)) {
                //mtrace("...... FAILED: No POC course found for School:{$student->schoolid}, Grade:{$student->gradeid}");
                $DB->set_field('student', 'email_status', 2, ['id' => $student->id]);
                continue; // Skip this student, continue with next
            }
            
            // Step 3: Verify course exists in Moodle
            $courseid = (int)$poc_course->courseid;
            $course = $DB->get_record('course', ['id' => $courseid]);
            if (!$course) {
                //mtrace("...... FAILED: Course ID {$courseid} not found in Moodle");
                $DB->set_field('student', 'email_status', 2, ['id' => $student->id]);
                continue;
            }
            //mtrace("...... POC Course found: {$course->fullname} (ID: {$courseid})");

            // Step 4: Check enrollment status first
            $context = \context_course::instance($courseid);
            $already_enrolled = is_enrolled($context, $user->id);
            
            if ($already_enrolled) {
                //mtrace("...... User already enrolled in course - skipping enrollment");
                // Even if already enrolled, check group assignment
                //mtrace("...... Checking group assignment for already enrolled user");
            } else {
                // Step 5: Enroll student first (before password/email)
                require_once($CFG->dirroot . '/enrol/manual/lib.php');
                
                $manual_plugin = enrol_get_plugin('manual');
                if (!$manual_plugin) {
                    //mtrace("...... FAILED: Manual enrollment plugin not available");
                    continue;
                }
                
                $manual_instance = $DB->get_record('enrol', [
                    'courseid' => $courseid,
                    'enrol' => 'manual',
                    'status' => ENROL_INSTANCE_ENABLED
                ]);
                
                if (!$manual_instance) {
                    //mtrace("...... FAILED: No active manual enrollment instance for course");
                    continue;
                }
                
                // Enroll user as student (role ID 5)
                $student_role_id = 5;
                $manual_plugin->enrol_user($manual_instance, $user->id, $student_role_id, time());
                //mtrace("...... SUCCESS: User enrolled in course ID: {$courseid}");
            }

            // Step 6: AFTER ENROLLMENT - Add user to group based on POC relation
            //mtrace("...... Starting group assignment for User ID: {$user->id}");
            
            try {
                // Method 1: Try to get POC ID from poc_copy_course table (current course relation)
                $poc_course_record = $DB->get_record('poc_copy_course', [
                    'courseid' => $courseid,
                    'status' => 1
                ]);
                
                if ($poc_course_record && !empty($poc_course_record->pocid)) {
                    $poc_id = $poc_course_record->pocid;
                    //mtrace("...... Found POC ID from course relation: {$poc_id}");
                } else {
                    // Method 2: Try to get POC ID from student table
                    $student_record = $DB->get_record('student', ['userid' => $user->id]);
                    if ($student_record) {
                        // Check if student table has POC relation fields
                        $student_fields = get_object_vars($student_record);
                        //mtrace("...... Student record fields: " . implode(', ', array_keys($student_fields)));
                        
                        // Try common POC relation field names
                        $poc_id = null;
                        if (isset($student_record->pocid)) {
                            $poc_id = $student_record->pocid;
                        } elseif (isset($student_record->poc_id)) {
                            $poc_id = $student_record->poc_id;
                        } elseif (isset($student_record->schoolid) && isset($student_record->gradeid)) {
                            // Create POC ID from school and grade combination
                            $poc_id = "School_{$student_record->schoolid}_Grade_{$student_record->gradeid}";
                        }
                        
                        if ($poc_id) {
                            //mtrace("...... Found POC ID from student record: {$poc_id}");
                        } else {
                            //mtrace("...... No POC ID found in student record");
                        }
                    } else {
                        //mtrace("...... No student record found");
                    }
                }
                
                // If we have a POC ID, create/assign group
                if (!empty($poc_id)) {
                    // Create group name based on poc_id
                    $group_name = "POC_" . $poc_id;
                    //mtrace("...... Group name: {$group_name}");
                    
                    // Check if group exists in this course
                    $group = $DB->get_record('groups', ['courseid' => $courseid, 'name' => $group_name]);
                    
                    if (!$group) {
                        //mtrace("...... Group doesn't exist, creating new group");
                        // Group doesn't exist, create it
                        $group_data = new \stdClass();
                        $group_data->courseid = $courseid;
                        $group_data->name = $group_name;
                        $group_data->description = "Auto-created group for POC ID: " . $poc_id;
                        $group_data->timecreated = time();
                        $group_data->timemodified = time();
                        
                        // Insert group directly into database
                        $group_id = $DB->insert_record('groups', $group_data);
                        //mtrace("...... Group created with ID: {$group_id}");
                        
                        if ($group_id) {
                            // Group created, now add user to group
                            $member_data = new \stdClass();
                            $member_data->groupid = $group_id;
                            $member_data->userid = $user->id;
                            $member_data->timeadded = time();
                            $member_id = $DB->insert_record('groups_members', $member_data);
                            //mtrace("...... User added to new group - Member ID: {$member_id}");
                            
                            // Verify the insertion
                            $verify_member = $DB->get_record('groups_members', ['id' => $member_id]);
                            if ($verify_member) {
                                //mtrace("...... VERIFIED: User successfully added to group");
                            } else {
                                //mtrace("...... ERROR: Failed to verify group membership");
                            }
                        }
                    } else {
                        //mtrace("...... Group already exists with ID: {$group->id}");
                        // Group exists, check if user is already a member
                        $group_id = $group->id;
                        $existing_member = $DB->get_record('groups_members', [
                            'groupid' => $group_id, 
                            'userid' => $user->id
                        ]);
                        
                        if (!$existing_member) {
                            //mtrace("...... User not a member, adding to group");
                            // User not a member, add them
                            $member_data = new \stdClass();
                            $member_data->groupid = $group_id;
                            $member_data->userid = $user->id;
                            $member_data->timeadded = time();
                            $member_id = $DB->insert_record('groups_members', $member_data);
                            //mtrace("...... User added to existing group - Member ID: {$member_id}");
                            
                            // Verify the insertion
                            $verify_member = $DB->get_record('groups_members', ['id' => $member_id]);
                            if ($verify_member) {
                                //mtrace("...... VERIFIED: User successfully added to existing group");
                            } else {
                                //mtrace("...... ERROR: Failed to verify group membership");
                            }
                        } else {
                            //mtrace("...... User already a member of this group (Member ID: {$existing_member->id})");
                        }
                    }
                } else {
                    //mtrace("...... No POC ID found - skipping group assignment");
                }
                
            } catch (\Exception $group_error) {
                //mtrace("...... CRITICAL ERROR in group assignment: " . $group_error->getMessage());
            }

            // Step 6: Update password
            $newpassword = generate_password();
            if (!update_internal_user_password($user, $newpassword)) {
                //mtrace("...... FAILED: Could not update password");
                continue;
            }
            //mtrace("...... Password updated successfully");

            // Step 7: Send email with new password
            $email_sent = \local_emailtemplates\email_sender::send_email("student", $user->id, $newpassword, 0);
            if (!$email_sent) {
                //mtrace("...... FAILED: Email could not be sent - will retry later");
                $DB->set_field('student', 'email_status', 0, ['id' => $student->id]);
                continue;
            }
            //mtrace("...... Email sent successfully");

            // Step 8: Add entry to pocstudnetenroll_queue for tracking
            $existing_queue = $DB->get_record('pocstudnetenroll_queue', [
                'userid' => $user->id,
                'courseid' => $courseid,
                'action' => 'enroll'
            ]);
            
            if (!$existing_queue) {
                $queueitem = new \stdClass();
                $queueitem->userid = $user->id;
                $queueitem->courseid = $courseid;
                $queueitem->gradeid = $student->gradeid;
                $queueitem->pocid = $poc_course->pocid ?? 0;
                $queueitem->action = 'enroll';
                $queueitem->status = 'completed'; // Mark as completed since we already enrolled
                $queueitem->timecreated = time();
                $queueitem->timemodified = time();
                $queueid = $DB->insert_record('pocstudnetenroll_queue', $queueitem);
                //mtrace("...... Queue entry created (ID: {$queueid})");
            } else {
                // Update existing queue entry
                $DB->set_field('pocstudnetenroll_queue', 'status', 'completed', ['id' => $existing_queue->id]);
                $DB->set_field('pocstudnetenroll_queue', 'timemodified', time(), ['id' => $existing_queue->id]);
                //mtrace("...... Queue entry updated");
            }

            // Step 9: Mark student as processed successfully
            $DB->set_field('student', 'email_status', 1, ['id' => $student->id]);
            $DB->set_field('student', 'status', 1, ['id' => $student->id]);
            //mtrace("...... SUCCESS: User ID {$student->userid} fully processed");

        } catch (\Exception $e) {
            //mtrace("...... CRITICAL ERROR: " . $e->getMessage());
            $DB->set_field('student', 'email_status', 2, ['id' => $student->id]);
        }
    }
    $students_rs->close();
    //mtrace("--- Finished processing all students ---");
}