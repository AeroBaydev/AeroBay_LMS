<?php

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/user/lib.php');

require_login();

$context = context_system::instance();
require_capability('local/school:manage', $context);

$schoolid = required_param('schoolid', PARAM_INT);
$gradeid = optional_param('gradeid', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$action = optional_param('action', '', PARAM_ALPHA);
$selectedids = optional_param_array('studentids', [], PARAM_INT);

$perpage = 20;

$school = $DB->get_record('school', ['id' => $schoolid], '*', MUST_EXIST);
$schoolcategory = null;

if (!empty($school->course_cat_id)) {
    $schoolcategory = $DB->get_record('course_categories', ['id' => $school->course_cat_id]);
}

if (!$schoolcategory && !empty($school->school_id)) {
    $schoolcategory = $DB->get_record('course_categories', ['idnumber' => $school->school_id]);
}

if (!$schoolcategory) {
    print_error('invalidcategory', 'error');
}

if ($gradeid) {
    $grade = $DB->get_record('course_categories', [
        'id' => $gradeid,
        'parent' => $schoolcategory->id,
    ], '*', MUST_EXIST);
} else {
    $grade = null;
}

$baseurlparams = ['schoolid' => $schoolid];
if ($gradeid) {
    $baseurlparams['gradeid'] = $gradeid;
}
if ($search !== '') {
    $baseurlparams['search'] = $search;
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/school/students.php', $baseurlparams));
$PAGE->set_pagelayout('course');
$PAGE->set_title(format_string($school->school_name));
$PAGE->set_heading(format_string($school->school_name));
$PAGE->requires->css(new moodle_url('/local/students/customedit.css'));

$PAGE->navbar->add('Schools', new moodle_url('/local/school/index.php'));
$PAGE->navbar->add(format_string($school->school_name), new moodle_url('/local/school/students.php', ['schoolid' => $schoolid]));
if ($grade) {
    $PAGE->navbar->add(format_string($grade->name));
    $PAGE->navbar->add('Students');
}

if ($action === 'bulkdelete' && !empty($selectedids)) {
    require_sesskey();
    require_capability('moodle/user:delete', $context);

    if (!$grade) {
        print_error('missingparam', 'error', '', 'gradeid');
    }

    list($studentidssql, $studentidsparams) = $DB->get_in_or_equal($selectedids, SQL_PARAMS_NAMED, 'studentid');
    $deleteparams = array_merge($studentidsparams, [
        'schoolid' => $schoolcategory->id,
        'gradeid' => $grade->id,
    ]);

    $validstudents = $DB->get_records_sql("
        SELECT st.userid, u.id, u.firstname, u.lastname, u.email
          FROM {student} st
          JOIN {user} u ON u.id = st.userid
         WHERE st.userid {$studentidssql}
           AND st.schoolid = :schoolid
           AND st.gradeid = :gradeid
           AND u.deleted = 0
    ", $deleteparams);

    if (!$validstudents) {
        redirect(new moodle_url('/local/school/students.php', [
            'schoolid' => $schoolid,
            'gradeid' => $grade->id,
        ]), 'No matching students were selected for this school and grade.', null,
            \core\output\notification::NOTIFY_WARNING);
    }

    if (!$confirm) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading('Confirm bulk delete');

        $confirmurl = new moodle_url('/local/school/students.php', [
            'schoolid' => $schoolid,
            'gradeid' => $grade->id,
            'action' => 'bulkdelete',
            'confirm' => 1,
            'sesskey' => sesskey(),
        ]);

        $cancelurl = new moodle_url('/local/school/students.php', [
            'schoolid' => $schoolid,
            'gradeid' => $grade->id,
            'search' => $search,
        ]);

        echo html_writer::div('Delete ' . count($validstudents) .
            ' selected student(s)? This will delete the Moodle user account too.',
            'alert alert-warning');
        echo html_writer::start_tag('form', ['method' => 'post', 'action' => $confirmurl->out(false)]);
        foreach (array_keys($validstudents) as $userid) {
            echo html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'studentids[]',
                'value' => $userid,
            ]);
        }
        echo html_writer::tag('button', 'Delete selected students', [
            'type' => 'submit',
            'class' => 'btn btn-danger mr-2',
        ]);
        echo html_writer::link($cancelurl, get_string('cancel'), ['class' => 'btn btn-secondary']);
        echo html_writer::end_tag('form');
        echo $OUTPUT->footer();
        exit;
    }

    $deleted = 0;
    foreach ($validstudents as $student) {
        if ($user = $DB->get_record('user', ['id' => $student->userid, 'deleted' => 0])) {
            if (user_delete_user($user)) {
                $DB->delete_records('student', ['userid' => $student->userid]);
                $deleted++;
            }
        }
    }

    redirect(new moodle_url('/local/school/students.php', [
        'schoolid' => $schoolid,
        'gradeid' => $grade->id,
    ]), $deleted . ' student(s) deleted successfully.', null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

echo html_writer::tag('h2', format_string($school->school_name), ['class' => 'custom-heading add-new-school']);

if (!$grade) {
    $grades = $DB->get_records_sql("
        SELECT cc.id,
               cc.name,
               COUNT(u.id) AS totalstudents
          FROM {course_categories} cc
     LEFT JOIN {student} st ON st.gradeid = cc.id
                           AND st.schoolid = :schoolcatid
     LEFT JOIN {user} u ON u.id = st.userid
                       AND u.deleted = 0
         WHERE cc.parent = :parent
      GROUP BY cc.id, cc.name
      ORDER BY cc.sortorder ASC, cc.name ASC
    ", [
        'schoolcatid' => $schoolcategory->id,
        'parent' => $schoolcategory->id,
    ]);

    $table = new html_table();
    $table->head = ['Grade Name', 'Total Students Count', 'Action'];
    $table->attributes['class'] = 'generaltable table table-striped';

    foreach ($grades as $item) {
        $viewurl = new moodle_url('/local/school/students.php', [
            'schoolid' => $schoolid,
            'gradeid' => $item->id,
        ]);

        $table->data[] = [
            format_string($item->name),
            (int)$item->totalstudents,
            html_writer::link($viewurl, 'View Students', ['class' => 'btn btn-primary']),
        ];
    }

    if (empty($table->data)) {
        echo $OUTPUT->notification('No grades/classes found for this school.', \core\output\notification::NOTIFY_INFO);
    } else {
        echo html_writer::table($table);
    }

    echo $OUTPUT->footer();
    exit;
}

echo html_writer::tag('h3', format_string($grade->name) . ' Students');

$searchurl = new moodle_url('/local/school/students.php', [
    'schoolid' => $schoolid,
    'gradeid' => $grade->id,
]);

echo html_writer::start_div('d-flex justify-content-between mb-2');
echo html_writer::link(new moodle_url('/local/school/students.php', ['schoolid' => $schoolid]), 'Back to Grades', ['class' => 'btn btn-secondary']);
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $searchurl->out(false), 'class' => 'd-flex']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'schoolid', 'value' => $schoolid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'gradeid', 'value' => $grade->id]);
echo html_writer::empty_tag('input', [
    'type' => 'search',
    'class' => 'form-control rounded mr-2',
    'name' => 'search',
    'placeholder' => 'Search students...',
    'value' => s($search),
]);
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Search', 'class' => 'btn btn-primary mr-2']);
echo html_writer::link($searchurl, 'Clear', ['class' => 'btn btn-secondary']);
echo html_writer::end_tag('form');
echo html_writer::end_div();

