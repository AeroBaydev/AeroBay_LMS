<?php
$monthstart = mktime(0, 0, 0, date('n'), 1, date('Y'));
echo date_default_timezone_get() . "\n";
echo "monthstart: " . $monthstart . "\n";
