<?php

require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot . '/local/school/lib.php');
global $tsort, $page;

$tsort = optional_param('tsort', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);


class school_class_table extends table_sql
{
    /** @var bool */
    private $readonly = false;

    function __construct($uniqueid, bool $readonly = false)
    {
        parent::__construct($uniqueid);
        $this->readonly = $readonly;

        $columns = array('serialno', 'school_name','school_sortname','school_code', 'principal_name', 'edit');
        $headers = array('S.No', 'School Name','School Short Name','School ID', 'Principal Name', 'Action');

        $is_downloading = optional_param('download', '', PARAM_RAW);

        if ($is_downloading || $this->readonly) {
            array_pop($columns);
            array_pop($headers);
        }

        $this->define_columns($columns);
        $this->define_headers($headers);
    }

    function col_timecreated($values)
    {

        $times = date("d-m-Y", $values->timecreated);
        return $times;
    }

    function col_edit($values)
    {

        global $CFG, $DB;
        if ($this->readonly) {
            return '';
        }

        $school = $DB->get_record('course_categories', array('idnumber' => $values->school_code), 'id, visible');

        $button_html = "
        <a href='{$CFG->wwwroot}/local/school/edit_school.php?id={$values->schoolid}' class='btn btn-primary mr-2' title='Edit School'>
        <i class='fa fa-pencil'></i>
    </a>
        <a href='{$CFG->wwwroot}/local/school/delete_school.php?id={$values->schoolid}&school_sortname={$values->school_code}' class='btn btn-primary mr-2' title='Delete School'>
            <i class='fa fa-trash'></i>
        </a>
        <a href='{$CFG->wwwroot}/local/school/school_profile.php?id={$values->schoolid}' class='btn btn-primary mr-2' title='View School Profile'>
            <i class='fa fa-user-circle'></i>
        </a>";

        if ($school) {
            if ($school->visible == 1) {
                $button_html .= "
                <a href='{$CFG->wwwroot}/local/school/toggle_school.php?name={$values->school_sortname}' class='btn btn-primary mr-2' title='Disable School'>
                    <i class='fa fa-eye'></i>
                </a>";
            } else {
                $button_html .= "
                <a href='{$CFG->wwwroot}/local/school/toggle_school.php?name={$values->school_sortname}' class='btn btn-primary mr-2' title='Enable School'>
                    <i class='fa fa-eye-slash'></i>
                </a>";
            }
        } else {
        }

        return $button_html;
    }

    function col_serialno($values)
    {
        return sr($values);
    }

    function col_school_name($values)
    {
        if ($this->readonly) {
            return format_string($values->school_name);
        }

        $url = new moodle_url('/local/school/students.php', ['schoolid' => $values->schoolid]);

        return html_writer::link($url, format_string($values->school_name), [
            'class' => 'text-primary',
            'title' => 'View students by grade',
        ]);
    }

    function define_headers($headers)
    {
        parent::define_headers($headers);
        $this->no_sorting('edit');
        $this->no_sorting('serialno');
        $this->no_sorting('school_name');
        $this->no_sorting('school_shortname');
        $this->no_sorting('school_code');
        $this->no_sorting('principal_name');
    }
}
