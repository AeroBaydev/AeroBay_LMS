<?php
require_once "../../config.php";
require_once $CFG->libdir . "/tablelib.php";
require_once "classes/table/poc_table.php";
$PAGE->requires->js(new moodle_url("$CFG->wwwroot/local/poc/main.js"));
global $page;
require_login();
$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);

$context = context_system::instance();
$PAGE->set_context($context);
if (!has_capability('local/poc:view', $context)) {
    throw new required_capability_exception($context, 'local/poc:view', 'nopermissions', '');
}
$_SESSION['caturlid']=0;
$PAGE->set_url('/poc_management.php');

$download = optional_param('download', '', PARAM_ALPHA);
$roleid = optional_param('roleid', 0, PARAM_INT);

$table = new poc_table('uniqueid');

 $table->is_downloading($download, 'poc', 'poc_data');
$PAGE->set_pagelayout('course');
$PAGE->set_title('POC Management');
$PAGE->navbar->add('POC Management', "$CFG->wwwroot/local/poc/poc_management.php");
// $PAGE->set_heading('POC Table');
if (!$table->is_downloading()) {
  
   
    echo $OUTPUT->header();
    $heading_text = "POC Management";
    
    echo html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-poc-user'));

    echo html_writer::start_div('action-button-container');
    echo "<form method='post' class='d-flex' action='$CFG->wwwroot/local/poc/poc_management.php'>";
    echo html_writer::link(new moodle_url('/local/poc/poc_form.php'), 'Add New POC', array('class' => 'btn btn-primary'));
    echo "<input type='search' class='ml-auto form-control rounded mr-2' name='search' placeholder='Search...' value='$search'>";
    echo '<input type="submit" value="Search" class="btn btn-primary mr-2">';
    echo '<a href="' . $CFG->wwwroot . '/local/poc/poc_management.php" class="btn btn-secondary mr-2">Clear</a>';
    echo '</form>';
    echo '</div>';

}       
        $fields = "(@row_number := @row_number + 1) as serial,pc.userid as id, ar.name as role,pc.firstname as firstname, pc.lastname as lastname, pc.contact_number as contact, pc.current_address as address, pc.poc_id as employeid";
        $from =  "{poc} as pc 
        LEFT JOIN {role} as ar ON ar.id = pc.roleid join {user} as u on u.id=pc.userid ";
        $where = "1=1 and u.deleted=0";
        $params = [];
        $order_by = "ORDER BY pc.id DESC";

        if ($search) {
           
            $where .= " AND (pc.firstname LIKE :search1 OR pc.contact_number LIKE :search2 OR pc.id LIKE :search3 OR pc.poc_id LIKE :search4) ";
            $params = ['search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%", 'search4' => "%$search%"];
        }
        $where .= ' ORDER BY pc.id DESC';
        $perpage = 11;
        $DB->execute('SET @row_number := ' . ($perpage * $page));
        $table->set_sql($fields, $from, $where, $params);
        $table->define_baseurl("$CFG->wwwroot/local/poc/poc_management.php?page=$page");
        
        if ($table->is_downloading()) {
            $table->out($perpage, true);
            exit;
        } else {
            $table->out($perpage, true);
            echo $OUTPUT->footer();
        }
        
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
<script>
function submitForm(id) {
    document.getElementById('hiddenIdpoc').value = id;
    document.getElementById('postForm').submit();
}
</script>