<?php

require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot . '/local/school/lib.php');
require_once($CFG->dirroot . '/local/pocschool/accesslib.php');
global $tsort, $page;

$tsort = optional_param('tsort', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);


class attendace_class_table extends table_sql
{


    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);

        $columns = array('serialno', 'description','schoolid','gradeid','date','edit');
        $this->define_columns($columns);

        $headers = array('S.No', 'Description','School','Grade ', 'Date','Action');
        $this->define_headers($headers);

        $is_downloading = optional_param('download', '', PARAM_RAW);

        if ($is_downloading) {
            array_pop($columns);
            array_pop($headers);
            $this->define_columns($columns);
            $this->define_headers($headers);
        }
    }

    function col_date($values) {
        global $DB;
    
        // Ensure $values->attendanceid exists before proceeding
        if (empty($values->attendanceid)) {
            return "N/A"; // Default value if attendance ID is missing
        }
    
        // Fetch the attendance date based on attendance ID
        $attendance_date = $DB->get_field_sql(
            "SELECT a.date as attendance_date FROM {attendance} a WHERE a.id = ?", 
            [$values->attendanceid]
        );
    
        // Return formatted date (if found), otherwise return a default value
        return $attendance_date ? date("d-M-Y", $attendance_date) : "-";
    }
    
    
    function col_schoolid($values)
    {
        global $CFG, $DB;
        $school_name = $DB->get_record_sql(
            "SELECT id, name FROM {course_categories} WHERE id = ?", 
            [$values->schoolid]
        );
        
        return $school_name ? $school_name->name : null;
    }

    function col_gradeid($values)
    {

        global $CFG, $DB;
        $grade_name = $DB->get_record_sql(
            "SELECT id, name FROM {course_categories} WHERE id = ?", 
            [$values->gradeid]
        );
        
        return $grade_name ? $grade_name->name : "ALL Grade";
    }

    function col_edit($values)
    {
        global $CFG, $DB;
        $button_html = "
        <a href='{$CFG->wwwroot}/local/attendance_new/takeattendance.php?schoolid={$values->schoolid}&catid={$values->gradeid}&attendanceid={$values->attendanceid}' class='btn btn-primary mr-2' title='Take attendance'>
        <i class='icon fa fa-play fa-fw'></i>
    </a>";
        if (!local_pocschool_is_trainer_user()) {
            $button_html .= "
        <a href='{$CFG->wwwroot}/local/attendance_new/delete_attendance.php?id={$values->attendanceid}&catid={$values->gradeid}&schoolid={$values->schoolid}' class='btn btn-primary mr-2' title='Delete attendance'>
            <i class='fa fa-trash'></i>
        </a>";
        }
        return $button_html;
    }

    function col_serialno($values)
    {
        return sr($values);
    }

    function define_headers($headers)
    {
        parent::define_headers($headers);
        $this->no_sorting('edit');
        $this->no_sorting('serialno');
        $this->no_sorting('badgecardtext');
        $this->no_sorting('schoolid');
        $this->no_sorting('gradeid');
        $this->no_sorting('gradeid');
    }
}
