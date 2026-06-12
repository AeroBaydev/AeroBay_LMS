
<?php

require_once "../../config.php";
require_once $CFG->libdir . "/tablelib.php";
require_once "classes/table/trainer_table.php";
require_once($CFG->dirroot . '/local/regionalpoc/lib.php');

global $DB, $USER;
require_login();

// $context = context_user::instance($USER->id);
// $PAGE->set_context($context);

$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);

$table = new trainer_table('uniqueid');


if(is_siteadmin()){
    $userIdPoc = optional_param('userid', 0, PARAM_INT);
    if (!isset($_SESSION['userIdPoc']) && !empty($userIdPoc)) {
        $_SESSION['userIdPoc'] = $userIdPoc;
        $userid = $userIdPoc;
    }
    
}
else{
    $userid=$USER->id;
}

if (isset($_SESSION['userIdPoc'])) {
     $userid=$_SESSION['userIdPoc'];
   
}

if (is_siteadmin() && empty($userid)) {
    redirect(new moodle_url('/local/trainer/index.php'));
}



// $table->is_downloading($download, 'trainer_data', 'trainer_data');

if (!$table->is_downloading()) {
    $PAGE->set_pagelayout('course');
    $PAGE->set_title('Trainer');
    if(is_siteadmin()){
    $PAGE->navbar->add('POC Control', "$CFG->wwwroot/local/poc/pocmange/?userid=$userid");
    $PAGE->navbar->add('POC Student list', "");
    }
    // $PAGE->set_heading('Trainer Table');
    // $PAGE->navbar->add('', new moodle_url('/trainer_manage.php'));
   
    echo $OUTPUT->header();

    $heading_text = "Trainer Management";
    echo html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-trainer'));

    echo html_writer::start_div('d-flex justify-content-between mb-2');
    echo html_writer::link(new moodle_url('/local/trainer/trainer_form.php'), 'Add New Trainer', array('class' => 'btn btn-primary mr-10'));
    echo html_writer::start_div('d-flex');
    echo "<form method='post' class='d-flex' action='$CFG->wwwroot/local/trainer/trainer_manage.php'>";
    echo "<input type='search' class='ml-auto form-control rounded mr-2' name='search' placeholder='Search...' value='" . ($search) . "'>";
    echo '<input type="submit" value="Search" class="btn btn-primary mr-2">';
    echo '<a href="' . $CFG->wwwroot . '/local/trainer/trainer_manage.php" class="btn btn-secondary mr-2">Clear</a>';
    echo '</form>';
    echo html_writer::end_div();
    echo html_writer::end_div();
}
// tr.trainer_id as trainercode, 
$schoolcourseconditions = [
    "c.category = tr.schoolid",
    "EXISTS (
        SELECT 1
          FROM {course_categories} coursecat
         WHERE coursecat.id = c.category
           AND cc.path IS NOT NULL
           AND coursecat.path LIKE CONCAT(cc.path, '/%')
    )",
];
if ($DB->get_manager()->table_exists('poc_copy_course')) {
    $schoolcourseconditions[] = "EXISTS (
        SELECT 1
          FROM {poc_copy_course} pcc
         WHERE pcc.schoolid = tr.schoolid
           AND pcc.courseid = c.id
    )";
}
$schoolcoursecondition = '(' . implode(' OR ', $schoolcourseconditions) . ')';

$fields = "(@row_number := @row_number + 1) as serialno,
    tr.userid as id,
    tr.firstname as firstname,
    tr.lastname as lastname,
    tr.contact_number as contact,
    COALESCE(sc.school_name, cc.name) as assignedschools,
    GROUP_CONCAT(DISTINCT c.fullname ORDER BY c.fullname SEPARATOR ', ') as assignedcourses,
    tr.current_address as address,
    tr.designation as designation,
    tr.trainerid as trainderid";
$from = "{trainer} as tr
    LEFT JOIN {course_categories} cc ON cc.id = tr.schoolid
    LEFT JOIN {school} sc ON sc.course_cat_id = cc.id
    LEFT JOIN {course} c ON c.visible = 1
                         AND c.id <> :siteid
                         AND tr.schoolid IS NOT NULL
                         AND tr.schoolid <> 0
                         AND cc.id IS NOT NULL
                         AND {$schoolcoursecondition}";
$params = ['siteid' => SITEID];
if (!is_siteadmin() && local_regionalpoc_is_arm_user((int) $USER->id)) {
    $armschoolids = local_regionalpoc_get_arm_school_ids((int) $USER->id);
    if (empty($armschoolids)) {
        $where = "1 = 0";
    } else {
        list($armschoolsql, $armschoolparams) = $DB->get_in_or_equal($armschoolids, SQL_PARAMS_NAMED, 'armtrainerschool');
        $where = "tr.schoolid {$armschoolsql}";
        $params += $armschoolparams;
    }
} else {
    $where = "((tr.schoolid IS NOT NULL AND tr.schoolid <> 0
                AND EXISTS (
                    SELECT 1
                      FROM {schoolassign} sa
                     WHERE sa.schoolid = tr.schoolid
                       AND sa.userid = :pocuserid
                ))
            OR ((tr.schoolid IS NULL OR tr.schoolid = 0 OR cc.id IS NULL) AND tr.createdby = :legacycreatedby))";
    $params += ['pocuserid' => $userid, 'legacycreatedby' => $userid];
}

if ($search) {
    $where .= " AND (tr.firstname LIKE :search1 OR tr.lastname LIKE :search2 OR tr.contact_number LIKE :search3 OR tr.trainerid LIKE :search4)";
    $params += ['search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%",'search4' => "%$search%"];
}

$perpage = 10;
$table->set_sql(
    $fields,
    $from,
    $where . " GROUP BY tr.userid, tr.firstname, tr.lastname, tr.contact_number, sc.school_name, cc.name, tr.current_address, tr.designation, tr.trainerid",
    $params
);
$table->set_count_sql(
    "SELECT COUNT(DISTINCT tr.userid)
       FROM $from
      WHERE $where",
    $params
);
$DB->execute('SET @row_number := ' . ($perpage * $page));
$table->define_baseurl("$CFG->wwwroot/local/trainer/trainer_manage.php?page=$page");

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
