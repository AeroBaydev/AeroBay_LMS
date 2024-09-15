<?php


function local_trainer_extend_navigation(global_navigation $navigation) {
    global $CFG, $PAGE;
  
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