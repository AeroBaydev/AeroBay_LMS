<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'local/pocschool:view' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
        'manager' => CAP_ALLOW,
        ),
    ),
    'local/pocschool:trainerrm' => array(
        'riskbitmask' => RISK_XSS | RISK_DATALOSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,

    ),

    'local/pocschool:studentrm' => array(
        'riskbitmask' => RISK_XSS | RISK_DATALOSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,

    ),
    
);
?>