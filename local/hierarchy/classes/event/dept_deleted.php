<?php

namespace local_hierarchy\event;

defined('MOODLE_INTERNAL') || die();

class dept_deleted extends \core\event\base { 

	protected function init() { //required function 

		$this->data['objecttable'] = 'loc'; 
		$this->data['crud'] = 'd';
		$this->data['edulevel'] = self::LEVEL_PARTICIPATING;
		//these 3 properties have to be specified, rest are optional 
		
	}

	
	public static function get_name() { //optional 
		return 'dept_deleted';
	}


	public function get_description() { //optional 
		return "The dept has been deleted";
	}

}

?>