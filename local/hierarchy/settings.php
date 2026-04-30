<?php

defined('MOODLE_INTERNAL') || die();



//capabilities 'moodle/site:config' and 'moodle/site:configview' are required to view the 'Site administration' tree 

	if(has_capability('local/hierarchy:manage', context_system::instance())) {
	//check if user has our 'manage' capability 
	
		$ADMIN->add('root', new admin_category('hierarchy', get_string('hierarchies','local_hierarchy')));

		$prefix_array=array('locate');
		//prefixes added here
			
		foreach($prefix_array as $prefix) {
				
			$url = new moodle_url($CFG->wwwroot.'/local/hierarchy/nodes/node_framework.php?prefix=' . $prefix);
				
			$ADMIN->add('hierarchy', new admin_externalpage($prefix, get_string($prefix . '_menu_name', 'local_hierarchy'), $url, 'moodle/site:configview', false));
				
		}
		
	}
	
	$ADMIN->add('root', new admin_externalpage('permission_roles', 'Roles and permissions', new moodle_url($CFG->wwwroot.'/local/hierarchy/role.php')));

	
	//$ADMIN->add('root', new admin_category('tenant_management', 'Tenant Management'));
	
	
	if(($CFG->prefix=='mdl_')&&(is_siteadmin())){
		
		$ADMIN->add('tenant_management', new admin_externalpage('manage_tenants', 'Manage Tenants', new moodle_url($CFG->wwwroot . '/centralSystem/tenant/index.php')));
		/*
		$ADMIN->add('tenant_management', new admin_externalpage('push_courses', 'Push Courses', new moodle_url($CFG->wwwroot . '/centralSystem/courses/tenant_courses.php')));
		*/
		
	}
        
    if ( user_has_role_assignment($USER->id,9)) {
		/*
		$ADMIN->add('tenant_management', new admin_externalpage('centralized_courses', 'Centralized courses', new moodle_url($CFG->wwwroot . '/centralSystem/courses/index.php')));
		*/
	
	}
        
    if(($CFG->prefix!='mdl_')&&(is_siteadmin())){
		/*
		$ADMIN->add('tenant_management', new admin_externalpage('centralized_courses', 'Centralized courses', new moodle_url($CFG->wwwroot . '/centralSystem/courses/index.php')));
		*/
		//$ADMIN->add('tenant_management', new admin_externalpage('main_site', 'Main Site', new moodle_url($CFG->mainurl)));
		
	}

?>