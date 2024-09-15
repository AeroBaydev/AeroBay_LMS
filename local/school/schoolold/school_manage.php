<?php
require_once "../../config.php";
require_once $CFG->libdir . "/tablelib.php";
require_once "classes/table/school_table.php";

global $DB, $OUTPUT, $PAGE;
require_login();
$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);

$context = context_system::instance();
$PAGE->set_context($context);

$table = new school_class_table('uniqueid');
$table->is_downloading($download, 'school_data', 'school_data');

if (!$table->is_downloading()) {
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title('School');
    $PAGE->set_heading('School Table');
   
    
    echo $OUTPUT->header();
    $heading_text = "Manage Schools";
    echo html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-new-school'));
    echo '<div class="action-button d-flex justify-content-between">';
    echo html_writer::start_div('action-button-container');
    echo html_writer::link(new moodle_url('/local/school/school_form.php'), 'Add New School', array('class' => 'btn btn-primary'));
    echo html_writer::end_div();

    echo "<form method='post' class='d-flex' action='$CFG->wwwroot/local/school/school_manage.php'>";
    echo "<input type='search' class='ml-auto form-control rounded mr-2' name='search' placeholder='Search...' value='$search'>";
    echo '<input type="submit" value="Search" class="btn btn-primary mr-2">';
    echo '<a href="' . $CFG->wwwroot . '/local/school/school_manage.php" class="btn btn-secondary mr-2">Clear</a>';
    echo '</form>';
    echo '</div>';
}

$fields = "sc.id as schoolid,sc.school_id as school_code,sc.principal_name as principal_name, sc.school_name AS school_name,sc.school_sortname";
$from = "{school} sc JOIN {course_categories} cc ON sc.school_sortname = cc.name";
$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (sc.principal_name LIKE :search1 OR sc.school_name LIKE :search2 OR sc.id LIKE :search3) ";
    $params = ['search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%"];
}
$where .= ' ORDER BY sc.id DESC';
$perpage = 11;
$DB->execute('SET @row_number := ' . (($perpage * $page)), []);

$table->set_sql($fields, $from, $where, $params);
$table->define_baseurl("$CFG->wwwroot/local/school/school_manage.php?page=$page");

if ($table->is_downloading()) {
    $table->out($perpage, true);
    exit;
} else {
    $table->out($perpage, true);
    echo $OUTPUT->footer();
}
?>
<script>
    $(document).ready(function() {
        const data = document.querySelectorAll('a')
        data.forEach((e) => {
            if (e.innerText.includes("Action")) {
                e.href = ''
            }

        })
    });
</script>