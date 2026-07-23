<?php

if (!defined('ABSPATH')) {
    exit;
}

function astt_enqueue_admin_assets(string $hook): void
{
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, astt_supported_post_types(), true)) {
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
