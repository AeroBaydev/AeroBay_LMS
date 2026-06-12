<?php
date_default_timezone_set('Asia/Kolkata'); // The system time seems to be +05:30
$monthstart = mktime(0, 0, 0, date('n'), 1, date('Y'));
$nextmonthstart = mktime(0, 0, 0, date('n') + 1, 1, date('Y'));
echo "monthstart: " . $monthstart . "\n";
echo "nextmonthstart: " . $nextmonthstart . "\n";
