<?php
namespace local_pocstudnetenroll\task;

use core\task\scheduled_task;

class process_enrollment_queue extends scheduled_task {
    
    public function get_name() {
        return 'Process enrollment queue';
    }
    
    public function execute() {
        global $DB, $CFG;
        
        require_once($CFG->dirroot . '/enrol/manual/lib.php');
        
        mtrace('--- Starting enrollment queue processing ---');
        
        // Get all pending enrollment requests
        $sql = "SELECT * FROM {pocstudnetenroll_queue} WHERE status = :status AND action = :action ORDER BY timecreated ASC";
        $pending_enrollments = $DB->get_recordset_sql($sql, ['status' => 'pending', 'action' => 'enroll']);
        
        $processed_count = 0;
        $failed_count = 0;
        
        foreach ($pending_enrollments as $enrollment) {
            try {
                mtrace("... Processing enrollment ID: {$enrollment->id}");
                
                // Check if user exists
                $user = $DB->get_record('user', ['id' => $enrollment->userid, 'deleted' => 0]);
                if (!$user) {
                    mtrace("...... FAILED: User not found");
                    $DB->set_field('pocstudnetenroll_queue', 'status', 'failed', ['id' => $enrollment->id]);
                    $failed_count++;
                    continue;
                }
                
                // Check if course exists
                $course = $DB->get_record('course', ['id' => $enrollment->courseid]);
                if (!$course) {
                    mtrace("...... FAILED: Course not found");
                    $DB->set_field('pocstudnetenroll_queue', 'status', 'failed', ['id' => $enrollment->id]);
                    $failed_count++;
                    continue;
                }
                
                // Check if already enrolled
                $context = \context_course::instance($enrollment->courseid);
                if (is_enrolled($context, $enrollment->userid)) {
                    mtrace("...... Already enrolled, marking completed");
                    $DB->set_field('pocstudnetenroll_queue', 'status', 'completed', ['id' => $enrollment->id]);
                    $processed_count++;
                    continue;
                }
                
                // Get manual enrollment plugin
                $manual_plugin = enrol_get_plugin('manual');
                if (!$manual_plugin) {
                    mtrace("...... FAILED: Manual enrollment plugin not available");
                    $failed_count++;
                    continue;
                }
                
                // Get manual enrollment instance
                $manual_instance = $DB->get_record('enrol', [
                    'courseid' => $enrollment->courseid,
                    'enrol' => 'manual',
                    'status' => ENROL_INSTANCE_ENABLED
                ]);
                
                if (!$manual_instance) {
                    mtrace("...... FAILED: No manual enrollment instance");
                    $failed_count++;
                    continue;
                }
                
                // Enroll user as student (role ID 5)
                $student_role_id = 5;
                $manual_plugin->enrol_user($manual_instance, $enrollment->userid, $student_role_id, time());
                
                mtrace("...... SUCCESS: User enrolled");
                
                // Mark as completed
                $DB->set_field('pocstudnetenroll_queue', 'status', 'completed', ['id' => $enrollment->id]);
                $DB->set_field('pocstudnetenroll_queue', 'timemodified', time(), ['id' => $enrollment->id]);
                
                $processed_count++;
                
            } catch (\Exception $e) {
                mtrace("...... ERROR: " . $e->getMessage());
                $DB->set_field('pocstudnetenroll_queue', 'status', 'failed', ['id' => $enrollment->id]);
                $failed_count++;
            }
        }
        
        $pending_enrollments->close();
        
        mtrace("--- Completed: Processed={$processed_count}, Failed={$failed_count} ---");
    }
}