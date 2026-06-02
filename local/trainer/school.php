<?php
require_once "../../config.php";
require_login();
// require_capability('moodle/site:config', context_system::instance());
global $DB, $OUTPUT, $PAGE,$USER;

$PAGE->requires->css(new moodle_url('/local/trainer/style.css'));
$PAGE->requires->js(new moodle_url('/local/trainer/script.js'));
$PAGE->set_pagelayout('standard');

$userId = optional_param('id', 0, PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$pocuserid = $USER->id;
if (is_siteadmin() && !empty($_SESSION['userIdPoc'])) {
    $pocuserid = (int) $_SESSION['userIdPoc'];
}
$trainer = $DB->get_record('trainer', ['userid' => $userId], '*', MUST_EXIST);
$canmanage = is_siteadmin();
if (!$canmanage) {
    if (!empty($trainer->schoolid)) {
        if ($DB->record_exists('course_categories', ['id' => $trainer->schoolid])) {
            $canmanage = $DB->record_exists('schoolassign', [
                'userid' => $pocuserid,
                'schoolid' => $trainer->schoolid,
            ]);
        } else {
            $canmanage = ((int) $trainer->createdby === (int) $pocuserid);
        }
    } else {
        $canmanage = ((int) $trainer->createdby === (int) $pocuserid);
    }
}
if (!$canmanage) {
    throw new moodle_exception('nopermissions', 'error', '', 'Assign School');
}

if (is_siteadmin()) {
                
    $PAGE->navbar->add('Trainer Management', new moodle_url('/local/trainer/index.php'));
    $PAGE->navbar->add("Assign School's");
}
else{
    $PAGE->navbar->add("Assign School's", "$CFG->wwwroot/local/regionalpoc/rm_arm_manage.php?roleid=13");
    
}
echo $OUTPUT->header();

if (is_siteadmin()) {
    $schools = $DB->get_records_sql(
        "SELECT cc.id AS course_cat_id, COALESCE(sc.school_name, cc.name) AS school_name
           FROM {course_categories} cc
      LEFT JOIN {school} sc ON sc.course_cat_id = cc.id
          WHERE cc.id <> :schoolid
            AND (
                sc.course_cat_id IS NOT NULL
                OR EXISTS (
                    SELECT 1
                      FROM {schoolassign} sa
                     WHERE sa.schoolid = cc.id
                )
            )
       ORDER BY school_name",
        ['schoolid' => !empty($trainer->schoolid) ? $trainer->schoolid : 0]
    );
} else {
    $schools = $DB->get_records_sql(
        "SELECT cc.id AS course_cat_id, COALESCE(sc.school_name, cc.name) AS school_name
           FROM {schoolassign} sa
           JOIN {course_categories} cc ON cc.id = sa.schoolid
      LEFT JOIN {school} sc ON sc.course_cat_id = cc.id
          WHERE sa.userid = :pocuserid
            AND sa.schoolid <> :schoolid
       ORDER BY school_name",
        [
            'pocuserid' => $pocuserid,
            'schoolid' => !empty($trainer->schoolid) ? $trainer->schoolid : 0,
        ]
    );
}

$assignedschool = [];
if (!empty($trainer->schoolid)) {
    $assignedschool = $DB->get_records_sql(
        "SELECT cc.id AS course_cat_id, COALESCE(sc.school_name, cc.name) AS school_name
           FROM {course_categories} cc
      LEFT JOIN {school} sc ON sc.course_cat_id = cc.id
          WHERE cc.id = :schoolid",
        ['schoolid' => $trainer->schoolid]
    );
}

?>
<div class="top-container">
    <h2>Assign School's </h2>
    <input type="hidden" id="userId" value='<?php echo ($userId); ?>'>
    <input type="hidden" id="returnurl" value='<?php echo s($returnurl); ?>'>

    <div class="select-container">
        <div class="select-box box left-box" id="left-box">
            <h4>Available Schools (<span id="available-count">0</span>)</h4>
            <input type="text" id="left-search" class="form-control" placeholder="Search...">
            <div id="left-list" class="scrollable-list">
                <?php
                if (!empty($schools)) {
                    foreach ($schools as $school) {
                        echo "<label><input type='checkbox' value='$school->course_cat_id'> " . s($school->school_name) . "</label>";
                    }
                }
                ?>
            </div>
        </div>
        <div class="middle-controls">
            <button id="move-right" class="btn btn-secondary">Add ►</button>
            <button id="move-left" class="btn btn-secondary">◄ Remove</button>
           
            
        </div>
        <div class="select-box box right-box form-control" id="right-box">
            <h4>Assigned School (<span id="assigned-count">0</span>)</h4>
            <input type="text" id="right-search" class="form-control" placeholder="Search...">
            <div id="right-list" class="scrollable-list">
                <?php
                if (!empty($assignedschool)) {
                    foreach ($assignedschool as $school) {
                        echo "<label><input type='checkbox' value='$school->course_cat_id'> " . s($school->school_name) . "</label>";
                    }
                }
                ?>
            </div>
        </div>
    </div>
</div>
<?php
echo $OUTPUT->footer();
?>
