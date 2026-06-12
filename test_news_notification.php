<?php
define('CLI_SCRIPT', true);
require('config.php');
require_once($CFG->dirroot . '/local/news/lib.php');

global $DB;

$news = new stdClass();
$news->news = 'This is an automated test announcement for school 470 grade 8.';
$news->schoolid = '470'; 
$news->gradeid = '8'; 
$news->timecreated = time();
$news->status = 1;

$news->id = $DB->insert_record('news', $news);
echo "Inserted news ID: {$news->id}\n";

local_news_send_notifications($news);
