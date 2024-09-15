<?php

class student_table extends table_sql
{
    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);

        // Define columns including the new checkbox column
        $columns = array('checkbox', 'serialno', 'studentid', 'Fullname', 'edit', 'approve', 'approvedby');
        $this->define_columns($columns);

        // Define headers including the new checkbox column
        $headers = array( '<input type="checkbox" id="select-all" />', 'S.No', 'Student Id', 'Full Name', 'Action', 'Approve/Reject', 'Approve By');
        $this->define_headers($headers);

        $is_downloading = optional_param('download', '', PARAM_RAW);

        if ($is_downloading) {
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
        return '<input type="checkbox" class="student-select" value="'.$values->id.'" />';
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

        if (isset($studentdata->approvedby)) {
            return $studentdata->approvedby;
        } else {
            return "_";
        }
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
