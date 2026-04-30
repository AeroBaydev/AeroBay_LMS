<?php
namespace local_studentapproval;

defined('MOODLE_INTERNAL') || die();

class observer {
    public static function handle_user_approved(\local_studentapproval\event\user_approved $event) {
        // 1. Create the Ad-hoc task
        $task = new \local_studentapproval\task\process_approval();
        
        // 2. Pass the User ID (optional, but good for debugging)
        $task->set_custom_data(['userid' => $event->objectid]);
        
        // 3. Queue the task (This is fast!)
        \core\task\manager::queue_adhoc_task($task);
    }
}