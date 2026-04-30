<?php
// studentapproval/classes/task/process_approval.php
namespace local_studentapproval\task;

use core\task\adhoc_task;

class process_approval extends adhoc_task {
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/local/send_email_id_and_password/lib.php');

        $data = $this->get_custom_data();
        $studentids = !empty($data->studentids) ? $data->studentids : [];

        // If your function can accept IDs, update it like:
        // local_send_email_id_and_password_run_process($studentids);

        // If not, just call it once as before:
        local_send_email_id_and_password_run_process();
    }
}
