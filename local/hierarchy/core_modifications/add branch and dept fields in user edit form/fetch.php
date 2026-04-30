<?php 
require('../config.php');
require('../local/hierarchy/lib.php');

global $CFG,$DB;
$cat_id = required_param('cat_id',PARAM_INT);
$selected_id = required_param('selected',PARAM_INT);
$option='';





$node_array=array(null=>'Select department'); 
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
	$option.="<option>no result found</option>";
}
echo $option;
?>