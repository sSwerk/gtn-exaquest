<?php

$settings->add(new admin_setting_heading('exaquest/heading_main',
    'Haupteinstellungen',
    ''));

// company name
$settings->add(new admin_setting_configtext('exaquest/company_name',
    'Firmenname',
    'Bitte einen Firmennamen eingeben',
    "", PARAM_TEXT));

// Moodle ID
$settings->add(new admin_setting_configtext('exaquest/moodle_id', 'Moodle ID',
    'Bitte eine MoodleID wählen', 0, PARAM_INT));

// apifonica SMS configuration
$settings->add(new admin_setting_heading('exaquest/heading_apifonica',
    'SMS-Verteilung über Apifonica',
    ''));
$settings->add(new admin_setting_configtext('exaquest/apifonica_sms_absender_name', 'absender Name',
    'Name oder Telefon', 'skillswork', PARAM_TEXT));
$settings->add(new admin_setting_configtext('exaquest/apifonica_sms_account_sid', 'Account SID',
    'Unique account identifier (also the username)', '', PARAM_TEXT));
$settings->add(new admin_setting_configtext('exaquest/apifonica_sms_auth_token', 'Account token',
    'Password for authentification', '', PARAM_TEXT));
$settings->add(new admin_setting_configtext('exaquest/apifonica_sms_app_sid', 'Application SID',
    'Unique application identifier', '', PARAM_TEXT));