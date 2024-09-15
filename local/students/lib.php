<?php
function local_students_extend_navigation(global_navigation $navigation) {
    global $CFG, $PAGE;
  
        $navigation->add(
            "Student Management",
            new moodle_url($CFG->wwwroot . '/local/students/student_manage.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_students',
            new pix_icon('i/cohort','')
        )->showinflatnavigation = true; 
}

if(is_siteadmin()){
    $CFG->custommenuitems ="School Management | /local/school/index.php
                    POC Management  | /local/poc/poc_management.php
                         Course Mapping | /local/copycourse/index.php
                         Email Management | /local/emailtemplates/list.php
                         Course Management | /course/management.php
                         Student Managment | /local/studentadmin/index.php
            ";
}

?>