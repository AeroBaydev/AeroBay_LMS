<?php
if (is_siteadmin()) {
function local_school_extend_navigation(global_navigation $navigation)
{
    global $CFG, $PAGE;

    $icon = new pix_icon('key', '', 'local_school', array('class' => 'icon pluginicon', 'style' => 'width: 22px; height: 30px; object-fit: contain;'));

    $previewnode = $PAGE->navigation->add(
        get_string('school', 'local_school'),
        new moodle_url($CFG->wwwroot . '/local/school/school_manage.php'),
        navigation_node::TYPE_CONTAINER
    );

    $previewnode->add(
        get_string('all_cohort', 'local_school'),
        new moodle_url($CFG->wwwroot . '/cohort/index.php?contextid=1&showall=1')
    );

    $previewnode->add(
        get_string('category', 'local_school'),
        new moodle_url($CFG->wwwroot . '/course/management.php')
    );

    $previewnode->add(
        get_string('add_student', 'local_school'),
        new moodle_url($CFG->wwwroot . '/local/school/cohort_form1.php')
    );

    $previewnode->add(
        get_string('course_copy', 'local_school'),
        new moodle_url($CFG->wwwroot . '/local/coursecopy/')
    );

  
}
}
function state()
{
    $states = [
        '' => get_string('state_name', 'local_school'),
        'Andhra Pradesh' => 'Andhra Pradesh',
        'Andaman and Nicobar Islands' => 'Andaman and Nicobar Islands',
        'Arunachal Pradesh' => 'Arunachal Pradesh',
        'Assam' => 'Assam',
        'Bihar' => 'Bihar',
        'Chandigarh' => 'Chandigarh',
        'Chhattisgarh' => 'Chhattisgarh',
        'Dadra and Nagar Haveli and Daman and Diu' => 'Dadra and Nagar Haveli and Daman and Diu',
        'Delhi' => 'Delhi',
        'Goa' => 'Goa',
        'Gujarat' => 'Gujarat',
        'Haryana' => 'Haryana',
        'Himachal Pradesh' => 'Himachal Pradesh',
        'Jammu and Kashmir' => 'Jammu and Kashmir',
        'Jharkhand' => 'Jharkhand',
        'Karnataka' => 'Karnataka',
        'Ladakh' => 'Ladakh',
        'Lakshadweep' => 'Lakshadweep',
        'Kerala' => 'Kerala',
        'Madhya Pradesh' => 'Madhya Pradesh',
        'Maharashtra' => 'Maharashtra',
        'Manipur' => 'Manipur',
        'Meghalaya' => 'Meghalaya',
        'Mizoram' => 'Mizoram',
        'Nagaland' => 'Nagaland',
        'Odisha' => 'Odisha',
        'Puducherry' => 'Puducherry',
        'Punjab' => 'Punjab',
        'Rajasthan' => 'Rajasthan',
        'Sikkim' => 'Sikkim',
        'Tamil Nadu' => 'Tamil Nadu',
        'Telangana' => 'Telangana',
        'Tripura' => 'Tripura',
        'Uttar Pradesh' => 'Uttar Pradesh',
        'Uttarakhand' => 'Uttarakhand',
        'West Bengal' => 'West Bengal',
    ];
    return $states;
}

/**
 * Prints the serial numbers in ascending order.
 * 
 * @param int $values
 * @return int
 */
function sr($values)
{
    global $page, $a, $sr;
    static $start = 1;
    static $a = 0;
    static $sr = 0;

    if (!isset($page)) {
        $page = optional_param('page', 0, PARAM_INT);
    }

    if ($page == 0) {
        if ($a == 0) {
            $a++;
        }
        return $a++;
    } else {
        $a++;
        $sr = $a + ($page * 10);
        return $sr;
    }
}
