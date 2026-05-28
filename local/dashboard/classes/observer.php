<?php

namespace local_dashboard;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer callbacks for local_dashboard.
 */
class observer {
    /**
     * Log new Moodle course creation in the dashboard activity feed.
     *
     * @param \core\event\course_created $event
     * @return void
     */
    public static function course_created(\core\event\course_created $event): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/local/dashboard/lib.php');

        $courseid = (int) $event->objectid;
        $course = $DB->get_record('course', ['id' => $courseid], 'id, fullname, category', IGNORE_MISSING);
        if (!$course) {
            return;
        }

        $site = get_site();
        local_dashboard_log_activity(
            'course_created',
            'Course created',
            format_string($course->fullname) . ' course created',
            0,
            [
                'schoolname' => format_string($site->fullname),
                'actorid' => (int) $event->userid,
                'metadata' => [
                    'courseid' => $courseid,
                    'categoryid' => (int) $course->category,
                ],
            ]
        );
    }

    /**
     * Log Moodle course deletion in the dashboard activity feed.
     *
     * @param \core\event\course_deleted $event
     * @return void
     */
    public static function course_deleted(\core\event\course_deleted $event): void {
        global $CFG;

        require_once($CFG->dirroot . '/local/dashboard/lib.php');

        $courseid = (int) $event->objectid;
        $course = $event->get_record_snapshot('course', $courseid);
        $fullname = $course && !empty($course->fullname)
            ? $course->fullname
            : ($event->other['fullname'] ?? 'Course');
        $categoryid = $course && !empty($course->category) ? (int) $course->category : 0;

        $site = get_site();
        local_dashboard_log_activity(
            'course_deleted',
            'Course deleted',
            format_string($fullname) . ' course removed',
            0,
            [
                'schoolname' => format_string($site->fullname),
                'actorid' => (int) $event->userid,
                'metadata' => [
                    'courseid' => $courseid,
                    'categoryid' => $categoryid,
                ],
            ]
        );
    }
}
