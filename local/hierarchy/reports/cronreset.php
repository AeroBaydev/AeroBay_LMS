<?php
define("CLI_SCRIPT",true);
$conn=mysqli_connect('localhost', 'root', '','vaidusi');
//i//$query="SELECT * FROM mdl_custom_tenants where status=1  and cronstatus=0 limit 0,1";
//$tin = mysqli_query($conn,$query) or die(mysql_error());
 //$date=date("Hi");
   //      if($date=="2355"){
$query2="update mdl_custom_tenants set cronstatus=0";
 $tin2 = mysqli_query($conn,$query2);
//}
?>
