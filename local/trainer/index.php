<?php

require_once('../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/local/trainer/classes/table/trainer_table.php');

require_login();

$page = optional_param('page', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_TEXT);

$context = context_system::instance();
$PAGE->set_context($context);
require_capability('local/trainer:view', $context);
require_admin();
$PAGE->set_url(new moodle_url('/local/trainer/index.php', ['search' => $search, 'page' => $page]));
$PAGE->set_pagelayout('course');
$PAGE->set_title('Trainer Management');
$PAGE->set_heading('');
$PAGE->navbar->add('Trainer Management', $PAGE->url);
$PAGE->requires->css(new moodle_url('/local/students/customedit.css'));

$table = new trainer_table('admin_trainer_listing', true);
$table->is_downloading($download, 'trainer_data', 'trainer_data');
$schoolassigncolumns = $DB->get_columns('schoolassign');
$schoolassignpocfield = array_key_exists('schoolassignby', $schoolassigncolumns) ? 'sa.schoolassignby' : 'sa.schoolassignee';

if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    echo html_writer::start_tag('style');
    echo '
        #page-local-trainer-index .page-header-headings {
            display: none;
        }
        .trainer-management-page .action-button-container {
            margin-bottom: 15px;
        }
        .trainer-management-page .action-button-container form {
            align-items: center;
            width: 100%;
        }
        .trainer-management-page .table-responsive,
        .trainer-management-page .no-overflow {
            overflow-x: auto;
        }
        .trainer-management-page table {
            min-width: 1080px;
        }
        .trainer-management-page table th,
        .trainer-management-page table td {
            vertical-align: middle;
        }
        .trainer-management-page .c6 {
            max-width: 260px;
            min-width: 220px;
            white-space: normal;
        }
        .trainer-name-link {
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
        }
        .trainer-name-link:hover,
        .trainer-name-link:focus {
            text-decoration: underline;
        }
        .trainer-compact-list,
        .trainer-course-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            max-width: 260px;
        }
        .trainer-list-pill,
        .trainer-course-summary,
        .trainer-list-more {
            background: #eff6ff;
            border: 1px solid #dbeafe;
            border-radius: 999px;
            color: #1d4ed8;
            display: inline-flex;
            font-size: 12px;
            font-weight: 750;
            line-height: 1.2;
            max-width: 100%;
            padding: 5px 8px;
        }
        .trainer-course-summary {
            background: #f8fafc;
            border-color: #e4e7ec;
            color: #344054;
        }
        .trainer-list-more {
            background: #f2f4f7;
            border-color: #e4e7ec;
            color: #475467;
            cursor: help;
        }
        .trainer-empty-value {
            color: #98a2b3;
        }
        .trainer-status {
            border-radius: 999px;
            display: inline-flex;
            font-size: 12px;
            font-weight: 850;
            padding: 6px 10px;
        }
        .trainer-status-active {
            background: #d4edda;
            color: #155724;
        }
        .trainer-status-inactive {
            background: #e2e3e5;
            color: #383d41;
        }
        .trainer-actions {
            align-items: center;
            display: inline-flex;
            gap: 6px;
            white-space: nowrap;
        }
        .trainer-management-page .downloadreport {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
            margin: 8px 0 14px;
        }
        @media (max-width: 760px) {
            .trainer-management-page .action-button-container form {
                align-items: stretch !important;
                flex-direction: column;
                gap: 8px;
            }
            .trainer-management-page .action-button-container .form-control,
            .trainer-management-page .action-button-container .btn {
                margin-right: 0 !important;
                width: 100%;
            }
        }
    ';
    echo html_writer::end_tag('style');
    echo html_writer::start_div('trainer-management-page');
    echo html_writer::tag('h2', 'Trainer Management', ['class' => 'custom-heading add-trainer']);
    echo html_writer::start_div('action-button-container');
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'class' => 'd-flex',
        'action' => (new moodle_url('/local/trainer/index.php'))->out(false),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'search',
        'class' => 'ml-auto form-control rounded mr-2',
        'name' => 'search',
        'placeholder' => 'Search...',
        'value' => s($search),
    ]);
    echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Search', 'class' => 'btn btn-primary mr-2']);
    echo html_writer::link(new moodle_url('/local/trainer/index.php'), 'Clear', ['class' => 'btn btn-secondary mr-2']);
    echo html_writer::end_tag('form');
    echo html_writer::end_div();
}

