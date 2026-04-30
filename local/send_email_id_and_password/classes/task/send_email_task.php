<?php
namespace local_send_email_id_and_password\task;

use core\task\scheduled_task;

class send_email_task extends scheduled_task {

    public function get_name() {
        return get_string('sendemailtask', 'local_send_email_id_and_password');
    }

    /**
     * The main execution method for the task.
     */
    public function execute() {
         global $CFG;
        require_once($CFG->dirroot . '/local/send_email_id_and_password/lib.php');
       
        // mtrace("Executing send_email_task...");

        // Step 1: Include the library file.
        // require_once($CFG->dirroot . '/local/send_email_id_and_password/test_enrollment.php');

        // // Step 2: Call the single main function with no parameters.
         local_send_email_id_and_password_run_process();

      
    }
}