<?php
date_default_timezone_set('Asia/Kolkata');

$dates = [
    'May 31 2026' => strtotime('2026-05-31 00:00:00'),
    'June 1 2026' => strtotime('2026-06-01 00:00:00'),
    'June 2 2026' => strtotime('2026-06-02 00:00:00'),
    'July 1 2026' => strtotime('2026-07-01 00:00:00'),
];

foreach ($dates as $name => $timestamp) {
    echo "$name -> $timestamp\n";
}
