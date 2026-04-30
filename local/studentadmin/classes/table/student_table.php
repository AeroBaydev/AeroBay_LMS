<?php
require_once($CFG->libdir . '/tablelib.php');

class student_table extends table_sql {
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $this->define_columns(array('serial', 'studentid','username', 'firstname', 'lastname', 'email', 'actions'));
        $this->define_headers(array('Serial', 'studentid','Username', 'First Name', 'Last Name', 'Email', 'Actions'));
        $this->sortable(true);
        $this->collapsible(true);
    }
}
?>
