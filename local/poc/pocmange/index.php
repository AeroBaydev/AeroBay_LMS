<?php
require_once "../../../config.php";
require_once $CFG->libdir . "/tablelib.php";
require_once "../classes/table/poc_manage_table.php";
global $page;
require_login();
$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);
$userIdPoc = optional_param('userid', 0, PARAM_INT);
$context = context_system::instance();
$PAGE->set_context($context);

if (!has_capability('local/poc:view', $context)) {
    throw new required_capability_exception($context, 'local/poc:view', 'nopermissions', '');
}
$_SESSION['caturlid'] = 0;
$PAGE->set_url('/poc_pocmanagement.php');
 $_SESSION['userIdPoc']=$userIdPoc;
$download = optional_param('download', '', PARAM_ALPHA);
$roleid = optional_param('roleid', 0, PARAM_INT);

$table = new poc_manage_table('uniqueid');

// $table->is_downloading($download, 'poc', 'poc_data');
$PAGE->set_pagelayout('course');
$PAGE->set_title('POC Management');

 $PAGE->navbar->add('POC List', "$CFG->wwwroot/local/poc/poc_management.php");
 $PAGE->navbar->add('POC controls', "");
      
// Get username
$user = core_user::get_user($userIdPoc);
if ($user) {
    $fullname = $user->username; // Assuming fullname function exists to get the full name.
} else {
    $fullname = 'User not found';
}
$PAGE->set_heading($fullname);
echo $OUTPUT->header();
$fields = "(@row_number := @row_number + 1) as serial, pc.userid as id, ar.name as role, pc.firstname as firstname, pc.lastname as lastname, pc.contact_number as contact, pc.current_address as address, pc.poc_id as employeid";
$from = "{poc} as pc LEFT JOIN {role} as ar ON ar.id = pc.roleid";
$where = "1=1 AND pc.userid = :userid";
$params = ['userid' => $userIdPoc];
$order_by = "ORDER BY pc.id DESC";

if ($search) {
    $where .= " AND (pc.firstname LIKE :search1 OR pc.contact_number LIKE :search2 OR pc.id LIKE :search3 OR pc.poc_id LIKE :search4)";
    $params['search1'] = "%$search%";
    $params['search2'] = "%$search%";
    $params['search3'] = "%$search%";
    $params['search4'] = "%$search%";
}

$perpage = 11;
$DB->execute('SET @row_number := ' . ($perpage * $page));

$table->set_sql($fields, $from, $where, $params);
$table->define_baseurl("$CFG->wwwroot/local/poc/poc_management.php?page=$page");
    $table->out($perpage, true);
    echo $OUTPUT->footer();

?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var select = document.getElementById('downloadtype_download');
        var options = select.options;
        var valuesToRemove = ['pdf', 'ods', 'json', 'html'];

        for (var i = options.length - 1; i >= 0; i--) {
            if (valuesToRemove.includes(options[i].value)) {
                select.remove(i);
            }
        }
    });
</script>
