<?php

require_once "../../config.php";
require_once $CFG->libdir . "/tablelib.php";
require_once "classes/table/student_table.php";
$PAGE->requires->js(new moodle_url("$CFG->wwwroot/local/students/main.js"));
global $DB;
require_login();

// $context = context_user::instance($USER->id);
// $PAGE->set_context($context);

$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);

$userIdPoc = optional_param('userid', '', PARAM_TEXT);

if(is_siteadmin()){
    if (!isset($_SESSION['userIdPoc'])) {
     
    $_SESSION['userIdPoc'] = $userIdPoc;
    $userid =$userIdPoc;
    }

}
else{
    $userid=$USER->id;
}

if (isset($_SESSION['userIdPoc'])) {
     $userid=$_SESSION['userIdPoc'];
   
}


$table = new student_table('uniqueid');

$table->is_downloading($download, 'student_data', 'student_data');

if (!$table->is_downloading()) {
    
   // $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('course');
   $PAGE->set_title('Student');
   $PAGE->navbar->add('POC Control', "$CFG->wwwroot/local/poc/pocmange/?userid=$userid");
   $PAGE->navbar->add('POC Student list', "");
    // $PAGE->requires->css('/local/students/amd/css/styles.css');
    echo $OUTPUT->header();

   $heading_text = "Student Management";
    echo html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-student'));

    echo html_writer::start_div('d-flex justify-content-between mb-2');

    echo html_writer::link(new moodle_url('/local/students/student_form.php'), 'Add New Student', array('class' => 'btn btn-primary mr-10'));
    echo html_writer::start_div('d-flex');
    
    echo "<form method='post' class='d-flex' action='$CFG->wwwroot/local/students/student_manage.php'>";
    echo "<input type='search' class='ml-auto form-control rounded mr-2' name='search' placeholder='Search...' value='" . ($search) . "'>";
    echo '<input type="submit" value="Search" class="btn btn-primary mr-2">';
    echo '<a href="' . $CFG->wwwroot . '/local/students/student_manage.php" class="btn btn-secondary mr-2">Clear</a>';
    echo '</form>';
    echo html_writer::end_div();
    echo html_writer::end_div();
}

$fields = "(@row_number := @row_number + 1) as serialno, st.userid as id,st.status, u.firstname as firstname, u.lastname as lastname, st.address as address , st.student_id as studentid";
$from = "{student} as st JOIN {user} u ON st.userid = u.id join {schoolassign} sa on sa.schoolid=st.schoolid ";
$where = "1=1  and sa.userid=$userid and u.deleted=0";
$params = [];

if ($search) {
    $where .= " AND (u.firstname LIKE :search1 OR u.lastname LIKE :search2 OR st.student_id LIKE :search3)";
    $params = ['search1' => "%$search%", 'search2' => "%$search%",'search3' => "%$search%"];
}

$perpage = 10;
$table->set_sql($fields, $from, $where, $params);
$DB->execute('SET @row_number := ' . ($perpage * $page));
$table->define_baseurl("$CFG->wwwroot/local/students/student_manage.php?page=$page?userid=$userid");

if ($table->is_downloading()) {
    $table->out($perpage, true);
    exit;
} else {
    $table->out($perpage, true);
    echo html_writer::tag('button', 'Balk Approve', array('id' => 'batch-approve-btn', 'class' => 'btn btn-success'));
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
    document.addEventListener('DOMContentLoaded', function() {
        var breadcrumbItems = document.querySelectorAll('.breadcrumb-item');
        breadcrumbItems.forEach(function(item) {
            if (item.textContent.trim() === 'Student Management') {
                item.style.display = 'none';
            }
        });
    });
</script>