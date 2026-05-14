<?php
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');


function local_trainer_extend_navigation(global_navigation $navigation) {
    global $CFG, $PAGE;
    if (local_pocschool_is_trainer_user()) {
        return;
    }

        $navigation->add(
            "Trainer Management",
            new moodle_url($CFG->wwwroot . '/local/trainer/trainer_manage.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_trainer',
            new pix_icon('i/cohort','')
        )->showinflatnavigation = true; 
      //  $PAGE->navigation->action="https://dev.icloudcampus.com/update/mydashboard/";
}

?>
