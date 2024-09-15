<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class pocmapping_form extends moodleform {
   
    public function definition() {
        global $DB; 

        $mform = $this->_form;
        $school = $DB->get_records_sql_menu(
            "SELECT cc.id, ss.school_name 
             FROM {school} ss 
             JOIN {course_categories} cc 
             ON ss.school_sortname = cc.name"
        );
        $poc = $DB->get_records_sql_menu(
            "SELECT pc.userid, pc.firstname, pc.lastname
            FROM {poc} pc"
        );
        $role1= $DB->get_records_sql_menu("SELECT id,name as shortname FROM {role} where shortname in ('manager','editingteacher','teacher')");
        $role1[0]='Select Role';
        ksort($role1);
        
                // $mform->addElement('select', 'poc', get_string('selectpoc', 'local_poc'), array('' => get_string('selectpoc', 'local_poc')) + $poc);
                // $mform->setType('poc', PARAM_INT);
                // $mform->addRule('poc', null, 'required', null, 'client');

                $mform->addElement('select', 'role', get_string('selectrole', 'local_poc') , $role1);
                // $mform->setType('role', PARAM_INT);
                $mform->addRule('role', null, 'required', null, 'client');
                // $mform->addElement('autocomplete', 'school', get_string('selectschool', 'local_poc'), $school, ['class' => 'ritikclass']);
                // $mform->getElement('school')->setMultiple(true);
                // foreach ($school as $id => $name) {
                //     $mform->addElement(
                //         'advcheckbox', 
                //         'school' . $id, 
                //         get_string('selectschool', 'local_poc'), 
                //         $name, 
                //         array('group' => 1), 
                //         array(0, 1)
                //     );
                // }
                // $mform->addElement('advcheckbox', 'school', get_string('selectschool', 'local_poc'), 'Label displayed after checkbox', array('group' => 1), array(0, 1));
                
                // This will select the skills A and B.
                // $mform->getElement('school')->setSelected(array('val1', 'val2'));
                
        // $mform->addElement('select', 'school', get_string('selectschool', 'local_poc'), array('' => get_string('selectschool', 'local_poc')) + $school);
        // $mform->setType('school', PARAM_INT);
        // $mform->addRule('school', null, 'required', null, 'client');
        
        $this->add_action_buttons(true, get_string('add_poc','local_poc'));
    }
}
?>

<script>
    const category_select = document.querySelector(".custom-select");
    
    console.log('hioo')
$(document).ready(function () {
    $("input[type='text']").on('change', function(){
        console.log("SSS");
        console.log($(this).val());
    })
    $("#id_school").on('change', function(){
        console.log($(this).val);
    })
  $("#id_school").CreateMultiCheckBox({ width: '230px',
             defaultText : 'Select Below', height:'250px' });
});
</script>