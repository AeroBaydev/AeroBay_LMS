<?php
namespace local_pocenrol\event; // This line MUST be here and be correct.

defined('MOODLE_INTERNAL') || die();

class poc_course_selected extends \core\event\base {
    // ... your class code here ...
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'course';
    }

    public static function get_name() {
        return get_string('eventpoccourseselected', 'local_pocenrol');
    }
}