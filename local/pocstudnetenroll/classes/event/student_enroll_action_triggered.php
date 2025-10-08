<?php
namespace local_pocstudnetenroll\event;

defined('MOODLE_INTERNAL') || die();

class student_enroll_action_triggered extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'pocstudnetenroll_queue';
    }

    public static function get_name() {
        return get_string('event_enrollactiontriggered', 'local_pocstudnetenroll');
    }

    public function get_description() {
        return "User with id '{$this->other['userid']}' queued for action '{$this->other['action']}' in course id '{$this->contextinstanceid}'.";
    }
}