<?php
require_once('../../config.php');
global $CFG, $DB;

$result = $DB -> get_records_sql('select r.id, r.shortname from {role} r inner join {role_context_levels} rl on rl.roleid = r.id where rl.contextlevel = 10');

$c = array();
foreach($result as $record)
{
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
								$c[$name->shortname."0"] = '1';
							}
						}

						if($c[$name->shortname."1"] != '1')
						{
							if($record->capability == 'moodle/course:update')
							{
								$c[$name->shortname."1"] = '1';
							}
						}

						if($c[$name->shortname."2"] != '1')
						{
							if($record->capability == 'moodle/course:delete')
							{
								$c[$name->shortname."2"] = '1';
							}
						}

						if($c[$name->shortname."3"] != '1')
						{
							if($record->capability == 'moodle/user:create')
							{
								$c[$name->shortname."3"] = '1';
							} 
						}

						if($c[$name->shortname."4"] != '1')
						{
							if($record->capability == 'enrol/manual:manage')
							{
								$c[$name->shortname."4"] = '1';
							}
						}

						if($c[$name->shortname."5"] != '1')
						{
							if($record->capability == 'enrol/manual:unenrol')
							{
								$c[$name->shortname."5"] = '1';
							}
						}

						if($c[$name->shortname."6"] != '1')
						{
							if($record->capability == 'moodle/course:viewparticipants')
							{
								$c[$name->shortname."6"] = '1';
							}
						}

					if($c[$name->shortname."7"] != '1')
					{
						if($record->capability == 'moodle/course:view')
						{
							$c[$name->shortname."7"] = '1';
						}
					}
		}
	}
}

function checkquery($id,$cap)
{
	global $DB;
	$check = $DB->get_record_sql('SELECT * FROM {role_capabilities} WHERE roleid = ? AND capability = ?',array($id,$cap));
	return $check;
}

function insertquery($id,$cap)
{ 
	global $DB;
    $DB->execute("insert into {role_capabilities} (contextid,roleid,capability,permission,timemodified) values(1,?,?,1,?)",array($id,$cap,time()));
}

function deletequery($id,$cap)
{
	global $DB;
	$DB->delete_records("role_capabilities", array('roleid' => $id, 'capability' => $cap));
}

