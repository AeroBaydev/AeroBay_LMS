<?php
require_once "../../config.php";
require_once $CFG->libdir . "/tablelib.php";
require_once "classes/table/school_table.php";
require_once($CFG->dirroot . '/local/dashboard/lib.php');

global $DB, $OUTPUT, $PAGE, $USER;
require_login();
$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);

$context = context_system::instance();
$PAGE->set_context($context);
$isadmin = is_siteadmin();
$ispocschool = local_dashboard_is_pocschool_user((int) $USER->id);
if ($isadmin) {
    $ispocschool = false;
} else if (!$ispocschool) {
    throw new required_capability_exception($context, 'local/school:manage', 'nopermissions', '');
}

if ($ispocschool && $download !== '') {
    throw new required_capability_exception($context, 'local/school:manage', 'nopermissions', '');
}

$table = new school_class_table('uniqueid', !$isadmin);

 $table->is_downloading($download, 'school_data', 'school_data');
 $PAGE->requires->css(new moodle_url('/local/students/customedit.css'));
if (!$table->is_downloading()) {
    $PAGE->set_pagelayout('course');
    $PAGE->set_title('School');
    // $PAGE->set_heading('School Table');
   
    
    echo $OUTPUT->header();
    $heading_text = $ispocschool ? "My Schools" : "Manage Schools";
    echo html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-new-school'));
    echo '<div class="action-button d-flex justify-content-between">';
    if ($isadmin) {
        echo html_writer::start_div('action-button-container');
        echo html_writer::link(new moodle_url('/local/school/addschool.php'), 'Add New School', array('class' => 'btn btn-primary'));
        echo html_writer::end_div();
    }

    echo "<form method='post' class='d-flex' action='$CFG->wwwroot/local/school/index.php'>";
    echo "<input type='search' class='ml-auto form-control rounded mr-2' name='search' placeholder='Search...' value='$search'>";
    echo '<input type="submit" value="Search" class="btn btn-primary mr-2">';
    echo '<a href="' . $CFG->wwwroot . '/local/school/index.php" class="btn btn-secondary mr-2">Clear</a>';
    echo '</form>';
    echo '</div>';
}

$fields = "sc.id as schoolid,sc.school_id as school_code,sc.principal_name as principal_name, sc.school_name AS school_name,sc.school_sortname as school_sortname";
$from = "{school} sc JOIN {course_categories} cc ON sc.school_id = cc.idnumber";
$where = "1=1";
$params = [];

if ($ispocschool) {
    $schoolids = $DB->get_fieldset_select('schoolassign', 'schoolid', 'userid = ?', [$USER->id]);
    $schoolids = array_values(array_unique(array_filter(array_map('intval', $schoolids))));
    if (empty($schoolids)) {
        $where .= " AND 1 = 0";
    } else {
        list($catsql, $catparams) = $DB->get_in_or_equal($schoolids, SQL_PARAMS_NAMED, 'pocschoolcat');
        list($schoolsql, $schoolparams) = $DB->get_in_or_equal($schoolids, SQL_PARAMS_NAMED, 'pocschoolrecord');
        $where .= " AND (cc.id {$catsql} OR sc.course_cat_id {$schoolsql})";
        $params += $catparams + $schoolparams;
    }
}

if ($search) {
    $where .= " AND (sc.principal_name LIKE :search1 OR sc.school_name LIKE :search2 OR sc.id LIKE :search3 OR sc.school_sortname like :search4 OR sc.school_id like :search5 ) ";
    $params += ['search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%",'search4'=>"%$search%",'search5'=>"%$search%"];
}
$where .= ' ORDER BY sc.id DESC';
$perpage = 10;
$DB->execute('SET @row_number := ' . (($perpage * $page)), []);

$table->set_sql($fields, $from, $where, $params);
$table->define_baseurl("$CFG->wwwroot/local/school/index.php?page=$page");

if ($table->is_downloading()) {
    $table->out($perpage, true);
    exit;
} else {
    $table->out($perpage, $isadmin);
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var select = document.getElementById('downloadtype_download');
        if (!select) {
            return;
        }
        var options = select.options;
        var valuesToRemove = ['pdf', 'ods', 'json', 'html'];

        for (var i = options.length - 1; i >= 0; i--) {
            if (valuesToRemove.includes(options[i].value)) {
                select.remove(i);
            }
        }
    });
</script>
