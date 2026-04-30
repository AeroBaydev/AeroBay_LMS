<?php

class trainer_table extends table_sql
{


    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);

        $columns = array('serialno', 'trainderid','Fullname', 'contact', 'designation', 'edit');
        $this->define_columns($columns);
       
       

        $headers = array('S.No', 'Trainer ID','Full Name', 'Contact', 'Designation', 'Action');
        $this->define_headers($headers);

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
        global $CFG;
        $button_html = "<a href='#' class='btn btn-primary' title='Assign School'><i class='fa fa-school'></i></a>
         <a href='$CFG->wwwroot/local/trainer/edit_trainer_form.php?id=$values->id' class='btn btn-primary' title='Edit Trainer'><i class='fa fa-pencil'></i></a>|<a href='$CFG->wwwroot/local/trainer/delete_trainer.php?id=$values->id' class='btn btn-primary'><i class='fa fa-trash' title='Delete Trainer'></i></a>";
        return $button_html;
    }


    function define_headers($headers)
    {
        parent::define_headers($headers);
        $this->no_sorting('edit');
   
        

    }
}
