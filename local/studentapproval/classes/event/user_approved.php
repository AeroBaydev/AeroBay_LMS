<?php
namespace local_studentapproval\event;

defined('MOODLE_INTERNAL') || die();

class user_approved extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'u'; // u for updated
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'user'; // The main object of this event is a user.
    }

    public static function get_name() {
        return get_string('eventuserapproved', 'local_studentapproval');
    }
}