<?php
require_once "../../config.php";
require_once $CFG->libdir . "/tablelib.php";
require_once "classes/table/regionalpoc_table.php";
// global $page;
$page = optional_param('page', 0, PARAM_INT);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/regionalpoc_custom.php');
$PAGE->requires->css(new moodle_url('/theme/boost/style/bootstrap.css'));
$PAGE->requires->js(new moodle_url('/theme/boost/javascript/bootstrap.js'));
$PAGE->requires->js(new moodle_url('/local/regionalpoc/amd/src/regpoc.js'));


$download = optional_param('download', '', PARAM_ALPHA);
$roleid = optional_param('roleid', 0, PARAM_INT);

$table = new regionalpoc_table('uniqueid');

$table->is_downloading($download, 'regionalpoc', 'regionalpoc_data');

var_dump($USER->id);die;
$fields = "(@row_number := @row_number + 1) as serialno,rp.id as id,ar.name as role, rp.userid as userid,rp.firstname as firstname, rp.lastname as lastname, rp.contact_number as contact, rp.current_address as address, rp.designation as designation";
$from =  "{regionalpoc} as rp left join {role} as ar on ar.id=rp.roleid";
$where = "rp.roleid=$roleid AND rp.pocid = $USER->id";
// if ($roleid == 12 || $roleid == 13) {
//     $where .= " AND rp.roleid=$roleid ";
// }
$order_by = "ORDER BY rp.id DESC";
$perpage = 10;
$table->set_sql($fields, $from, $where . ' ' . $order_by);
// $table->set_sql($fields, $from, $where);
$DB->execute('SET @row_number := ' . ($perpage * $page));
$table->define_baseurl("$CFG->wwwroot/local/regionalpoc/regionalpoc_custom.php?page=$page&roleid=$roleid");

if ($table->is_downloading()) {
    // die('fafs');
    $table->out($perpage, true);
    exit;
} else {
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title('RM/ARM Management');
    $PAGE->navbar->add('RM/ARM Management', new moodle_url('/regionalpoc_custom.php'));
    $PAGE->set_heading('RM/ARM Management');

    echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>';
    echo $OUTPUT->header();

    $heading_text = "RM / ARM Management";
    echo html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-regionalpoc-user'));

    echo html_writer::start_div('form-inline text-xs-right action-button-container');
    echo html_writer::link(new moodle_url('/local/regionalpoc/regionalpoc_form.php'), 'Add New RM/ARM', array('class' => 'btn btn-primary mr-2'));
    echo html_writer::end_div();


    $roles = $DB->get_records_sql("SELECT id FROM {role} WHERE name IN ('RM','ARM')");
    $role = [];
    foreach ($roles as $role1) {
        $role[] = $role1->id;
    }

    $roleNames = [
        12 => 'Regional Manager',
        13 => 'Assistant Regional Manager'
    ];

    foreach ($role as $roleId) {
        if (isset($roleNames[$roleId])) {
        }
    }

    $selectedroleid = optional_param('roleid', 0, PARAM_INT);

    $defaultroleid = 12;

    // If no valid role ID is selected, use the default.
    if (!array_key_exists($selectedroleid, $roleNames)) {
        $selectedroleid = $defaultroleid;
    }
    echo $OUTPUT->single_select(
        new moodle_url("$CFG->wwwroot/local/regionalpoc/regionalpoc_custom.php", $urlparams),
        'roleid',
        $roleNames,
        $selectedroleid,
        '',
        null,
        ['label' => 'Select View']

    );





    $table->out($perpage, true);
    echo $OUTPUT->render_from_template('local_regionalpoc/modal', []);
    echo $OUTPUT->footer();
}
