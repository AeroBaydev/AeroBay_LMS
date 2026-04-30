<?php
namespace local_mydashboard;


defined('MOODLE_INTERNAL') || die();

class observers {
  
public static function f1(\core\event\dashboard_viewed $event) {
  

    global $DB,$CFG,$OUTPUT,$PAGE;
  
      //  $redirecturl = new \moodle_url('/my/index2.php');
        // $message = 'You are being redirected...';
        // $delay = 3;

        // // // Perform the redirect
        // $redirecturl="https://dev.icloudcampus.com/update/local/coursecopy/";
        //  redirect($redirecturl, $message, $delay);
        echo'<script>
        window.location.replace("'.$CFG->wwwroot.'/mydashboard");
        </script>';
// echo "<script> alert()</script>";
    // echo  $OUTPUT->render_from_template('local_mydashbord/mydashboard', $data);
  
 
}



}