<?php
require_once "../../config.php";
require_once $CFG->libdir . "/tablelib.php";
require_once "classes/table/regionalpoc_table.php";
require_once($CFG->dirroot . '/local/regionalpoc/lib.php');
// global $page;
global $DB, $OUTPUT, $PAGE;
require_login();
local_regionalpoc_require_regional_manager();
$page = optional_param('page', 0, PARAM_INT);
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/rm_arm_manage.php');
// $PAGE->requires->css(new moodle_url('/theme/boost/style/bootstrap.css'));
// $PAGE->requires->js(new moodle_url('/theme/boost/javascript/bootstrap.js'));
// $PAGE->requires->js(new moodle_url('/local/regionalpoc/amd/src/regpoc.js'));


$download = optional_param('download', '', PARAM_ALPHA);
$usertypselected = optional_param('usertype', 'arm', PARAM_ALPHA);
if ($usertypselected !== 'arm') {
    $usertypselected = 'arm';
}
$table = new regionalpoc_table('uniqueid');
$usertype = 'asstmanager';
$table->is_downloading($download, 'regionalpoc', 'regionalpoc_data');

$fields = "(@row_number := @row_number + 1) as serialno, rp.userid as userid, rp.firstname as firstname, rp.lastname as lastname, rp.contact_number as contact, rp.current_address as address, rp.designation as designation";
$from =  "{regionalpoc}  rp  join {user}  u on u.id=rp.userid";

$where = "rp.usertype = :usertype AND rp.pocid = :pocid";
$params = ['usertype' => $usertype, 'pocid' => $USER->id];

// $order_by = "ORDER BY rp.userid DESC";
$perpage = 10;
$table->set_sql($fields, $from, $where . ' ORDER BY rp.id DESC', $params);
$DB->execute('SET @row_number := ' . ($perpage * $page));
$table->define_baseurl("$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?usertype=$usertypselected&page=$page");


if ($table->is_downloading()) {
    $table->out($perpage, true);
    exit;
} else {
    $PAGE->set_pagelayout('course');
    $PAGE->set_title('Assistant Regional Manager Management');
    $PAGE->navbar->add('Assistant Regional Manager Management', "$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?usertype=arm");
    // $PAGE->set_heading('Assistant Regional Manager Management');
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />';
    echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>';
    echo $OUTPUT->header();

    $heading_text = "Assistant Regional Manager Management";
    echo html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-regionalpoc-user'));

    echo html_writer::start_div('form-inline text-xs-right action-button-container');
    echo html_writer::link(new moodle_url('/local/regionalpoc/rm_arm_form.php'), 'Add Assistant Regional Manager', array('class' => 'btn btn-primary mr-2'));
    echo html_writer::end_div();


    $roleNames = [
        "arm" => 'Assistant Regional Manager'
    ];

   

    echo $OUTPUT->single_select(
        new moodle_url("$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php", $urlparams),
        'usertype',
        $roleNames,
        $usertypselected,
        '',
        null,
        ['label' => 'Select View']

    );



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
