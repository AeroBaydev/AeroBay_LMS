<?php

require_once('../../../config.php');
require_once($CFG->dirroot . '/local/dashboard/lib.php');

require_login();

$page = optional_param('page', 0, PARAM_INT);
$filters = [
    'search' => optional_param('search', '', PARAM_TEXT),
    'schoolid' => optional_param('schoolid', 0, PARAM_INT),
    'status' => optional_param('status', '', PARAM_ALPHA),
];

if (!in_array($filters['status'], ['', 'active', 'pending', 'inactive'], true)) {
    $filters['status'] = '';
}

$context = context_system::instance();
$PAGE->set_context($context);
$scope = [];
if (is_siteadmin()) {
    require_admin();
} else if (local_dashboard_is_pocschool_user((int) $USER->id)) {
    $scope = local_dashboard_get_pocschool_scope((int) $USER->id);
} else {
    throw new required_capability_exception($context, 'moodle/site:config', 'nopermissions', '');
}

$PAGE->set_url(new moodle_url('/local/dashboard/admin/trainer_activity.php', array_filter($filters) + ['page' => $page]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Trainer Activity Monitoring');
$PAGE->set_heading('');
$PAGE->navbar->add('Admin Dashboard', new moodle_url('/local/dashboard/admin/index.php'));
$PAGE->navbar->add('Trainer Activity Monitoring');

$templatecontext = local_dashboard_get_trainer_activity_page_context($filters, $page, 25, $scope);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_dashboard/trainer_activity', $templatecontext);
echo $OUTPUT->footer();
