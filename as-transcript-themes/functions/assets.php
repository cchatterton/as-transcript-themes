<?php

if (!defined('ABSPATH')) {
    exit;
}

function astt_enqueue_admin_assets(string $hook): void
{
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, array(ASTT_TRANSCRIPT_POST_TYPE, ASTT_EMAIL_POST_TYPE, ASTT_THEME_POST_TYPE, ASTT_CONTACT_POST_TYPE, ASTT_ORG_POST_TYPE), true)) {
        return;
    }

    wp_enqueue_style(
        'astt-admin',
        ASTT_PLUGIN_URL . 'styles/as-transcript-themes.css',
        array(),
        ASTT_VERSION
    );

    wp_enqueue_script(
        'astt-admin',
        ASTT_PLUGIN_URL . 'scripts/as-transcript-themes.js',
        array(),
        ASTT_VERSION,
        true
    );
}
