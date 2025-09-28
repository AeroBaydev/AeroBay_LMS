<?php
defined('MOODLE_INTERNAL') || die();

$settings = new admin_settingpage('local_videohub', get_string('pluginname', 'local_videohub'));
$ADMIN->add('localplugins', $settings);

$settings->add(new admin_setting_configtext(
    'local_videohub/allowedmimetypes',
    get_string('allowedmimetypes', 'local_videohub'),
    '',
    ''
));

$settings->add(new admin_setting_configtext(
    'local_videohub/perpage',
    get_string('perpage', 'local_videohub'),
    '',
    10
));

$settings->add(new admin_setting_configtext(
    'local_videohub/schoolfield',
    get_string('schoolfield', 'local_videohub'),
    '',
    get_string('defaultfield_school', 'local_videohub')
));

$settings->add(new admin_setting_configtext(
    'local_videohub/gradefield',
    get_string('gradefield', 'local_videohub'),
    '',
    get_string('defaultfield_grade', 'local_videohub')
));
