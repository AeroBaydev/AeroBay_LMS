<?php
namespace local_studentapproval\task;

use core\task\adhoc_task;

class process_approval extends adhoc_task {
    public function execute() {
        global $CFG, $DB;
        //mtrace('Executing process_approval adhoc task...');



        require_once($CFG->dirroot . '/local/send_email_id_and_password/lib.php');
        local_send_email_id_and_password_run_process();

        //mtrace("... Finished process_approval task for userid {$userid}.");
    }
}