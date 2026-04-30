<?php
$currenturl=array();
$currenturl=explode('.', @$_SERVER['HTTP_HOST']);
if(count($currenturl)){
$tenantdomain=$currenturl[0];
}
//$save_name='UserReport.xlsx';
$file="user_".$tenantdomain.".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename='.$file);
header('Cache-Control: max-age=0');
readfile($file);
ob_end_clean();
?>

