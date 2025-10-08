<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'db';
$CFG->dbname    = 'update_mapping';
$CFG->dbuser    = 'root';
$CFG->dbpass    = 'rootpass';
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
//  $CFG->debug = 32767; $CFG->debugdisplay = 1;

$CFG->wwwroot   = 'http://88.222.214.159:8000/update';
$CFG->dataroot  = '/var/www/moodledata/updatedata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

require_once(__DIR__ . '/lib/setup.php');
