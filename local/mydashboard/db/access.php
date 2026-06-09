<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/mydashboard:submitdoubt' => [
        'riskbitmask' => RISK_SPAM | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'user' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];
