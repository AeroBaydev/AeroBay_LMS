<?php
function sr1($values)
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
