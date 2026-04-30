<?php
if (!is_siteadmin()) {
function local_coursecopy_extend_navigation(global_navigation $navigation) {
    global $CFG, $PAGE;
  
        $navigation->add(
            "School Allotment",
            new moodle_url($CFG->wwwroot . '/local/coursecopy/test.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_coursecopy',
            new pix_icon('i/cohort','')
        )->showinflatnavigation = true; 
}
}
?>