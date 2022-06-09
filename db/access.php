<?php
$capabilities = array(

    'block/exaquest:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),

    'block/exaquest:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

    'block/exaquest:fragenersteller' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'fragenersteller' => CAP_ALLOW,
        ),
        //this has to be manually applied to the role "fragenersteller"... is there a way to do it automatically?
    ),
    'block/exaquest:createquestion' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'fragenersteller' => CAP_ALLOW,
        ),
        //this has to be manually applied to the role "fragenersteller"... is there a way to do it automatically?
    ),
    'block/exaquest:modulverantwortlicher' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'modulverantwortlicher' => CAP_ALLOW,
        ),
        //this has to be manually applied to the role "modulverantwortlicher"
    ),

);