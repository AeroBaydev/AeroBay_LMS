<?php
namespace local_pocstudnetenroll;

defined('MOODLE_INTERNAL') || die();

class observer {
    public static function process_enroll_action(\core\event\base $event) {
        global $DB;

        $queueid = $event->objectid;
        $queueitem = $DB->get_record('pocstudnetenroll_queue', ['id' => $queueid, 'status' => 'pending']);

        if (!$queueitem) {
            return true; // If not found or already processed, do nothing.
        }

        try {
            $enrol = enrol_get_plugin('manual');
            $instances = $DB->get_records('enrol', ['enrol' => 'manual', 'courseid' => $queueitem->courseid]);
            $instance = reset($instances);

            if (!$instance) {
                throw new \moodle_exception('manual_enrolment_not_found', 'local_pocstudnetenroll');
            }

            if ($queueitem->action === 'enroll') {
                $studentrole = $DB->get_record('role', ['shortname' => 'student']);
                $enrol->enrol_user($instance, $queueitem->userid, $studentrole->id);
            } else if ($queueitem->action === 'unenroll') {
                $enrol->unenrol_user($instance, $queueitem->userid);
            }
            $queueitem->status = 'processed';
        } catch (\Exception $e) {
            debugging('Error processing enrollment queue item ' . $queueitem->id . ': ' . $e->getMessage());
            $queueitem->status = 'error';
        }

        // --- NEW LOGIC for controlling table entries ---
        // If the action was 'unenroll' and it was successful, we delete the record from the queue.
        if ($queueitem->action === 'unenroll' && $queueitem->status === 'processed') {
            $DB->delete_records('pocstudnetenroll_queue', ['id' => $queueitem->id]);
        } else {
            // Otherwise (for 'enroll' actions or any errors), we just update the record's status.
            $queueitem->timemodified = time();
            $DB->update_record('pocstudnetenroll_queue', $queueitem);
        }
        // --- END NEW LOGIC ---

        return true;
    }
}