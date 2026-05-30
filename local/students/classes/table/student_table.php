<?php
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');
require_once($CFG->dirroot . '/local/students/approval_lib.php');

class student_table extends table_sql
{
    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);

        // Define columns including the new checkbox column
        $columns = local_pocschool_is_trainer_user()
            ? array('serialno', 'studentid', 'Fullname')
            : array('checkbox', 'serialno', 'studentid', 'Fullname');
        if (!local_pocschool_is_trainer_user()) {
            $columns = array_merge($columns, array('edit', 'approve', 'approvedby'));
        }
        $this->define_columns($columns);

        // Define headers including the new checkbox column
        $headers = local_pocschool_is_trainer_user()
            ? array('S.No', 'Student Id', 'Full Name')
            : array( '<input type="checkbox" id="select-all" />', 'S.No', 'Student Id', 'Full Name');
        if (!local_pocschool_is_trainer_user()) {
            $headers = array_merge($headers, array('Action', 'Approve/Reject', 'Action By'));
        }
        $this->define_headers($headers);

        $is_downloading = optional_param('download', '', PARAM_RAW);

        if ($is_downloading && !local_pocschool_is_trainer_user()) {
            // Remove the checkbox and action columns during download
            array_splice($columns, -3, 1);
            array_splice($headers, -3, 1);
        
            array_splice($columns, -2, 1);
            array_splice($headers, -2, 1);
        
            $this->define_columns($columns);
            $this->define_headers($headers);
        }

        foreach ($columns as $column) {
            $this->no_sorting($column);
        }
    }

    // Define the checkbox column content
    function col_checkbox($values)
{
    global $DB;

    if (local_pocschool_is_trainer_user()) {
        return '';
    }

    // Get student record by userid
    $student = $DB->get_record('student', ['userid' => $values->id], 'status');

    $checked  = '';
    $disabled = '';

    if ($student && ($student->status == 1 || $student->status == 0)) {
        $checked  = 'checked';
        $disabled = 'disabled';
    }

    return '<input type="checkbox"
            class="student-select"
            value="'.$values->id.'"
            '.$checked.'
            '.$disabled.' />';
}


    function col_edit($values)
    {
        global $CFG;
        $i = $values->serialno - 1;
        $button_html = "<a href='$CFG->wwwroot/local/students/edit_student_form.php?id=$values->id' class='btn btn-primary mr-2' title='Edit student'><i class='fa fa-pencil'></i></a>
                        <a href='#' onclick='openDeletePopup(\"$values->id\",\"$i\"); return false;' title='Delete student' class='btn btn-primary'>Delete</a>";
        return $button_html;
    }

    function col_approvedby($values)
    {
        global $DB;
        $studentdata = $DB->get_record('student', array('userid' => $values->id));

        if (empty($studentdata->approvedby)) {
            return "_";
        }

        $prefix = 'Action By:';
        if ((int) $studentdata->status === 1) {
            $prefix = 'Approved By:';
        } else if ((int) $studentdata->status === 0) {
            $prefix = 'Rejected By:';
        }

        return $prefix . html_writer::empty_tag('br') . s(local_students_format_action_actor($studentdata->approvedby));
    }

    function col_approve($values)
    {
        global $OUTPUT, $CFG;

        if ($values->status == 1) {
            $button_html = "<button class='btn btn-success' disabled>Approved</button>";
        } elseif ($values->status == 0) {
            $button_html = "<button class='btn btn-danger' disabled>Rejected</button>";
        } else {
            $approveurl = new moodle_url('/local/students/approve_student.php', array('id' => $values->id));
            $approvebutton = html_writer::link($approveurl, 'Approve', array('class' => 'btn alert-success mr-2'));

            $rejecturl = new moodle_url('/local/students/reject_student.php', array('id' => $values->id));
            $rejectbutton = "<a href='#' onclick='openRejectPopup(\"$values->id\"); return false;' title='Reject Student' class='btn btn-danger'>Reject</a>";

            $button_html = $approvebutton . $rejectbutton;
        }

        return $button_html;
    }
}
?>
