<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category('local_regionalpoc', get_string('pluginname', 'local_regionalpoc')));

    $settingspage = new admin_settingpage('local_regionalpoc_settings', get_string('pluginname', 'local_regionalpoc'));
    $settingspage->add(new admin_setting_heading('local_regionalpoc_heading', get_string('pluginname', 'local_regionalpoc'), ''));

    $ADMIN->add('local_regionalpoc', $settingspage);
}
