<?php

require_once(__DIR__ . '/../../../config.php');
require(__DIR__ . '/../../../local/hierarchy/lib.php');

global $CFG,$DB;
$cat_id = required_param('cat_id',PARAM_INT);
$selected_id = required_param('selected',PARAM_INT);
$option='';





$node_array=array("0"=>"Select department"); 
$table='loc';
add_nodes_in_select_array($table, 0, $cat_id, $node_array);




if(count($node_array) > 1) {
	foreach($node_array as $value=>$text)
	{	$seltext='';
		if($value==$selected_id){
			$seltext="selected";
		}
		$option.="<option value='$value' $seltext>$text</option>";
	}
}
else
{
	$option.="<option value=\"0\">Select department</option>";
}
echo $option;
?>