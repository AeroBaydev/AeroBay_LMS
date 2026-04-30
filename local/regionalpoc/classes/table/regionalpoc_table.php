<?php


class regionalpoc_table extends table_sql
{
  function __construct($uniqueid)
  {
    parent::__construct($uniqueid);

    $usertype = $_GET['usertype'];

    if ($usertype == "rm") {
      $columns = array('serialno', 'Fullname', 'contact',   'appointment', 'designation','edit');
      $this->define_columns($columns);

      $headers = array('S.No', 'Full Name', 'Contact',  'Assigned ARM', 'Designation','Action');
      $this->define_headers($headers);
    } 
    elseif ($usertype == "arm") {
      $columns = array('serialno', 'Fullname', 'contact', 'designation','edit');
      $this->define_columns($columns);

      $headers = array('S.No', 'Fullname', 'Contact', 'Designation','Action');
      $this->define_headers($headers);
    }

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

  function col_schools($values)
  {
    // global $DB;
    // $schools = $DB->get_record_sql("SELECT count(schoolid) as count from {schoolassign} where userid=$values->id");

    // return $schools->count;
  }

  function col_edit($values)
  {


    $usertype = $_GET['usertype'];

    if ($usertype == "rm") {
      global $CFG, $USER;
      $button_html = "<a href='$CFG->wwwroot/local/permissions/index.php?userId=$values->userid&usertype=$usertype'  title='Assign Permission' class='btn btn-primary mr-2'><i class='icon fa fa-unlock-alt fa-fw'></i></a> 
      <a href='$CFG->wwwroot/local/regionalpoc/assignschool/school.php?id=$values->userid&usertype=$usertype'  title='Assign School' class='btn btn-primary mr-2'><i class='fa-solid fa-school-circle-check'></i></a> 
      <a href='$CFG->wwwroot/local/regionalpoc/edit_rm_arm_form.php?id=$values->userid&usertype=$usertype' class='btn btn-primary mr-2' title='Edit '><i class='fa fa-cog'></i></a> 
      <a href='$CFG->wwwroot/local/regionalpoc/delete_regionalpoc.php?id=$values->userid&usertype=$usertype' class='btn btn-primary mr-2' title='Delete'><i class='fa fa-trash'></i></a>";
      return $button_html;
    } elseif ($usertype == "arm") {
      global $CFG;
      $button_html = "<a href='$CFG->wwwroot/local/permissions/index.php?userId=$values->userid&usertype=$usertype'  title='Assign Permission' class='btn btn-primary mr-2'><i class='icon fa fa-unlock-alt fa-fw'></i></a> 
      <a href='$CFG->wwwroot/local/assign_school_ARM/school.php?userId=$values->userid&usertype=$usertype'  title='Assign ' class='btn btn-primary mr-2'><i class='fa-solid fa-school-circle-check'></i></a> 
      <a href='$CFG->wwwroot/local/regionalpoc/edit_rm_arm_form.php?id=$values->userid&usertype=$usertype' class='btn btn-primary mr-2' title='Edit '><i class='fa fa-cog'></i></a> 
      <a href='$CFG->wwwroot/local/regionalpoc/delete_regionalpoc.php?id=$values->userid&usertype=$usertype' class='btn btn-primary mr-2' title='Delete'><i class='fa fa-trash'></i></a>";
      return $button_html;
    }

    
  }



  function col_appointment($values)
  {
    global $DB;

    $button_html = "<button type='button' data-userid='$values->userid' data-id='$values->id'  class='btn btn-primary appointment'>View</button>";

    return $button_html;
  }
}
