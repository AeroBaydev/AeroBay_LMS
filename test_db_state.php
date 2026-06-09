<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');

global $DB;

$output = "";

$trainers = $DB->get_records('trainer');
$output .= "Total trainers in table: " . count($trainers) . "\n";
foreach ($trainers as $t) {
    $output .= "Trainer ID: {$t->id}, UserID: {$t->userid}, SchoolID: {$t->schoolid}\n";
}

$mappings = $DB->get_records('trainer_course_mapping');
$output .= "\nTotal mappings in table: " . count($mappings) . "\n";
foreach ($mappings as $m) {
    $output .= "Mapping ID: {$m->id}, TrainerUserID: {$m->traineruserid}, CourseID: {$m->courseid}, SchoolID: {$m->schoolid}, Status: {$m->status}\n";
}

file_put_contents('test_db_state.txt', $output);
