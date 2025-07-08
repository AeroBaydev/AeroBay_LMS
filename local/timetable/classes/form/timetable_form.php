<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
require_once("$CFG->dirroot/local/timetable/lib.php");
 $PAGE->requires->js(new moodle_url("$CFG->wwwroot/local/timetable/style/main.js"));


class timetable_form extends moodleform
{
    public function definition()
    {
        global $DB;

        $mform = $this->_form;

        // Hidden timetable ID
        $mform->addElement('hidden', 'timetableid');
        $mform->setType('timetableid', PARAM_INT);

        // Heading
        $heading_text = "Add timetable";
        $heading = html_writer::tag('h2', $heading_text, array('class' => 'custom-heading add-new-school'));
        $mform->addElement('html', $heading);

        // timetable text field
        $customdata = $this->_customdata;
        $mform->addElement('hidden', 'catid', $customdata['catid']);
        $mform->setType('catid', PARAM_INT);
    
        $mform->addElement('hidden', 'schoolid', $customdata['schoolid']);
        $mform->setType('schoolid', PARAM_INT);
      

        // $school_options=[];
        // $school_options = $DB->get_records_sql_menu(
        //     "SELECT cc.id, cc.name
        //      FROM {schoolassign} sa
        //      JOIN {course_categories} cc ON sa.schoolid = cc.id"
        // );
        // $school_options = array(0 => get_string('pleaseselectschool', 'local_timetable')) + $school_options;

        // $mform->addElement('select', 'school', get_string('school', 'local_timetable'), $school_options);
        // $mform->setType('school', PARAM_INT);
        // $mform->addRule('school', get_string('required'), 'required', null, 'client');
        // //   echo $_POST['school'];
        
        // $grade_options=[];
        // if($_POST['school']){
        //     // die;
        //    $schoolid=$_POST['school'];
        //    $grade_options = $DB->get_records_sql_menu(
        //     "SELECT cc.id, cc.name
        //      from {course_categories} cc where cc.parent= $schoolid"
        // );
        //  }
        // // Add Grade dropdown
        // $grade_options = array(0 => get_string('pleaseselectgrade', 'local_timetable')) + $grade_options;
        // $mform->addElement('select', 'grade', get_string('grade', 'local_timetable'), $grade_options);
        // $mform->setType('grade', PARAM_TEXT);
        //  $mform->addRule('grade', get_string('required'), 'required', null, 'client');


         // Dropdown options for periods (1 to 7)
                $period_options = [
                    '' => get_string('selectperiod', 'local_timetable'), // Placeholder
                    1 => 'Period 1',
                    2 => 'Period 2',
                    3 => 'Period 3',
                    4 => 'Period 4',
                    5 => 'Period 5',
                    6 => 'Period 6',
                    7 => 'Period 7'
                ];

                $mform->addElement('select', 'period', get_string('period', 'local_timetable'), $period_options);
                $mform->setType('period', PARAM_INT);
                $mform->addRule('period', get_string('required'), 'required', null, 'client');


                $day_options = [
                    '' => get_string('selectday', 'local_timetable'), // Placeholder
                    'monday'    => 'Monday',
                    'tuesday'   => 'Tuesday',
                    'wednesday' => 'Wednesday',
                    'thursday'  => 'Thursday',
                    'friday'    => 'Friday',
                    'saturday'  => 'Saturday',
                    'sunday'    => 'Sunday'
                ];
                
                $mform->addElement('select', 'day', get_string('day', 'local_timetable'), $day_options);
                $mform->setType('day', PARAM_ALPHANUMEXT);
                $mform->addRule('day', get_string('required'), 'required', null, 'client');



        // Hidden field for time created
        $mform->addElement('hidden', 'timecreated', time());
        $mform->setType('timecreated', PARAM_INT);

        // Submit button
        $this->add_action_buttons(true, get_string('savechanges', 'local_timetable'));
    }

    public function validation($data, $files)
    {
        global $DB;
        $errors = parent::validation($data, $files);

        // Validate if the school exists
        // if (!$DB->record_exists('school', array('id' => $data['schoolid']))) {
        //     $errors['schoolid'] = get_string('invalidschool', 'local_timetable');
        // }

        // Validate if the grade exists
        // if (!$DB->record_exists('grade', array('id' => $data['gradeid']))) {
        //     $errors['gradeid'] = get_string('invalidgrade', 'local_timetable');
        // }

        return $errors;
    }
}
