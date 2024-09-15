<?php
namespace local_hierarchy;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__DIR__).'/lib.php');

class observers
{
	

	public static function user_created(\core\event\user_created $event) {
			
		global $DB,$CFG,$USER;
		
		$userid = $event->objectid;
		$user = $DB->get_record('user', array('id'=>$userid));
		$deptid = $user->dept;
		
		if($deptid > 0) { //if dept has been set (default value of dept is 0) 
			
			add_user_to_dept($userid, $deptid); //add the user in that dept 
			//though this function updates the 'user' table again, it isn't a problem
		}
		
	}
	

	public static function user_updated(\core\event\user_updated $event) {
			
		global $DB,$CFG,$USER;
		
		$userid = $event->objectid;
		$newuser = $DB->get_record('user', array('id'=>$userid));
		$newdeptid = $newuser->dept;
		
		$olduser = $DB->get_record('user_dept_enrolments', array('userid'=>$userid), '*', IGNORE_MISSING); //returns false if no record found 
		
		if($olduser == false) { //if user was not assigned a dept before 
		
			if($newdeptid > 0) { //if user is assigned a dept this time 
				add_user_to_dept($userid, $newdeptid);
			}
			
		} else { //if user did have a dept before 
			
			$olddeptid = $olduser->deptid;
			
			if($newdeptid != $olddeptid) { //if dept has been changed 
				
				remove_user_from_dept($userid, $olddeptid);
				
				if($newdeptid > 0) { //if user is assigned a new dept 
					add_user_to_dept($userid, $newdeptid);
				}
				
			}
			
		}
		//though these functions update the 'user' table again, it isn't a problem

	}

	
	public static function user_deleted(\core\event\user_deleted $event) {
			
		global $DB,$CFG,$USER;
		
		$userid = $event->objectid;
		
		$DB->delete_records('user_dept_enrolments', array('userid'=>$userid)); //delete entry of user (if any) from table 
		
		//user is automatically unenrolled from all courses by moodle 

	}
	

	public static function enrol_instance_deleted(\core\event\enrol_instance_deleted $event) {
			
		global $DB,$CFG;
		
		$enrolid = $event->objectid;
		
		$DB->delete_records('dept_enrolments', array('enrolid'=>$enrolid));
		//When an enrolment method is deleted, delete its entries (if any) from 'dept_enrolments' table. The users enrolled through that method are automatically unenrolled by moodle. 
		
		//This also covers the case of course being deleted. We record enrolments in table 'dept_enrolments' through enrolment method of the course which is deleted when the course is deleted. 
		
	}
	

	public static function role_deleted(\core\event\role_deleted $event) {
			
		global $DB,$CFG;
		
		$roleid = $event->objectid;
		
		$sql = "UPDATE {dept_enrolments} SET roleid=0 WHERE roleid=$roleid";
		$sql = "UPDATE {dept_category_enrolments} SET roleid=0 WHERE roleid=$roleid";
		//when a role is deleted, moodle sets roleid for all users having that role in a course as 0 
		
		$DB->execute($sql);
		
	}
	

	public static function dept_deleted(event\dept_deleted $event) { //custom event
		
		global $DB,$CFG;
		
		$deptid = $event->objectid;
		
		//this event should be used by future users of the plugin. The plugin developers themselves should never use events.

	}


	public static function category_deleted(\core\event\course_category_deleted $event) {
		
		global $DB,$CFG;
		
		$categoryid = $event->objectid;
		
		$DB->delete_records('dept_category_enrolments', array('categoryid'=>$categoryid));
		//when a category is deleted, delete its entries (if any) from 'dept_category_enrolments' table

	}


