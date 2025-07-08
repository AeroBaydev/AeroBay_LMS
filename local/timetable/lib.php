<?php
function get_course_name($courseid) {
    global $DB;
    
    if (empty($courseid)) {
        return "Invalid Course ID";
    }

    $course = $DB->get_record('course', ['category' => $courseid], 'fullname');

    return $course ? $course->fullname : "Course Not Found";
}