foreach($result as $name)
{	
	//Edit Capability
	if($_POST[$name->shortname."UsrView"]== "on" && $c[$name->shortname."6"] == '0')
	{
		$capcheck = 'moodle/user:viewdetails';
		$check = checkquery($name->id,$capcheck);
		if($check == null)
		{
		   insertquery($name->id,$capcheck);
		}
		$capcheck = 'moodle/course:enrolreview';
		$check = checkquery($name->id,$capcheck);
		if($check == null)
		{
		   insertquery($name->id,$capcheck);
		}
		$par='moodle/course:viewparticipants';
		insertquery($name->id,$par);  
	}
	if($_POST[$name->shortname."UsrCreate"]== "on" && $c[$name->shortname."3"] == '0')
	{
		$capcheck = 'moodle/site:configview';
		$check = checkquery($name->id,$capcheck);
		if($check == null)
		{
		  insertquery($name->id,$capcheck);
		}
		$par='moodle/user:create';
		insertquery($name->id,$par);
	}

	if($_POST[$name->shortname."UsrEdit"]== "on" && $c[$name->shortname."4"] == '0')
	{
		$capcheck = 'moodle/course:enrolreview';
		$check = checkquery($name->id,$capcheck);
		if($check == null)
		{
		  insertquery($name->id,$capcheck);
		}
		$capcheck = 'moodle/user:viewdetails';
		$check = checkquery($name->id,$capcheck);
		if($check == null)
		{
		   insertquery($name->id,$capcheck);
		}
		$capcheck = 'moodle/course:viewparticipants';
		$check = checkquery($name->id,$capcheck);
		if($check == null)
		{
		   insertquery($name->id,$capcheck);
		}
		$par='enrol/manual:manage';
	    insertquery($name->id,$par);
	}

	if($_POST[$name->shortname."UsrDelete"]== "on" && $c[$name->shortname."5"] == '0')
	{
		$capcheck = 'moodle/course:enrolreview';
		$check = checkquery($name->id,$capcheck);
		if($check == null)
		{
		  insertquery($name->id,$capcheck);
		}
		$capcheck = 'moodle/user:viewdetails';
		$check = checkquery($name->id,$capcheck);
		if($check == null)
		{
		   insertquery($name->id,$capcheck);
		}
		$capcheck = 'moodle/course:viewparticipants';
		$check = checkquery($name->id,$capcheck);
		if($check == null)
		{
		   insertquery($name->id,$capcheck);
		}
		$par='enrol/manual:unenrol';
	    insertquery($name->id,$par);
	}

	if($_POST[$name->shortname."CorsView"]== "on" && $c[$name->shortname."7"] == '0')
	{
		$par='moodle/course:view';
		insertquery($name->id,$par); 
	}

	if($_POST[$name->shortname."CorsCreate"]== "on" && $c[$name->shortname."0"] == '0')
	{
		$capcheck = 'moodle/site:configview';
		$check = checkquery($name->id,$capcheck);
		if($check == null)
		{
		  insertquery($name->id,$capcheck);
		}
		$par='moodle/course:create';
	    insertquery($name->id,$par);
	}

	if($_POST[$name->shortname."CorsEdit"]== "on" && $c[$name->shortname."1"] == '0')
	{	
		$par='moodle/course:update';
	    insertquery($name->id,$par); 
	}

	if($_POST[$name->shortname."CorsDelete"]== "on" && $c[$name->shortname."2"] == '0')
	{
		$capcheck = 'moodle/site:configview';
		$check = checkquery($name->id,$capcheck);
		if($check == null)
		{
			  insertquery($name->id,$capcheck);
		}
		$capcheck = 'moodle/course:create';
		$check = checkquery($name->id,$capcheck);
		if($check == null)
		{
			insertquery($name->id,$capcheck);
		}
		$par='moodle/course:delete';
	    insertquery($name->id,$par);
	}

	//delete Capability
	
	if($_POST[$name->shortname."UsrView"]== null && $c[$name->shortname."6"] == '1')
	{
		$par='moodle/course:viewparticipants';
		deletequery($name->id,$par);
		$par='moodle/user:viewdetails';
		deletequery($name->id,$par);
		$par='moodle/course:enrolreview';
		deletequery($name->id,$par);
		$par='enrol/manual:unenrol';
	    deletequery($name->id,$par);
		$par='enrol/manual:manage';
	    deletequery($name->id,$par);
	}
	
	if($_POST[$name->shortname."UsrCreate"]== null && $c[$name->shortname."3"] == '1')
	{
		$capcheck = 'moodle/course:create';
		$check = checkquery($name->id,$capcheck);
		if($check == null)
		{
		  	$par='moodle/user:create';
			deletequery($name->id,$par);
			$par='moodle/site:configview';
			deletequery($name->id,$par);
		}
		else
		{
			$par='moodle/user:create';
			deletequery($name->id,$par);
		}
	}

	if($_POST[$name->shortname."UsrEdit"]== null && $c[$name->shortname."4"] == '1')
	{
		$par='enrol/manual:manage';
	   deletequery($name->id,$par);
	}

	if($_POST[$name->shortname."UsrDelete"]== null && $c[$name->shortname."5"] == '1')
	{
		$par='enrol/manual:unenrol';
	    deletequery($name->id,$par);
	}

	if($_POST[$name->shortname."CorsView"]== null && $c[$name->shortname."7"] == '1')
	{
		$par='moodle/course:view';
		deletequery($name->id,$par);
	}


	if($_POST[$name->shortname."CorsCreate"]== null && $c[$name->shortname."0"] == '1')
	{
		$capcheck = 'moodle/user:create';
		$check = checkquery($name->id,$capcheck);
		if($check == null)
		{
		  	$par='moodle/course:create';
			deletequery($name->id,$par);
			$par='moodle/site:configview';
			deletequery($name->id,$par);
			$par='moodle/course:delete';
	   	    deletequery($name->id,$par);
		}
		else
		{
			$par='moodle/course:create';
	    	deletequery($name->id,$par);
			$par='moodle/course:delete';
	   	    deletequery($name->id,$par);
			
		}
		 
	}

	if($_POST[$name->shortname."CorsEdit"]== null && $c[$name->shortname."1"] == '1')
	{
		$par='moodle/course:update';
	    deletequery($name->id,$par);
	}

	if($_POST[$name->shortname."CorsDelete"]== null && $c[$name->shortname."2"] == '1')
	{
		$par='moodle/course:delete';
	    deletequery($name->id,$par);

	}

}

header('Location: role.php');

?>