$where = "st.schoolid = :schoolid AND st.gradeid = :gradeid AND u.deleted = 0";
$params = [
    'schoolid' => $schoolcategory->id,
    'gradeid' => $grade->id,
];

if ($search !== '') {
    $where .= " AND (" . $DB->sql_like('u.firstname', ':firstname', false) .
        " OR " . $DB->sql_like('u.lastname', ':lastname', false) .
        " OR " . $DB->sql_like('u.username', ':username', false) .
        " OR " . $DB->sql_like('u.email', ':email', false) . ")";
    $params['firstname'] = '%' . $search . '%';
    $params['lastname'] = '%' . $search . '%';
    $params['username'] = '%' . $search . '%';
    $params['email'] = '%' . $search . '%';
}

$total = $DB->count_records_sql("
    SELECT COUNT(1)
      FROM {student} st
      JOIN {user} u ON u.id = st.userid
     WHERE {$where}
", $params);

$students = $DB->get_records_sql("
    SELECT u.id,
           u.firstname,
           u.lastname,
           u.username,
           u.email,
           u.suspended,
           u.lastaccess,
           st.status,
           cc.name AS gradename
      FROM {student} st
      JOIN {user} u ON u.id = st.userid
      JOIN {course_categories} cc ON cc.id = st.gradeid
     WHERE {$where}
  ORDER BY u.firstname ASC, u.lastname ASC, u.id ASC
", $params, $page * $perpage, $perpage);

$bulkactionurl = new moodle_url('/local/school/students.php', [
    'schoolid' => $schoolid,
    'gradeid' => $grade->id,
    'search' => $search,
]);

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $bulkactionurl->out(false),
    'id' => 'school-students-form',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'bulkdelete']);

