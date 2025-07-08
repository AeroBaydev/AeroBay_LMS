<?php
require_once "../../config.php";
require_once $CFG->libdir . "/tablelib.php";
require_once "classes/table/news_table.php";

global $DB, $OUTPUT, $PAGE;
require_login();
$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);

$context = context_system::instance();
$PAGE->set_context($context);

$table = new news_class_table('uniqueid');

 $table->is_downloading($download, 'news_data', 'news_data');
  $PAGE->requires->css(new moodle_url('/local/news/customedit.css'));
  $PAGE->requires->js(new moodle_url("$CFG->wwwroot/local/news/main.js"));
if (!$table->is_downloading()) {
    $PAGE->set_pagelayout('course');
    $PAGE->set_title('news');
    // $PAGE->set_heading('news Table');
   
    
    echo $OUTPUT->header();
    $heading_text = "Manage News";
    echo html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-new-news'));
    echo '<div class="action-button d-flex justify-content-between">';
    echo html_writer::start_div('action-button-container');
    echo html_writer::link(new moodle_url('/local/news/addnews.php'), 'Add  New', array('class' => 'btn btn-primary'));
    echo html_writer::end_div();

    echo "<form method='post' class='d-flex' action='$CFG->wwwroot/local/news/index.php'>";
    echo "<input type='search' class='ml-auto form-control rounded mr-2' name='search' placeholder='Search...' value='$search'>";
    echo '<input type="submit" value="Search" class="btn btn-primary mr-2">';
    echo '<a href="' . $CFG->wwwroot . '/local/news/index.php" class="btn btn-secondary mr-2">Clear</a>';
    echo '</form>';
    echo '</div>';
}

$fields = "ns.id as newsid,ns.news as newstext,ns.schoolid as schoolid ,ns.gradeid as gradeid, ns.timecreated as timecreated ";
$from = "{news} ns";
$where = "1=1 ";
$params = [];

if ($search) {
    $where .= " AND (ns.principal_name LIKE :search1 OR ns.news_name LIKE :search2 OR ns.id LIKE :search3 OR ns.news_sortname like :search4 OR ns.news_id like :search5 ) ";
    $params = ['search1' => "%$search%", 'search2' => "%$search%", 'search3' => "%$search%",'search4'=>"%$search%",'search5'=>"%$search%"];
}
$where .= "ORDER BY ns.id DESC";
$perpage = 10;
$DB->execute('SET @row_number := ' . (($perpage * $page)), []);

$table->set_sql($fields, $from, $where, $params);
$table->define_baseurl("$CFG->wwwroot/local/news/index.php?page=$page");

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