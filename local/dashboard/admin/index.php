<?php

require_once('../../../config.php'); // Moodle configuration file.
require_once($CFG->dirroot . '/local/dashboard/lib.php');

require_login();

$title = 'Admin Dashboard';
$pagetitle = $title;
$PAGE->set_title($title);
$PAGE->set_heading($title);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$scope = [];
if (is_siteadmin()) {
    require_admin();
} else if (local_dashboard_is_pocschool_user((int) $USER->id)) {
    $scope = local_dashboard_get_pocschool_scope((int) $USER->id);
} else {
    throw new required_capability_exception($context, 'moodle/site:config', 'nopermissions', '');
}
$somdata = local_dashboard_get_admin_stats_context($scope);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_dashboard/index', $somdata);
echo $OUTPUT->footer();