$fields = "(@row_number := @row_number + 1) AS serialno,
           tr.userid AS id,
           u.firstname AS firstname,
           u.lastname AS lastname,
           u.email AS email,
           tr.contact_number AS contact,
           GROUP_CONCAT(DISTINCT COALESCE(sc.school_name, schoolcat.name) ORDER BY COALESCE(sc.school_name, schoolcat.name) SEPARATOR ', ') AS assignedschools,
           GROUP_CONCAT(DISTINCT COALESCE(NULLIF(CONCAT(pu.firstname, ' ', pu.lastname), ' '), pc.firstname, pc.username) ORDER BY pu.firstname, pu.lastname SEPARATOR ', ') AS assignedpocs,
           GROUP_CONCAT(DISTINCT c.fullname ORDER BY c.fullname SEPARATOR ', ') AS assignedcourses,
           CASE WHEN u.suspended = 1 THEN 'Inactive' ELSE 'Active' END AS statuslabel";

$from = "{trainer} tr
         JOIN {user} u ON u.id = tr.userid
    LEFT JOIN {trainer_course_mapping} tcm ON tcm.traineruserid = tr.userid AND (tcm.status IS NULL OR tcm.status = 1)
    LEFT JOIN {schoolassign} sa ON sa.userid = tr.userid
    LEFT JOIN {course_categories} schoolcat ON schoolcat.id = COALESCE(tcm.schoolid, tr.schoolid, sa.schoolid)
    LEFT JOIN {school} sc ON sc.course_cat_id = schoolcat.id
    LEFT JOIN {user} pu ON pu.id = COALESCE(tcm.pocid, tr.createdby, $schoolassignpocfield)
    LEFT JOIN {poc} pc ON pc.userid = pu.id
    LEFT JOIN {course} c ON c.id = tcm.courseid";

$where = "u.deleted = 0";
$params = [];

if ($search) {
    $where .= " AND (u.firstname LIKE :search1
                 OR u.lastname LIKE :search2
                 OR u.email LIKE :search3
                 OR tr.contact_number LIKE :search4
                 OR tr.trainerid LIKE :search5
                 OR sc.school_name LIKE :search6
                 OR schoolcat.name LIKE :search7
                 OR c.fullname LIKE :search8
                 OR pu.firstname LIKE :search9
                 OR pu.lastname LIKE :search10)";
    $params = [
        'search1' => "%$search%",
        'search2' => "%$search%",
        'search3' => "%$search%",
        'search4' => "%$search%",
        'search5' => "%$search%",
        'search6' => "%$search%",
        'search7' => "%$search%",
        'search8' => "%$search%",
        'search9' => "%$search%",
        'search10' => "%$search%",
    ];
}

$groupsort = " GROUP BY tr.userid, u.firstname, u.lastname, u.email, tr.contact_number, u.suspended
               ORDER BY u.firstname, u.lastname";

$perpage = 10;
$DB->execute('SET @row_number := ' . ($perpage * $page));
$table->set_sql($fields, $from, $where . $groupsort, $params);
$table->set_count_sql("SELECT COUNT(DISTINCT tr.userid) FROM $from WHERE $where", $params);
$table->define_baseurl(new moodle_url('/local/trainer/index.php', ['search' => $search]));

if ($table->is_downloading()) {
    $table->out($perpage, true);
    exit;
}

$table->out($perpage, true);
echo html_writer::end_div();
echo $OUTPUT->footer();
