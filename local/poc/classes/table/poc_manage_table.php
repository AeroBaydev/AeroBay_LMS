<?php

class poc_manage_table extends table_sql
{
    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);

        $columns = array('edit');
        $this->define_columns($columns);

        $headers = array(' POC Action');
        $this->define_headers($headers);
        $this->no_sorting('schoolcount');
        $this->no_sorting('edit');

        $is_downloading = optional_param('download', '', PARAM_RAW);

        if ($is_downloading) {
            array_pop($columns);
            array_pop($headers);
            $this->define_columns($columns);
            $this->define_headers($headers);
        }
        foreach ($columns as $column) {
            $this->no_sorting($column);
        }

    }


    function col_edit($values)
{
    global $CFG, $OUTPUT;
    
    $urlpocschool = new moodle_url('/local/pocschool/', array('userid' => $values->id));
    $urltrainer = new moodle_url('/local/trainer/trainer_manage.php', array('userid' => $values->id));
    $urlstudent = new moodle_url('/local/students/student_manage.php',array('userid' => $values->id));

    $button_html = html_writer::link($urlpocschool,
        html_writer::tag('i', '', array('class' => 'fa-solid fa-school-circle-check')) . ' School POC',
        array('class' => 'btn btn-info', 'title' => 'Edit POC Details')
    );

    $button_html .= html_writer::link($urltrainer,
        html_writer::tag('i', '', array('class' => 'fa-solid fa-chalkboard-user')) . ' Trainer POC',
        array('class' => 'btn btn-info', 'title' => 'Delete POC')
    );

    $button_html .= html_writer::link($urlstudent,
        html_writer::tag('i', '', array('class' => 'fa fa-user')) . 'Student POC',
        array('class' => 'btn btn-info', 'title' => 'View Student')
    );

    return $button_html;
}
    function col_schoolcount($values)
    {
        global $DB;


        $userid = $values->id;
        $data = $DB->get_record_sql("SELECT COUNT(sa.userid) as schoolcount FROM {schoolassign} sa  join {course_categories} cc on  sa.schoolid=cc.id WHERE sa.userid = ?", [$userid]);

        return $data->schoolcount;
    }
    function define_headers($headers)
    {
        parent::define_headers($headers);
        $this->no_sorting('edit');
        
     
    }


}
