<?php
require_once "../../config.php";
require_once $CFG->libdir . "/tablelib.php";
require_once "classes/table/subcard_table.php";

global $DB, $OUTPUT, $PAGE;
require_login();
$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);
$parent = optional_param('id', '', PARAM_TEXT);

$context = context_system::instance();
$PAGE->set_context($context);

$table = new subcard_table('uniqueid');

 $table->is_downloading($download, 'assessmentcard_data', 'assessmentcard_data');
 $PAGE->requires->css(new moodle_url('/local/assessmentcard/customedit.css'));
if (!$table->is_downloading()) {
    $PAGE->set_pagelayout('course');
    $PAGE->set_title('assessmentcard');
    // $PAGE->set_heading('assessmentcard Table');
   
    $PAGE->navbar->add('assessmentcard Management', "$CFG->wwwroot/local/assessmentcard/");
 $PAGE->navbar->add('Add assessmentcard', "$CFG->wwwroot/local/assessmentcard/addassessmentcard.php");
    echo $OUTPUT->header();
    $heading_text = "Manage assessmentcard";
    echo html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-new-assessmentcard'));
    echo '<div class="action-button d-flex justify-content-between">';
    echo html_writer::start_div('action-button-container');
    $assessmentcard = $DB->get_record('assessmentcard', array('parentid' => $parent));
    if(!$assessmentcard){
    echo html_writer::link(new moodle_url("/local/assessmentcard/addsub_card.php?id=$parent"), 'Add New assessmentcard', array('class' => 'btn btn-primary'));
}
    echo html_writer::end_div();

    echo "<form method='post' class='d-flex' action='$CFG->wwwroot/local/assessmentcard/index.php'>";
    echo "<input type='search' class='ml-auto form-control rounded mr-2' name='search' placeholder='Search...' value='$search'>";
    echo '<input type="submit" value="Search" class="btn btn-primary mr-2">';
    echo '<a href="' . $CFG->wwwroot . '/local/card/index.php" class="btn btn-secondary mr-2">Clear</a>';
    echo '</form>';
    echo '</div>';
}

$fields = "bc.id as assessmentcardid , bc.name as name,bc.imgpath as imgpath,bc.parentid,parentid ,bc.rang1 as rang1,bc.rang2 as rang2";
$from = "{assessmentcard} bc";
$where = "1=1 and bc.parentid=$parent";
$params = [];

if ($search) {
   
}
$where .= " ORDER BY bc.id ";
$perpage = 10;
$DB->execute('SET @row_number := ' . (($perpage * $page)), []);

$table->set_sql($fields, $from, $where, $params);
$table->define_baseurl("$CFG->wwwroot/local/assessmentcard/index.php?page=$page");

if ($table->is_downloading()) {
    $table->out($perpage, true);
    exit;
} else {
    $table->out($perpage, true);
    echo $OUTPUT->footer();
}
