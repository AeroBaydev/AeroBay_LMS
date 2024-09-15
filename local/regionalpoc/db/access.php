<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'local/regionalpoc:assign' => array(
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'coursecreator' => CAP_ALLOW,
        ),
    ),
);