	public static function enrol_instance_created(\core\event\enrol_instance_created $event) {
		
		//course_created event is called before the manual enrolment is created for the course. We need manual enrolment to enrol the dept in the course.

		//we are not unenrolling users when a course is removed from a category, because the dept may have been explicitly enrolled in that particular course through course enrolment, irrespective of the category

		global $DB,$CFG;
		
		$enrolid = $event->objectid;

		$check1 = $DB->record_exists('enrol', array('id'=>$enrolid, 'enrol'=>'manual')); //check if the enrol instance is manual

		if($check1 == true) {

			$enrol = $DB->get_record('enrol', array('id'=>$enrolid)); //get enrol record

			$course = $DB->get_record('course', array('id'=>$enrol->courseid)); //get course record

			$category = $DB->get_record('course_categories', array('id'=>$course->category)); //get record of course's category

			$result = $DB->get_records('dept_category_enrolments', array('categoryid'=>$category->id)); //get all depts enrolled in that category

			foreach($result as $record) { //for each dept enrolled in that category

				//no need to check if dept is already enrolled in that course. If manual enrolment is deleted and added again for a course, the records would have been already deleted in 'dept_enrolments' table due to our 'enrol_instance_deleted' event

				$result2 = $DB->get_records('user_dept_enrolments', array('deptid'=>$record->deptid)); //get all users in that dept

				foreach($result2 as $record2) { //for each user

					enrol($course->id, $record2->userid, $record->roleid, $record->timestart, $record->timeend);

				}

				//after enrolling all users make entry of dept in 'dept_enrolments' table
				$dept_enrolment_record = new \stdclass();

				$dept_enrolment_record->enrolid = $enrol->id;
				$dept_enrolment_record->deptid = $record->deptid;
				$dept_enrolment_record->roleid = $record->roleid;
				$dept_enrolment_record->timestart = $record->timestart;
				$dept_enrolment_record->timeend = $record->timeend;
				$dept_enrolment_record->modifierid = $USER->id;
				$dept_enrolment_record->timecreated = time();
				$dept_enrolment_record->timemodified = time();

				$DB->insert_record('dept_enrolments', $dept_enrolment_record, $returnid=false, $bulk=false);

			} //depts loop

		} //check1

	}


	public static function course_updated(\core\event\course_updated $event) {
		
		global $DB,$CFG;
		
		$courseid = $event->courseid;

		$course = $DB->get_record('course', array('id'=>$courseid)); //get course

		$enrol = $DB->get_record('enrol', array('courseid'=>$course->id, 'enrol'=>'manual'));
		$enrolid = $enrol->id; //get enrol id for manual enrolment of course

		$category = $DB->get_record('course_categories', array('id'=>$course->category)); //get category of course

		$result = $DB->get_records('dept_category_enrolments', array('categoryid'=>$category->id)); //get all depts enrolled in that category

		$check1 = $DB->record_exists('enrol', array('courseid'=>$course->id, 'enrol'=>'manual')); //check if manual enrolment is added for that course 

		if($check1 == true) {

			foreach($result as $record) { //for each dept enrolled in that category

				$check2 = $DB->record_exists('dept_enrolments', array('deptid'=>$record->deptid, 'enrolid'=>$enrolid)); //check if dept is already enrolled in that course

				if($check2 == false) { //if dept is not already enrolled in that course

					$result2 = $DB->get_records('user_dept_enrolments', array('deptid'=>$record->deptid)); //get all users in that dept

					foreach($result2 as $record2) { //for each user 

						enrol($course->id, $record2->userid, $record->roleid, $record->timestart, $record->timeend);

					}

					//after enrolling all users make entry of dept in 'dept_enrolments' table
					$dept_enrolment_record = new \stdclass();

					$dept_enrolment_record->enrolid = $enrolid;
					$dept_enrolment_record->deptid = $record->deptid;
					$dept_enrolment_record->roleid = $record->roleid;
					$dept_enrolment_record->timestart = $record->timestart;
					$dept_enrolment_record->timeend = $record->timeend;
					$dept_enrolment_record->modifierid = $USER->id;
					$dept_enrolment_record->timecreated = time();
					$dept_enrolment_record->timemodified = time();

					$DB->insert_record('dept_enrolments', $dept_enrolment_record, $returnid=false, $bulk=false);

				} //dept check

			} //depts loop

		} //manual enrolment check

	}


//purge cache for changes to take effect 
}

?>