<?php

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
