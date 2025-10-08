<?php
namespace local_pocenrol;

defined('MOODLE_INTERNAL') || die();

class observer {
    /**
     * Listens for the poc_course_selected event and enrolls the user.
     * @param \local_pocenrol\event\poc_course_selected $event The event object.
     */
    public static function course_selected(\local_pocenrol\event\poc_course_selected $event) {
        global $DB;

        // Get the data from the event
        $courseid = $event->courseid;
        $userid = $event->relateduserid;
        $other_data = $event->other;

        try {
            // STEP 1: Find the 'manual' enrolment plugin instance for the course.
        $enrol = enrol_get_plugin('manual');
        if (!$enrol) {
            return;
        }

        $instances = $DB->get_records('enrol', ['enrol' => 'manual', 'courseid' => $courseid, 'status' => 0]);
        if (empty($instances)) {
            return;
        }

        $instance = reset($instances);

            // STEP 2: Get the 'pocschool' role ID
        $pocschoolrole = $DB->get_record('role', ['shortname' => 'pocschool']);
        if (!$pocschoolrole) {
            return;
        }

            // STEP 3: Enroll the user with the custom pocschool role.
        $enrol->enrol_user($instance, $userid, $pocschoolrole->id);

            // STEP 4: ONLY AFTER SUCCESSFUL ENROLLMENT - Insert into poc_copy_course table
            $context = \context_course::instance($courseid);
            if (is_enrolled($context, $userid)) {
                // Enrollment successful, now create table entry
                $record = new \stdClass();
                $record->schoolid   = $other_data['schoolid'];
                $record->gradeid    = $other_data['gradeid'];
                $record->courseid   = $courseid;
                $record->sessionid  = $other_data['sessionid'];
                $record->pocid      = $other_data['pocid'];
                $record->status     = 1;
                $record->timecreated = time();
                
                // Check for duplicates before inserting
                $conditions = [
                    'pocid'     => $other_data['pocid'],
                    'status'    => 1,
                    'gradeid'   => $other_data['gradeid'],
                    'courseid'  => $courseid,
                    'sessionid' => $other_data['sessionid']
                ];
                
                if (!$DB->record_exists('poc_copy_course', $conditions)) {
                    $insertedid = $DB->insert_record('poc_copy_course', $record);
                }

                // STEP 5: Add user to group based on poc_id from mdl_poc table
                mtrace("... Starting group assignment process for User ID: {$userid}");
                try {
                    $poc_record = $DB->get_record('poc', ['userid' => $userid]);
                    if ($poc_record && !empty($poc_record->poc_id)) {
                        mtrace("... Found POC record - POC ID: {$poc_record->poc_id}");
                        
                        // Create group name based on poc_id
                        $group_name = "POC_" . $poc_record->userid;
                        mtrace("... Group name will be: {$group_name}");
                        
                        // Check if group exists in this course
                        $group = $DB->get_record('groups', ['courseid' => $courseid, 'name' => $group_name]);
                        
                        if (!$group) {
                            mtrace("... Group doesn't exist, creating new group");
                            // Group doesn't exist, create it manually using direct DB insert
                            $group_data = new \stdClass();
                            $group_data->courseid = $courseid;
                            $group_data->name = $group_name;
                            $group_data->description = "Auto-created group for POC ID: " . $poc_record->userid;
                            $group_data->timecreated = time();
                            $group_data->timemodified = time();
                            
                            // Insert group directly into database
                            $group_id = $DB->insert_record('groups', $group_data);
                            mtrace("... Group created with ID: {$group_id}");
                            
                            if ($group_id) {
                                // Group created, now add user to group
                                $member_data = new \stdClass();
                                $member_data->groupid = $group_id;
                                $member_data->userid = $userid;
                                $member_data->timeadded = time();
                                $member_id = $DB->insert_record('groups_members', $member_data);
                                mtrace("... User added to group - Member ID: {$member_id}");
                            } else {
                                mtrace("... ERROR: Failed to create group");
                            }
                        } else {
                            mtrace("... Group already exists with ID: {$group->id}");
                            // Group exists, check if user is already a member
                            $group_id = $group->id;
                            $existing_member = $DB->get_record('groups_members', [
                                'groupid' => $group_id, 
                                'userid' => $userid
                            ]);
                            
                            if (!$existing_member) {
                                mtrace("... User not a member, adding to group");
                                // User not a member, add them
                                $member_data = new \stdClass();
                                $member_data->groupid = $group_id;
                                $member_data->userid = $userid;
                                $member_data->timeadded = time();
                                $member_id = $DB->insert_record('groups_members', $member_data);
                                mtrace("... User added to existing group - Member ID: {$member_id}");
                            } else {
                                mtrace("... User already a member of this group");
                            }
                        }
                    } else {
                        if (!$poc_record) {
                            mtrace("... ERROR: No POC record found for User ID: {$userid}");
                        } else {
                            mtrace("... ERROR: POC record found but poc_id is empty");
                        }
                    }
                } catch (\Exception $group_error) {
                    mtrace("... CRITICAL ERROR in group creation: " . $group_error->getMessage());
                    error_log("Group creation failed for POC enrollment: " . $group_error->getMessage());
                }
            }

        } catch (\Exception $e) {
            // Silent error handling - don't break AJAX response
            error_log("POC Enrollment Observer Error: " . $e->getMessage());
        }
    }
}