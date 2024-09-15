<?php
/**
 * Simple file test_custom.php to drop into root of Moodle installation.
 * This is an example of using a sql_table class to format data.
 */
require "../../config.php";
require "$CFG->libdir/tablelib.php";
require "classes/test_table.php";
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/test_custom.php');

$download = optional_param('download', '', PARAM_ALPHA);

$table = new test_table('uniqueid');
$table->is_downloading($download, 'test', 'testing123');

if (!$table->is_downloading()) {
    // Only print headers if not asked to download data.
    // Print the page header.
    $PAGE->set_title('Testing');
    $PAGE->set_heading('Testing table class');
    $PAGE->navbar->add('Testing table class', new moodle_url('/test_custom.php'));
    echo $OUTPUT->header();
}

// Work out the sql for the table.
// $table->set_sql('*', "{user}", '1=1');

// $table->define_baseurl("$CFG->wwwroot/local/poc/test_custom.php");

// $table->out(10, true);

$fields = "(@row_number := @row_number + 1) as serialno,tr.id as id,ar.name as role, tr.userid as userid,tr.firstname as firstname, tr.lastname as lastname, tr.contact_number as contact, tr.current_address as address, tr.designation as designation";
        $from =  "{poc} as tr left join {role} as ar on ar.id=tr.roleid";
        $where = "'1==1'";

        $perpage = 10;
        $page = optional_param('page', 0, PARAM_INT);
        $DB->execute('SET @row_number = ' . (($perpage * $page)));
        // Work out the sql for the table.
        $table->set_sql($fields,$from ,$where );

        $table->define_baseurl("$CFG->wwwroot/local/poc/poc_management.php");

        $table->out(10, true);

        if (!$table->is_downloading()) {
            echo $OUTPUT->footer();
        }