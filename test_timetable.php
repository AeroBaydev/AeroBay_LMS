<?php
define('CLI_SCRIPT', true);
require('config.php');
global $DB, $USER;

$USER = $DB->get_record('user', ['id' => 2]); // Admin or someone? We need a valid trainer userid, or we can just mock the timetable_schoolid.

// Let's find a valid trainer.
$trainer = $DB->get_record_sql("SELECT * FROM {trainer} LIMIT 1");
$trainerschoolid = $trainer ? $trainer->schoolid : 0;
if (!$trainerschoolid) {
    $trainerschoolid = $DB->get_field('schoolassign', 'schoolid', ['userid' => $trainer->userid ?? 0]);
}

// Fallback to a schoolid that has timetable entries
if (!$trainerschoolid) {
    $tt = $DB->get_record_sql("SELECT schoolid FROM {timetable} LIMIT 1");
    $trainerschoolid = $tt ? $tt->schoolid : 362; // 362 was a schoolid in previous queries
}

$timetable_schoolid = (int)$trainerschoolid;
echo "School ID: $timetable_schoolid\n";

$period_roman = ['1'=>'I','2'=>'II','3'=>'III','4'=>'IV','5'=>'V','6'=>'VI','7'=>'VII','8'=>'VIII','9'=>'IX','10'=>'X'];
$week_days    = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

$timetable_all = [];
$all_rows = $DB->get_records_sql(
    'SELECT t.id, t.day, t.period,
            t.schoolid, t.gradeid,
            cc.name AS gradename,
            CAST(pcc.courseid AS UNSIGNED) AS courseid,
            c.fullname AS coursename
       FROM {timetable} t
  LEFT JOIN {course_categories} cc ON cc.id = t.gradeid
  LEFT JOIN {poc_copy_course} pcc
         ON CAST(pcc.schoolid AS UNSIGNED) = t.schoolid
        AND CAST(pcc.gradeid  AS UNSIGNED) = t.gradeid
        AND pcc.status = 1
  LEFT JOIN {course} c ON c.id = pcc.courseid
      WHERE t.schoolid = :schoolid
   ORDER BY CAST(t.period AS UNSIGNED)',
    ['schoolid' => $timetable_schoolid]
);

$courseids = [];
foreach ($all_rows as $row) {
    $courseid = (int) ($row->courseid ?? 0);
    if ($courseid > 0) {
        $courseids[$courseid] = $courseid;
    }
}

$sectionsbycourse = [];
if (!empty($courseids)) {
    list($coursesql, $courseparams) = $DB->get_in_or_equal(array_values($courseids), SQL_PARAMS_NAMED, 'ttcourse');
    $sectionrows = $DB->get_records_sql(
        "SELECT id, course, section, name, visible
           FROM {course_sections}
          WHERE course {$coursesql}
            AND section > 0
       ORDER BY course, section",
        $courseparams
    );

    foreach ($sectionrows as $section) {
        $sectioncourseid = (int) $section->course;
        if (!isset($sectionsbycourse[$sectioncourseid])) {
            $sectionsbycourse[$sectioncourseid] = [];
        }

        $sectionnumber = (int) $section->section;
        $sectionsbycourse[$sectioncourseid][] = [
            'sectionid' => (int) $section->id,
            'sectionnumber' => $sectionnumber,
            'sectionname' => !empty($section->name) ? format_string($section->name) : 'Session ' . $sectionnumber,
            'visible' => (int) $section->visible,
        ];
    }
}

$progressbysection = [];
// MOCK PROGRESS AS WE DON'T NEED REAL ONE TO CHECK IF SESSIONS EXIST

foreach ($week_days as $wd) {
    $timetable_all[$wd] = [];
}
foreach ($all_rows as $row) {
    $day_key = $row->day;
    if (!isset($timetable_all[$day_key])) {
        $timetable_all[$day_key] = [];
    }
    $period_num = trim((string) $row->period);
    $courseid = (int) ($row->courseid ?? 0);
    $sessions = [];
    foreach ($sectionsbycourse[$courseid] ?? [] as $section) {
        $sessions[] = $section + [
            'status' => 'pending',
            'completeddays' => 0,
            'timecompleted' => 0,
            'trainerid' => 0,
        ];
    }

    $timetable_all[$day_key][] = [
        'period'     => isset($period_roman[$period_num]) ? 'Period ' . $period_roman[$period_num] : 'Period ' . $period_num,
        'gradename'  => !empty($row->gradename)  ? $row->gradename  : '—',
        'coursename' => !empty($row->coursename) ? $row->coursename : '—',
        'courseid'   => $courseid,
        'sessions'   => $sessions,
    ];
}

$timetablejson = json_encode($timetable_all, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
echo "1. EXACT final timetablejson string length: " . strlen($timetablejson) . "\n";

foreach ($timetable_all as $day => $rows) {
    foreach ($rows as $row) {
        if (!empty($row['sessions'])) {
            echo "2. Print ONE raw JSON row exactly before json_encode:\n";
            print_r($row);
            $found = true;
            break 2;
        }
    }
}

