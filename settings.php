<?php
/**
 * Defines the settings for the exaquest block plugin, primarily for the similarity comparison plugin.
 *
 * @package    block_exaquest
 * @copyright  2022 Stefan Swerk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // similarity comparison algorithm to use
    $algorithmoptions = array(
            GTN\strategy\editbased\JaroWinklerStrategy::class => get_string('exaquest:similarity_settings_algorithm_jarowinkler', 'block_exaquest'),
            GTN\strategy\editbased\SmithWatermanGotohStrategy::class => get_string('exaquest:similarity_settings_algorithm_smithwaterman', 'block_exaquest')
    );
    $setting = new admin_setting_configselect('block_exaquest/config_similarity_algorithm',
            new lang_string('exaquest:similarity_settings_algorithm', 'block_exaquest'),
            new lang_string('exaquest:similarity_settings_algorithm_desc', 'block_exaquest'), JaroWinklerStrategy::class, $algorithmoptions);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    // similarity comparison threshold to use
    $setting = new admin_setting_configtext('block_exaquest/config_similarity_threshold',
            new lang_string('exaquest:similarity_settings_threshold', 'block_exaquest'),
            new lang_string('exaquest:similarity_settings_threshold_desc', 'block_exaquest'),
            0.8, PARAM_FLOAT);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    // similarity comparison JaroWinkler minimum prefix length to use
    $setting = new admin_setting_configtext('block_exaquest/config_similarity_jwminprefixlength',
            new lang_string('exaquest:similarity_settings_jwminprefixlength', 'block_exaquest'),
            new lang_string('exaquest:similarity_settings_jwminprefixlength_desc', 'block_exaquest'),
            4, PARAM_INT);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    // similarity comparison JaroWinkler Prefix Scale to use
    $setting = new admin_setting_configtext('block_exaquest/config_similarity_jwprefixscale',
            new lang_string('exaquest:similarity_settings_jwprefixscale', 'block_exaquest'),
            new lang_string('exaquest:similarity_settings_jwprefixscale_desc', 'block_exaquest'),
            0.1, PARAM_FLOAT);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    // similarity comparison SmithWatermanGotoh Match Value to use
    $setting = new admin_setting_configtext('block_exaquest/config_similarity_swgmatchmalue',
            new lang_string('exaquest:similarity_settings_swgmatchmalue', 'block_exaquest'),
            new lang_string('exaquest:similarity_settings_swgmatchmalue_desc', 'block_exaquest'),
            1.0, PARAM_FLOAT);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    // similarity comparison SmithWatermanGotoh Mismatch Value to use
    $setting = new admin_setting_configtext('block_exaquest/config_similarity_swgmismatchvalue',
            new lang_string('exaquest:similarity_settings_swgmismatchvalue', 'block_exaquest'),
            new lang_string('exaquest:similarity_settings_swgmismatchvalue_desc', 'block_exaquest'),
            -2.0, PARAM_FLOAT);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    // similarity comparison SmithWatermanGotoh Gap Value to use
    $setting = new admin_setting_configtext('block_exaquest/config_similarity_swggapvalue',
            new lang_string('exaquest:similarity_settings_swggapvalue', 'block_exaquest'),
            new lang_string('exaquest:similarity_settings_swggapvalue_desc', 'block_exaquest'),
            -0.5, PARAM_FLOAT);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    // number of threads to use for computing the similarity distance
    $setting = new admin_setting_configtext('block_exaquest/config_similarity_nrofthreads',
            new lang_string('exaquest:similarity_settings_nrofthreads', 'block_exaquest'),
            new lang_string('exaquest:similarity_settings_nrofthreads_desc', 'block_exaquest'),
            1, PARAM_INT);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);
}

/*
TODO by stefan: I commented out this code, since it is not effective/not displaying anything in my setup, include it again if required
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
*/