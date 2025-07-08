<?php

require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot . '/local/school/lib.php');
global $tsort, $page;

$tsort = optional_param('tsort', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);


class timetable_class_table extends table_sql
{


    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);

        $columns = array('serialno','schoolid','gradeid','edit');
        $this->define_columns($columns);

        $headers = array('S.No', 'School','Grade ', 'Action');
        $this->define_headers($headers);

        $is_downloading = optional_param('download', '', PARAM_RAW);

        if ($is_downloading) {
            array_pop($columns);
            array_pop($headers);
            $this->define_columns($columns);
            $this->define_headers($headers);
        }
    }

    function col_image($values)
    {
        // Assuming $values->imgpath contains the full image URL stored in the database
        if (!empty($values->imgpath)) {
            return '<img src="' . $values->imgpath . '" alt="Badge Image" width="100" height="100">';
        } else {
            return 'No image available';
        }
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
        <a href='{$CFG->wwwroot}/local/timetable/taketimetable.php?schoolid={$values->schoolid}&gradeid={$values->gradeid}&timetableid={$values->timetableid}' class='btn btn-primary mr-2' title='Take timetable'>
        <i class='icon fa fa-play fa-fw'></i>
    </a>
        <a href='{$CFG->wwwroot}/local/timetable/delete_timetable.php?id={$values->timetableid}' class='btn btn-primary mr-2' title='Delete timetable'>
            <i class='fa fa-trash'></i>
        </a>
        ";
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
        $this->no_sorting('timetabletext');
        $this->no_sorting('schoolid');
        $this->no_sorting('gradeid');
        $this->no_sorting('gradeid');
    }
}