$table = new html_table();
$table->head = [
    html_writer::checkbox('selectall', 1, false, '', ['id' => 'select-all-students']),
    'Student Name',
    'Username',
    'Email',
    'Grade/Class',
    'Status',
    'Last Access',
    'Actions',
];
$table->attributes['class'] = 'generaltable table table-striped';

foreach ($students as $student) {
    $editurl = new moodle_url('/local/students/edit_student_form.php', ['id' => $student->id]);
    $deleteurl = new moodle_url('/local/students/delete_student.php', ['id' => $student->id]);
    $status = $student->suspended ? 'Suspended' : 'Active';
    if ((string)$student->status === '0') {
        $status = 'Rejected';
    } else if ((string)$student->status === '1') {
        $status = 'Approved';
    }

    $table->data[] = [
        html_writer::checkbox('studentids[]', $student->id, false, '', ['class' => 'student-checkbox']),
        fullname($student),
        s($student->username),
        s($student->email),
        format_string($student->gradename),
        $status,
        $student->lastaccess ? userdate($student->lastaccess) : 'Never',
        html_writer::link($editurl, html_writer::tag('i', '', ['class' => 'fa fa-pencil']), [
            'class' => 'btn btn-primary mr-2',
            'title' => 'Edit student',
        ]) . html_writer::link($deleteurl, 'Delete', [
            'class' => 'btn btn-primary',
            'title' => 'Delete student',
        ]),
    ];
}

if (empty($table->data)) {
    echo $OUTPUT->notification('No students found for this school and grade.', \core\output\notification::NOTIFY_INFO);
} else {
    echo html_writer::table($table);
    echo html_writer::tag('button', 'Bulk Delete', [
        'type' => 'submit',
        'class' => 'btn btn-danger',
        'id' => 'bulk-delete-students',
    ]);
    echo $OUTPUT->paging_bar($total, $page, $perpage, new moodle_url('/local/school/students.php', $baseurlparams));
}

echo html_writer::end_tag('form');

echo html_writer::script("
document.addEventListener('DOMContentLoaded', function() {
    var selectAll = document.getElementById('select-all-students');
    var form = document.getElementById('school-students-form');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.student-checkbox').forEach(function(checkbox) {
                checkbox.checked = selectAll.checked;
            });
        });
    }

    if (form) {
        form.addEventListener('submit', function(event) {
            var checked = document.querySelectorAll('.student-checkbox:checked').length;
            if (!checked) {
                event.preventDefault();
                alert('Please select at least one student.');
                return;
            }

            if (!confirm('Delete selected students? This will delete their Moodle user accounts too.')) {
                event.preventDefault();
            }
        });
    }
});
");

echo $OUTPUT->footer();
