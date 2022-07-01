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

    // Roles are written in the German name. Capabilities in english.
    // The capabilities are created and assigned upon installing the block
    'block/exaquest:fragenersteller' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:createquestion' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:modulverantwortlicher' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:admintechnpruefungsdurchf' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:pruefungskoordination' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:pruefungsstudmis' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:fachlfragenreviewer' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:beurteilungsmitwirkende' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:fachlicherpruefer' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:pruefungsmitwirkende' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:fachlicherzweitpruefer' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
);