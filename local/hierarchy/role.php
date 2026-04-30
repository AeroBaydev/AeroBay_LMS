<?php

require_once('../../config.php');
global $CFG, $DB, $USER;
require_login();

$check = optional_param('check',0, PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_title('Role and Permission');
$PAGE->set_heading('Role and Permission');
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/local/role/role.php',array('check'=>$check)); // shortened url must start with backslash 
purge_all_caches();
echo $OUTPUT->header();

$context=context_system::instance();

//$CorsCreateV = $CorsEditV = $CorsDeleteV = $UsrCreateV = $UsrEditV = $UsrDeleteV = $UsrViewV = $CorsViewV = '0' ;
//$CorsCreateC = $CorsEditC = $CorsDeleteC = $UsrCreateC = $UsrEditC = $UsrDeleteC = $UsrViewC = $CorsViewC = '' ;

$result = $DB -> get_records_sql('select r.id, r.shortname from {role} r inner join {role_context_levels} rl on rl.roleid = r.id where rl.contextlevel = 10');
//$result1 = $DB ->get_records_sql('SELECT b.* FROM {role_context_levels} a INNER JOIN {role_capabilities} b ON a.roleid = b.roleid WHERE a.contextlevel = 10');


$narray = array();
$b = array();
$c = array();
foreach($result as $record)
{
	  $narray[$record->shortname] = "";
	  for($i=0; $i<8; $i++)
	  {
		  $c[$record->shortname.$i] = '0'; 
	  }
}
foreach($result as $name)
{
	$result1 = $DB ->get_records_sql('SELECT b.* FROM {role_context_levels} a INNER JOIN {role_capabilities} b ON a.roleid = b.roleid WHERE a.contextlevel = 10 AND b.roleid =?',array($name->id));
	if($result1)
	{
		foreach($result1 as $record)
		{	
						if($c[$name->shortname."0"] != '1')
						{
							if($record->capability == 'moodle/course:create')
							{
								$b['CorsCreateN'] = $name->shortname.'CorsCreate';
								$b['CorsCreateC'] = 'checked';
								$c[$name->shortname."0"] = '1';
							}
							else
							{
								$b['CorsCreateN'] = $name->shortname.'CorsCreate';
								$b['CorsCreateC'] = '';
							}
						}

						if($c[$name->shortname."1"] != '1')
						{
							if($record->capability == 'moodle/course:update')
							{
								$b['CorsEditN'] = $name->shortname.'CorsEdit';
								$b['CorsEditC'] = 'checked';
								$c[$name->shortname."1"] = '1';
							}
							else
							{
								$b['CorsEditN'] = $name->shortname.'CorsEdit';
								$b['CorsEditC'] = '';
							}
						}

						if($c[$name->shortname."2"] != '1')
						{
							if($record->capability == 'moodle/course:delete')
							{
								$b['CorsDeleteN'] = $name->shortname.'CorsDelete';
								$b['CorsDeleteC'] = 'checked';
								$c[$name->shortname."2"] = '1';
							}
							else
							{
								$b['CorsDeleteN'] = $name->shortname.'CorsDelete';
								$b['CorsDeleteC'] = '';
							}
						}

						if($c[$name->shortname."3"] != '1')
						{
							if($record->capability == 'moodle/user:create')
							{
								$b['UsrCreateN']= $name->shortname.'UsrCreate';
								$b['UsrCreateC']= 'checked';
								$c[$name->shortname."3"] = '1';
							} 
							else
							{
								$b['UsrCreateN']= $name->shortname.'UsrCreate';
								$b['UsrCreateC'] = '';
							}
						}

						if($c[$name->shortname."4"] != '1')
						{
							if($record->capability == 'enrol/manual:manage')
							{
								$b['UsrEditN'] = $name->shortname.'UsrEdit';
								$b['UsrEditC'] = 'checked';
								$c[$name->shortname."4"] = '1';
							}
							else
							{
								$b['UsrEditN'] = $name->shortname.'UsrEdit';
								$b['UsrEditC'] = '';
							}
						}

						if($c[$name->shortname."5"] != '1')
						{
							if($record->capability == 'enrol/manual:unenrol')
							{
								$b['UsrDeleteN'] = $name->shortname.'UsrDelete';
								$b['UsrDeleteC'] = 'checked';
								$c[$name->shortname."5"] = '1';
							}
							else
							{
								$b['UsrDeleteN'] = $name->shortname.'UsrDelete';
								$b['UsrDeleteC'] = '';
							}
						}

						if($c[$name->shortname."6"] != '1')
						{
							if($record->capability == 'moodle/course:viewparticipants')
							{
								$b['UsrViewN'] = $name->shortname.'UsrView';
								$b['UsrViewC'] = 'checked';
								$c[$name->shortname."6"] = '1';
							}
							else
							{
								$b['UsrViewN'] = $name->shortname.'UsrView';
								$b['UsrViewC'] = '';
							}
						}

					if($c[$name->shortname."7"] != '1')
					{
						if($record->capability == 'moodle/course:view')
						{
							$b['CorsViewN'] = $name->shortname.'CorsView';
							$b['CorsViewC'] = 'checked';
							$c[$name->shortname."7"] = '1';
						}
						else
						{
							$b['CorsViewN'] = $name->shortname.'CorsView';
							$b['CorsViewC'] = '';
						}
					}
			
		}
		$narray[$name->shortname] = $b;
	}
	
	else
	{
		$b['CorsCreateN'] = $name->shortname.'CorsCreate';
		$b['CorsCreateC'] = '';
		$b['CorsEditN'] = $name->shortname.'CorsEdit';
		$b['CorsEditC'] = '';
		$b['CorsDeleteN'] = $name->shortname.'CorsDelete';
		$b['CorsDeleteC'] = '';
		$b['UsrCreateN']= $name->shortname.'UsrCreate';
		$b['UsrCreateC'] = '';
		$b['UsrEditN'] = $name->shortname.'UsrEdit';
		$b['UsrEditC'] = '';
		$b['UsrDeleteN'] = $name->shortname.'UsrDelete';
		$b['UsrDeleteC'] = '';
		$b['UsrViewN'] = $name->shortname.'UsrView';
		$b['UsrViewC'] = '';
		$b['CorsViewN'] = $name->shortname.'CorsView';
		$b['CorsViewC'] = '';
		$narray[$name->shortname] = $b;
		
	}
}
echo "<form action='submit.php' method='post'>";

$table = new html_table();
$table->attributes['class'] = 'table table-striped table-hover table-bordered custom-table';
$table -> head = array('Roles', 'View (Can view all of assign users)',	'Create (Can create users)',	'Edit (Can edit all assign users)',	'Delete (Can delete all assign users)',	
					   'View (Can view all of assign course)',	'Create (Can create course)',	'Edit (Can edit all assign course)',	'Delete (Can delete all assign course)');

foreach ($narray as $record=>$m) 
{
	$role = $record;
    $UsrView     =html_writer::empty_tag('input', array('id' => '','type' => 'checkbox', 'name' => $m['UsrViewN'], '', $m['UsrViewC']=>'checked'));
	$UsrCreate    =html_writer::empty_tag('input', array('id' => '','type' => 'checkbox', 'name' => $m['UsrCreateN'],'', $m['UsrCreateC']=>'checked'));
	$UsrEdit     =html_writer::empty_tag('input', array('id' => '','type' => 'checkbox', 'name' => $m['UsrEditN'],'' , $m['UsrEditC']=>'checked'));
	$UsrDelete   =html_writer::empty_tag('input', array('id' => '','type' => 'checkbox', 'name' => $m['UsrDeleteN'],'', $m['UsrDeleteC']=>'checked'));
	$CorsView     =html_writer::empty_tag('input', array('id' => '','type' => 'checkbox', 'name' => $m['CorsViewN'],'', $m['CorsViewC']=>'checked'));
	$CorsCreate       =html_writer::empty_tag('input', array('id' => '','type' => 'checkbox', 'name' => $m['CorsCreateN'],'',$m['CorsCreateC']=>'checked'));
	$CorsEdit       =html_writer::empty_tag('input', array('id' => '','type' => 'checkbox', 'name' => $m['CorsEditN'],'', $m['CorsEditC']=>'checked'));
	$CorsDelete =html_writer::empty_tag('input', array('id' => '','type' => 'checkbox', 'name' => $m['CorsDeleteN'],'', $m['CorsDeleteC']=>'checked'));
	$table->data[] =array($role,$UsrView,$UsrCreate,$UsrEdit,$UsrDelete,$CorsView,$CorsCreate,$CorsEdit,$CorsDelete);
}

echo html_writer::table($table);

echo html_writer::tag('button','Set Capability');
echo "</form>";
echo $OUTPUT->footer();

?>
