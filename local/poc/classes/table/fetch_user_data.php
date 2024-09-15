<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once('../../../../config.php');
global $DB;

$id = required_param('id', PARAM_INT);
// die('hi');

// Fetch the user data from the database based on the provided ID
// $user_data = $DB->get_record_sql("SELECT username,phone1 FROM {user} where id = $id");
$user_data = $DB->get_records_sql("SELECT acc.id,acc.name as name,abs.school_id as uniqueschoolid ,ac.id as contextid,au.id as userid FROM aero_course_categories acc 
JOIN aero_context ac on acc.id=ac.instanceid 
join aero_role_assignments ara on ara.contextid=ac.id 
join aero_user au on au.id=ara.userid 
left join aero_school abs on abs.course_cat_id = acc.id	 where au.id= $id");
echo"
<table class='appointtable' border='1'>
<thead>
  <tr>
    <th>Serial No</th>
    <th>School Name</th>
    <th>School ID</th>
  </tr>
</thead>";

$n=1;
foreach($user_data as $user){
    if ($user_data) {
        echo "
        <tbody>
          <!-- Your table rows (data) would go here -->
          <tr>
            <td>$n</td>
            <td>$user->name</td>
            <td>$user->uniqueschoolid</td>
          </tr>
          
        </tbody>

        ";
        $n+=1;
        // echo "<p>Fullname: {$user_data->username}</p>";
        // echo "<p>Contact: {$user_data->phone1}</p>";
        // echo "<p>Role: {$user_data->role}</p>";
        // echo "<p>Designation: {$user_data->designation}</p>";
        // Add more fields as needed
    } else {
        echo "<p>No user data found for the provided ID.</p>";
    }
  
}

echo "</table>";
// var_dump($user_data);
die;



?>
