<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

// ================= DATABASE SETTINGS =================
$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = '127.0.0.1';     // Use IP (better than localhost)
$CFG->dbname    = 'lms_db';        // Your imported DB
$CFG->dbuser    = 'root';          // MySQL user
$CFG->dbpass    = 'root123';       // Your MySQL password
$CFG->prefix    = 'mdl_';

$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => 3306,
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_0900_ai_ci',
);

// ================= SITE SETTINGS =================
$CFG->wwwroot   = 'http://localhost/lms';   // Local URL

// ================= DATA DIRECTORY =================
$CFG->dataroot  = 'C:\\lmsdata';   // Windows path

// ================= ADMIN =================
$CFG->admin     = 'admin';

// ================= PERMISSIONS =================
$CFG->directorypermissions = 0777;

// ================= OPTIONAL DEBUG (FOR DEV) =================
// Uncomment if you want to see errors
// $CFG->debug = 32767;
// $CFG->debugdisplay = 1;

// ================= DO NOT TOUCH =================
require_once(__DIR__ . '/lib/setup.php');