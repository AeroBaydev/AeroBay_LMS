<?php

defined('MOODLE_INTERNAL') || die;

$capabilities = array(

    'local/hierarchy:manage' => array(

        'riskbitmask' => RISK_XSS | RISK_DATALOSS,
		
        'captype' => 'write',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
		
        )
    ),
	
	
	'local/hierarchy:roles_manage' => array(

        'captype' => 'write',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
		
        )
    ),
	
	
	'local/hierarchy:course_report_access' => array(

        'captype' => 'read',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(

        	'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW
		
        )
    ),
	
	
	'local/hierarchy:course_report_filter' => array(

        'captype' => 'read',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
			'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW
        )
    ),
	
	
	'local/hierarchy:course_report_download' => array(

        'captype' => 'read',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
        	'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW
        )
    ),
	
	
	'local/hierarchy:user_report_access' => array(

        'captype' => 'read',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
        	'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW
        )
    ),
	
	
	'local/hierarchy:user_report_filter' => array(

        'captype' => 'read',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
		
        )
    ),
	
	
	'local/hierarchy:user_report_download' => array(

        'captype' => 'read',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
		
        )
    ),
	
	
	'local/hierarchy:branch_view' => array(

        'captype' => 'read',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
		
        )
    ),
	
	
	'local/hierarchy:branch_add' => array(

        'captype' => 'write',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
		
        )
    ),
	
	
	'local/hierarchy:branch_delete' => array(

        'captype' => 'write',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
		
        )
    ),
	
	
	'local/hierarchy:branch_edit' => array(

        'captype' => 'write',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
		
        )
    ),
		
	
	'local/hierarchy:dept_view' => array(

        'captype' => 'read',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
		
        )
    ),
	
	'local/hierarchy:dept_add' => array(

        'captype' => 'write',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
		
        )
    ),
	
	'local/hierarchy:dept_delete' => array(

        'captype' => 'write',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
		
        )
    ),
	
	
	'local/hierarchy:dept_edit' => array(

        'captype' => 'write',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
		
        )
    ),
	
	
	'local/hierarchy:dept_assign' => array(

        'captype' => 'write',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
		
        )
    ),
	
	
	'local/hierarchy:dept_user_manage' => array(

        'captype' => 'write',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
		
        )
    ),
	
	
	'local/hierarchy:dept_user_view' => array(

        'captype' => 'read',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
		
        )
    ),
	
	'local/hierarchy:dept_user_individual_report_download' => array(

        'captype' => 'read',
		
        'contextlevel' => CONTEXT_SYSTEM,
		
        'archetypes' => array(
		
        )
    )
		
);
