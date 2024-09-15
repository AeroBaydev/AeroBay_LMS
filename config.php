<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'edutechspark.com';
$CFG->dbname    = 'update1';
$CFG->dbuser    = 'admin';
$CFG->dbpass    = 'root@root123';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => 3306,
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_0900_ai_ci',
);

// $CFG->debug = 32767;         // DEBUG_DEVELOPER // NOT FOR PRODUCTION SERVERS!
// // for Moodle 2.0 - 2.2, use:  $CFG->debug = 38911;  
// $CFG->debugdisplay = true;   // NOT FOR PRODUCTION SERVERS!

// // You can specify a comma separated list of user ids that that always see
// // debug messages, this overrides the debug flag in $CFG->debug and $CFG->debugdisplay
// // for these users only.
// $CFG->debugusers = '2';
 // $CFG->debug = 32767; $CFG->debugdisplay = 1;
$CFG->wwwroot   = 'https://edutechspark.com/update';
$CFG->dataroot  = '/var/www/updatedata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

require_once(__DIR__ . '/lib/setup.php');
