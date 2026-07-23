<?php
/**
 * Plugin Name: AS Transcript Themes
 * Plugin URI: https://github.com/cchatterton/as-transcript-themes/releases/latest
 * Description: Uses the WordPress AI Client to identify topics, themes, and commitments from transcripts and email threads.
 * Version: 0.2.0
 * Requires at least: 7.0
 * Requires PHP: 8.1
 * Update URI: https://github.com/cchatterton/as-transcript-themes
 * Author: AlphaSys
 * Author URI: https://alphasys.com.au
 * Text Domain: as-transcript-themes
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ASTT_VERSION', '0.2.0');
define('ASTT_TRANSCRIPT_POST_TYPE', 'astt_transcript');
define('ASTT_EMAIL_POST_TYPE', 'astt_email');
define('ASTT_THEME_POST_TYPE', 'astt_theme');
define('ASTT_BIG_THEME_POST_TYPE', 'astt_big_theme');
define('ASTT_COMMITMENT_POST_TYPE', 'astt_commitment');
define('ASTT_CONTACT_POST_TYPE', 'astt_contact');
define('ASTT_ORG_POST_TYPE', 'astt_organisation');
define('ASTT_OPTION_NAME', 'astt_options');
define('ASTT_PLUGIN_FILE', __FILE__);
define('ASTT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASTT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ASTT_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once ASTT_PLUGIN_DIR . 'functions/helpers.php';
require_once ASTT_PLUGIN_DIR . 'functions/setup.php';
require_once ASTT_PLUGIN_DIR . 'functions/assets.php';
require_once ASTT_PLUGIN_DIR . 'functions/admin.php';
require_once ASTT_PLUGIN_DIR . 'functions/rest.php';
require_once ASTT_PLUGIN_DIR . 'functions/updater.php';

register_activation_hook(ASTT_PLUGIN_FILE, 'astt_activate');
register_deactivation_hook(ASTT_PLUGIN_FILE, 'astt_deactivate');

add_action('plugins_loaded', 'astt_boot');
