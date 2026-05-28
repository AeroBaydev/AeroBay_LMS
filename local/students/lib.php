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
if (!isset($CFG->custommenuitems)) {
    $CFG->custommenuitems = "";
}
if(is_siteadmin()){
    $CFG->custommenuitems ="School Management | /local/school/index.php
                    Trainer Management | /local/trainer/index.php
                    POC Management  | /local/poc/poc_management.php
                         Course Mapping | /local/copycourse/index.php
                         Email Management | /local/emailtemplates/list.php
                         Course Management | /course/management.php
                         Student Managment | /local/studentadmin/index.php
                          News Managment | /local/news/
                           Time Table Managment | /local/timetable/index.php
                           Add Session Badge | /local/sessioncard/index.php
                           Add Assessment Badge  | /local/assessmentcard/index.php
                            Add Attendance Badge  | /local/attendancecard/index.php
                             Attendance Managment  | /local/attendance_new/index.php
            ";
}
function is_student_anywhere(?int $userid): bool {
    global $DB;
    if (empty($userid)) {
        return false;
    }
    $sql = "SELECT 1
              FROM {role_assignments} ra
              JOIN {role} r   ON r.id = ra.roleid
              JOIN {context} c ON c.id = ra.contextid
             WHERE ra.userid = :uid
               AND c.contextlevel = :level
               AND r.archetype = 'student'";
    return $DB->record_exists_sql($sql, ['uid' => $userid, 'level' => CONTEXT_COURSE]);
}
global $USER, $CFG, $DB;
if (isset($USER->id) && is_numeric($USER->id)) {
     
    if (is_student_anywhere((int)$USER->id)) {
      
        $CFG->custommenuitems = "video upload media | /local/videohub/index.php";
    }
}

?>
