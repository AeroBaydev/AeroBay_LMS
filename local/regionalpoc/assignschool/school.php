<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/regionalpoc/lib.php');

require_login();
local_regionalpoc_require_regional_manager();

global $CFG, $DB, $OUTPUT, $PAGE, $USER;

$armid = required_param('id', PARAM_INT);
$usertype = optional_param('usertype', 'arm', PARAM_ALPHA);
if ($usertype !== 'arm') {
    $usertype = 'arm';
}

$conditions = [
    'userid' => $armid,
    'usertype' => 'asstmanager',
];
if (!is_siteadmin()) {
    $conditions['pocid'] = $USER->id;
}
$arm = $DB->get_record('regionalpoc', $conditions, '*', MUST_EXIST);

$assignableschools = local_regionalpoc_get_assignable_school_options((int) $USER->id);
$assignedschoolids = is_siteadmin() ?
    local_regionalpoc_get_stored_arm_school_ids($armid) :
    local_regionalpoc_get_arm_school_ids($armid);
$assignedschoolids = array_values(array_intersect($assignedschoolids, array_map('intval', array_keys($assignableschools))));
$otherarmassignedschoolids = local_regionalpoc_get_other_arm_assigned_school_ids(
    $armid,
    is_siteadmin() ? 0 : (int) $USER->id
);

if (optional_param('add', '', PARAM_RAW) !== '') {
    $selected = optional_param_array('available_select', [], PARAM_INT);
    $assignedschoolids = array_values(array_unique(array_merge($assignedschoolids, $selected)));
    $assignedschoolids = array_values(array_intersect($assignedschoolids, array_map('intval', array_keys($assignableschools))));
    local_regionalpoc_save_arm_school_assignments($armid, $assignedschoolids, (int) $USER->id);
    redirect(new moodle_url('/local/regionalpoc/assignschool/school.php', ['id' => $armid, 'usertype' => $usertype]));
} else if (optional_param('remove', '', PARAM_RAW) !== '') {
    $selected = optional_param_array('assigned_select', [], PARAM_INT);
    $assignedschoolids = array_values(array_diff($assignedschoolids, $selected));
    local_regionalpoc_save_arm_school_assignments($armid, $assignedschoolids, (int) $USER->id);
    redirect(new moodle_url('/local/regionalpoc/assignschool/school.php', ['id' => $armid, 'usertype' => $usertype]));
}

$availableoptions = array_diff_key($assignableschools, array_flip(array_merge($assignedschoolids, $otherarmassignedschoolids)));
$assignedoptions = array_intersect_key($assignableschools, array_flip($assignedschoolids));

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/regionalpoc/assignschool/school.php', ['id' => $armid, 'usertype' => $usertype]));
$PAGE->set_pagelayout('course');
$PAGE->set_title('Assign Schools');
$PAGE->set_heading('Assign Schools');
$PAGE->navbar->add('Assistant Regional Manager Management', new moodle_url('/local/regionalpoc/rm_arm_manage.php', ['usertype' => 'arm']));
$PAGE->navbar->add("Assign School's", $PAGE->url);

echo $OUTPUT->header();

echo html_writer::link(
    new moodle_url('/local/regionalpoc/rm_arm_manage.php', ['usertype' => $usertype]),
    '&lt;&lt; Back to Assistant Regional Manager Management'
);
echo html_writer::tag('h2', "Assign School's", ['class' => 'custom-heading assign-arm-school']);
?>
<form id="assignform" method="post" action="<?php echo $PAGE->url->out(false); ?>">
    <div>
        <input type="hidden" name="id" value="<?php echo (int) $armid; ?>">
        <input type="hidden" name="usertype" value="<?php echo s($usertype); ?>">
        <table summary="" class="roleassigntable generaltable generalbox boxaligncenter" cellspacing="0">
            <tr>
                <td id="availablecell">
                    <p><label for="id_available_select">Available Schools</label></p>
                    <?php echo local_regionalpoc_render_school_select($availableoptions, 'available_select'); ?>
                </td>
                <td id="buttonscell">
                    <div id="addcontrols">
                        <input name="add" id="add" type="submit" value="Add &#9658;" class="btn btn-secondary" title="Add">
                    </div>
                    <div id="removecontrols" style="margin-top: 8px;">
                        <input name="remove" id="remove" type="submit" value="&#9668; Remove" class="btn btn-secondary" title="Remove">
                    </div>
                </td>
                <td id="assignedcell">
                    <p><label for="id_assigned_select">Assigned Schools</label></p>
                    <?php echo local_regionalpoc_render_school_select($assignedoptions, 'assigned_select'); ?>
                </td>
            </tr>
        </table>
    </div>
</form>
<?php
echo $OUTPUT->footer();

function local_regionalpoc_render_school_select(array $options, string $name): string {
    $html = html_writer::start_tag('select', [
        'name' => $name . '[]',
        'id' => 'id_' . $name,
        'multiple' => 'multiple',
        'size' => 10,
        'class' => 'form-control no-overflow',
    ]);

    if (empty($options)) {
        $html .= html_writer::tag('option', 'No school found', ['disabled' => 'disabled']);
    } else {
        foreach ($options as $id => $schoolname) {
            $html .= html_writer::tag('option', s($schoolname), ['value' => (int) $id]);
        }
    }

    $html .= html_writer::end_tag('select');
    return $html;
}

function local_regionalpoc_get_other_arm_assigned_school_ids(int $armid, int $pocid): array {
    global $DB;

    $params = [
        'armid' => $armid,
        'usertype' => 'asstmanager',
    ];
    $pocwhere = '';
    if ($pocid > 0) {
        $params['pocid'] = $pocid;
        $pocwhere = ' AND rp.pocid = :pocid';
    }

    $schoolids = [];
    if ($DB->get_manager()->table_exists('regionalpoc_arm_school')) {
        $schoolids = array_merge($schoolids, $DB->get_fieldset_sql(
            "SELECT DISTINCT ras.schoolid
               FROM {regionalpoc_arm_school} ras
               JOIN {regionalpoc} rp ON rp.userid = ras.userid
              WHERE rp.usertype = :usertype
                AND rp.userid <> :armid
                    {$pocwhere}",
            $params
        ));
    }

    $schoolids = array_merge($schoolids, $DB->get_fieldset_sql(
        "SELECT DISTINCT sa.schoolid
           FROM {schoolassign} sa
           JOIN {regionalpoc} rp ON rp.userid = sa.userid
          WHERE rp.usertype = :usertype
            AND rp.userid <> :armid
            AND sa.schoolid IS NOT NULL
            AND sa.schoolid <> 0
                {$pocwhere}",
        $params
    ));

    return array_values(array_unique(array_filter(array_map('intval', $schoolids))));
}
