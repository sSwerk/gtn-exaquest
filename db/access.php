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

    // capabilities defined by ZML:
    'block/exaquest:readallquestions' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:readquestionstatistics' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:changestatusofreleasedquestions' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:createquestion' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:setstatustoreview' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:reviseownquestion' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:setstatustoreleased' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:showownrevisedquestions' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:showquestionstoreview' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:editquestiontoreview' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:showreleasedquestions' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:showquestionstorevise' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:releasequestion' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:editallquestions' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:addquestiontoexam' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:releaseexam' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:technicalreview' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:executeexam' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:assignsecondexaminator' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:definequestionblockingtime' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:showexamresults' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:gradeexam' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:createexamstatistics' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:showexamstatistics' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:correctexam' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:acknowledgeexamcorrection' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:releaseexamgrade' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:releasecommissionalexamgrade' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:exportgradestokusss' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:executeexamreview' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:addparticipanttomodule' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:assignroles' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:changerolecapabilities' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
    'block/exaquest:createroles' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
    ),
);