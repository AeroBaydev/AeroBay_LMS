<?php
namespace local_studentapproval;

defined('MOODLE_INTERNAL') || die();

class observer {
    public static function handle_user_approved(\local_studentapproval\event\user_approved $event) {
        //mtrace("Observer triggered for userid: {$event->objectid}");
        
        // Instead of adhoc task, directly call the processing function
        global $CFG;
        require_once($CFG->dirroot . '/local/send_email_id_and_password/lib.php');
        
        //mtrace("Calling student processing function directly...");
        local_send_email_id_and_password_run_process();
        
        //mtrace("Student processing completed for userid: {$event->objectid}");
    }
}