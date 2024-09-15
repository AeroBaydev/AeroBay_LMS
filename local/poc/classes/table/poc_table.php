<?php

class poc_table extends table_sql
{
    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);

        $columns = array('serial', 'employeid', 'Fullname', 'contact',  'schoolcount',  'edit');
        $this->define_columns($columns);

        $headers = array('S.No', 'Employee Id', 'Full Name', 'Contact No',  'Assigned School', 'Action');
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
    global $CFG, $DB;

    // Determine if the user is currently suspended or active
    $is_suspended = $DB->get_field('poc', 'suspended', ['userid' => $values->id]);

    // Set icon and title based on the user's status
    if ($is_suspended) {
        $toggle_icon = 'fa fa-eye-slash';  // Eye-slash icon for activating (currently suspended)
        $toggle_title = 'Activate POC';  // Tooltip for activating
        $toggle_action = 'activate';  // Action to perform
    } else {
        $toggle_icon = 'fa fa-eye';  // Eye icon for suspending (currently active)
        $toggle_title = 'Suspend POC';  // Tooltip for suspending
        $toggle_action = 'suspend';  // Action to perform
    }

    // HTML for the different action buttons
    $button_html = "
        <a href='$CFG->wwwroot/local/poc/assignschool/school.php?id=$values->id' title='Assign School' class='btn btn-primary'>
            <i class='fa-solid fa-school-circle-check'></i>
        </a>

        <a href='$CFG->wwwroot/local/poc/edit_poc_form.php?id=$values->id' class='btn btn-primary' title='Edit POC Details'>
            <i class='fa fa-cog'></i>
        </a>

        <a href='$CFG->wwwroot/local/poc/delete_poc.php?id=$values->id' class='btn btn-primary' title='Delete POC'>
            <i class='fa fa-trash'></i>
        </a>

        <a href='$CFG->wwwroot/local/poc/pocmange?userid=$values->id' class='btn btn-primary' title='View POC Control'>
            <i class='fa fa-user'></i>
        </a>

         <a href='#' class='btn btn-link icon-no-margin p-1 ml-1 toggle-action' id='poc_$values->id'  data-id='$values->id' data-action='$toggle_action' title='$toggle_title'>
            <i class='$toggle_icon  fa-fw' title='$toggle_title' id='poc_icon_$values->id' role='img' aria-label='$toggle_title'></i>
        </a>";

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
