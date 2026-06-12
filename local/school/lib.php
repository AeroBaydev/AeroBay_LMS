<?php

/**
 * Returns the filemanager options used for school banners.
 *
 * @return array
 */
function local_school_get_banner_file_options(): array
{
    return [
        'accepted_types' => ['.jpg', '.jpeg', '.png', '.webp'],
        'maxbytes' => 5 * 1024 * 1024,
        'maxfiles' => 1,
        'subdirs' => 0,
    ];
}

/**
 * Returns the stored banner URL for a school, or the local fallback image.
 *
 * @param int $schoolid
 * @return moodle_url
 */
function local_school_get_banner_url(int $schoolid): moodle_url
{
    global $CFG;

    $context = context_system::instance();
    $files = get_file_storage()->get_area_files(
        $context->id,
        'local_school',
        'banner',
        $schoolid,
        'id DESC',
        false
    );
    $file = reset($files);

    if ($file) {
        return moodle_url::make_pluginfile_url(
            $context->id,
            'local_school',
            'banner',
            $schoolid,
            $file->get_filepath(),
            $file->get_filename()
        );
    }

    return new moodle_url($CFG->wwwroot . '/local/school/pix/default_banner.svg');
}

/**
 * Serves school banner files.
 *
 * @param stdClass|null $course
 * @param stdClass|null $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function local_school_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = [])
{
    global $DB;

    if ($context->contextlevel !== CONTEXT_SYSTEM || $filearea !== 'banner') {
        return false;
    }

    require_login();

    $schoolid = (int) array_shift($args);
    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args) . '/';

    if (!$DB->record_exists('school', ['id' => $schoolid])) {
        return false;
    }

    $file = get_file_storage()->get_file(
        $context->id,
        'local_school',
        'banner',
        $schoolid,
        $filepath,
        $filename
    );
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
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
