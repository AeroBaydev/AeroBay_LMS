<?php

require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot . '/local/school/lib.php');
global $tsort, $page;

$tsort = optional_param('tsort', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);


class news_class_table extends table_sql
{


    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);

        $columns = array('serialno', 'newstext','schoolid', 'timecreated','edit');
        $this->define_columns($columns);

        $headers = array('S.No', 'News','School',  'Date', 'Action');
        $this->define_headers($headers);
        $this->column_class('news', 'column-news');
        $is_downloading = optional_param('download', '', PARAM_RAW);

        if ($is_downloading) {
            array_pop($columns);
            array_pop($headers);
            $this->define_columns($columns);
            $this->define_headers($headers);
        }
    }

    function col_timecreated($values)
    {

        $times = date("d-M-Y", $values->timecreated);
        return $times;
    }
    function col_schoolid($values) {
        global $CFG, $DB;
        
        // Split the school IDs (comma-separated) into an array
         $school_ids = explode(',', $values->schoolid);
        
        // Prepare a SQL query to get the names of the schools by their IDs
        $school_names = [];
        foreach ($school_ids as $school_id) {
            $school = $DB->get_record_sql(
                "SELECT name FROM {course_categories} WHERE id = ?", 
                [$school_id]
            );
            
            // If the school is found, add its name to the array
            if ($school) {
                $school_names[] = $school->name;
            }
        }
       
        // Return the school names as a comma-separated string
        return implode(', ', $school_names);
    }
    

    // function col_gradeid($values)
    // {

    //     global $CFG, $DB;
    //     $grade_name = $DB->get_record_sql(
    //         "SELECT id, name FROM {course_categories} WHERE id = ?", 
    //         [$values->gradeid]
    //     );
        
    //     return $grade_name ? $grade_name->name : "ALL Grade";
    // }

    function col_edit($values)
    {
        global $CFG, $DB;

         $is_status = $DB->get_field('news', 'status', ['id' => $values->newsid]);

    // Set icon and title based on the user's status
    if ($is_status) {
        $toggle_icon = 'fa fa-eye';  // Eye icon for suspending (currently active)
        
         $toggle_title = 'IN-Activate News';  // Tooltip for suspending
        $toggle_action = 'Hide';  // Action to perform
        
    } else {
        $toggle_icon = 'fa fa-eye-slash';  // Eye-slash icon for activating (currently suspended)
 
        $toggle_title = 'Activate News';  // Tooltip for activating
        $toggle_action = 'Activate';  // Action to perform
       
    }

        $button_html = "
        <a href='{$CFG->wwwroot}/local/news/edit_news.php?id={$values->newsid}' class='btn btn-primary mr-2' title='Edit School'>
        <i class='fa fa-pencil'></i>
    </a>
        <a href='{$CFG->wwwroot}/local/news/delete_news.php?id={$values->newsid}' class='btn btn-primary mr-2' title='Delete School'>
            <i class='fa fa-trash'></i>
        </a>
         <a href='#' class='btn btn-link icon-no-margin p-1 ml-1 toggle-action' id='news_$values->newsid'  data-id='$values->newsid' data-action='$toggle_action' title='$toggle_title'>
            <i class='$toggle_icon  fa-fw' title='$toggle_title' id='news_icon_$values->newsid' role='img' aria-label='$toggle_title'></i>
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
        $this->no_sorting('newstext');
        $this->no_sorting('schoolid');
        $this->no_sorting('gradeid');
        $this->no_sorting('gradeid');
    }
}
