<?php
require_once("../../config.php");
require_once($CFG->libdir . "/tablelib.php");
require_once("classes/table/student_table.php");
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');

$PAGE->requires->js(new moodle_url("$CFG->wwwroot/local/students/main.js"));

global $DB;

// Moodle login
require_login();

// --------- PARAMS ----------
$download    = optional_param('download', '', PARAM_ALPHA);
$search      = optional_param('search', '', PARAM_TEXT);
$userIdPoc   = optional_param('userid', '', PARAM_TEXT);
$perpage_in  = optional_param('perpage', null, PARAM_ALPHANUM);


$page_input = optional_param('page', null, PARAM_INT);

if ($page_input !== null) {

    $_SESSION['student_manage_page'] = $page_input;
    $page = $page_input;
} else if (isset($_SESSION['student_manage_page'])) {
    
    $page = (int)$_SESSION['student_manage_page'];

    
    if (!isset($_GET['page']) && !isset($_POST['page'])) {
        $_GET['page']     = $page;
        $_REQUEST['page'] = $page;
    }
} else {
   
    $page = 0;
    $_GET['page']     = 0;
    $_REQUEST['page'] = 0;
}
// --- PAGE SESSION LOGIC END ---

// --- PERPAGE SESSION LOGIC START ---
if ($perpage_in) {
    
    $_SESSION['student_manage_perpage'] = $perpage_in;
    $perpage_display = $perpage_in;

    
    $page = 0;
    $_SESSION['student_manage_page'] = 0;
    $_GET['page']     = 0;
    $_REQUEST['page'] = 0;
} elseif (isset($_SESSION['student_manage_perpage'])) {
    $perpage_display = $_SESSION['student_manage_perpage'];
} else {
    $perpage_display = '10';
}

// Convert to Integer for Database
if ($perpage_display === 'all') {
    $perpage_sql = 50000; // Set a high limit to show "All"
} else {
    $perpage_sql = (int)$perpage_display;
    if ($perpage_sql < 1) $perpage_sql = 10; // Safety fallback
}
// --- PERPAGE SESSION LOGIC END ---


// --------- USER / POC LOGIC ----------
if (is_siteadmin()) {
    if (!isset($_SESSION['userIdPoc']) && !empty($userIdPoc)) {
        $_SESSION['userIdPoc'] = $userIdPoc;
        $userid = $userIdPoc;
    }
} else {
    $userid = $USER->id;
}

if (isset($_SESSION['userIdPoc']) && (is_siteadmin() || local_pocschool_is_poc_user())) {
    $userid = $_SESSION['userIdPoc'];
}


// --------- TABLE SETUP ----------
$table = new student_table('uniqueid');
$table->is_downloading($download, 'student_data', 'student_data');

if (!$table->is_downloading()) {

    $PAGE->set_pagelayout('course');
    $PAGE->set_title('Student');
    if (!local_pocschool_is_trainer_user()) {
        $PAGE->navbar->add('POC Control', "$CFG->wwwroot/local/poc/pocmange/?userid=$userid");
        $PAGE->navbar->add('POC Student list', "");
    }

    echo $OUTPUT->header();

    $heading_text = "Student Management";
    echo html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-student'));

    echo html_writer::start_div('d-flex justify-content-between mb-2');

    echo html_writer::link(
        new moodle_url('/local/students/student_form.php'),
        'Add New Student',
        array('class' => 'btn btn-primary mr-10')
    );

    echo html_writer::start_div('d-flex');

    // --- FORM (PERPAGE + SEARCH) ---
    echo "<form method='post' class='d-flex align-items-center' action='$CFG->wwwroot/local/students/student_manage.php'>";

    // userid preserve
    echo html_writer::empty_tag('input', [
        'type'  => 'hidden',
        'name'  => 'userid',
        'value' => $userid
    ]);

    $perpage_options = array(
        '10'  => '10',
        '20'  => '20',
        '50'  => '50',
        '100' => '100',
        'all' => 'Show All'
    );

    echo html_writer::label('Show:', 'perpage_select', false, array('class' => 'mr-1 font-weight-bold'));
    echo html_writer::select(
        $perpage_options,
        'perpage',
        $perpage_display,
        false,
        array(
            'id'    => 'perpage_select',
            'class' => 'form-control mr-3',
            'style' => 'width: auto;',
            'onchange' => 'this.form.submit()'
        )
    );

    echo "<input type='search' class='ml-auto form-control rounded mr-2' name='search' placeholder='Search...' value='" . s($search) . "'>";
    echo '<input type="submit" value="Search" class="btn btn-primary mr-2">';
    echo '<a href="' . $CFG->wwwroot . '/local/students/student_manage.php" class="btn btn-secondary mr-2">Clear</a>';
    echo '</form>';

    echo html_writer::end_div(); // inner d-flex
    echo html_writer::end_div(); // outer d-flex
}


// --------- SQL FOR TABLE ----------
$fields = "(@row_number := @row_number + 1) as serialno,
           st.userid as id,
           st.status,
           u.firstname as firstname,
           u.lastname as lastname,
           st.address as address,
           st.student_id as studentid";

$from = "{student} as st
         JOIN {user} u ON st.userid = u.id
         JOIN {schoolassign} sa ON sa.schoolid = st.schoolid";

$safe_userid = (int)$userid;
$where = "1=1 AND sa.userid = :pocuserid AND u.deleted = 0";
$params = ['pocuserid' => $safe_userid];
local_pocschool_apply_trainer_student_filter($where, $params, 'st');

if ($search) {
    $where .= " AND (u.firstname LIKE :search1 OR u.lastname LIKE :search2 OR st.student_id LIKE :search3)";
    $params['search1'] = "%$search%";
    $params['search2'] = "%$search%";
    $params['search3'] = "%$search%";
}

$table->set_sql($fields, $from, $where, $params);

// Row number base
$DB->execute('SET @row_number := ' . ($perpage_sql * $page));


$baseurl = $CFG->wwwroot . '/local/students/student_manage.php?userid=' . $userid;
$table->define_baseurl($baseurl);

// --------- OUTPUT ----------
if ($table->is_downloading()) {
    $table->out($perpage_sql, true);
    exit;
} else {
    $table->out($perpage_sql, true);
    if (!local_pocschool_is_trainer_user()) {
        echo html_writer::tag('button', 'Bulk Approve', array('id' => 'batch-approve-btn', 'class' => 'btn btn-success'));
    }
    echo $OUTPUT->footer();
}
?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var select = document.getElementById('downloadtype_download');
        if (!select) return;

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
