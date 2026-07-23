<?php

if (!defined('ABSPATH')) {
    exit;
}

function astt_boot(): void
{
    add_action('init', 'astt_register_post_types');
    add_action('admin_menu', 'astt_register_admin_menu');
    add_action('admin_init', 'astt_handle_theme_ranking_action');
    add_action('wp_dashboard_setup', 'astt_register_dashboard_widgets');
    add_action('add_meta_boxes', 'astt_register_meta_boxes');
    add_action('save_post_' . ASTT_TRANSCRIPT_POST_TYPE, 'astt_save_transcript', 10, 2);
    add_action('save_post_' . ASTT_EMAIL_POST_TYPE, 'astt_save_email', 10, 2);
    add_action('admin_enqueue_scripts', 'astt_enqueue_admin_assets');
    add_action('admin_notices', 'astt_admin_notices');
    add_action('pre_get_posts', 'astt_sort_workspace_admin_list');
    add_filter('use_block_editor_for_post_type', 'astt_use_classic_editor_for_sources', 10, 2);
    add_filter('manage_' . ASTT_THEME_POST_TYPE . '_posts_columns', 'astt_theme_columns');
    add_action('manage_' . ASTT_THEME_POST_TYPE . '_posts_custom_column', 'astt_theme_column_content', 10, 2);
    add_filter('manage_' . ASTT_BIG_THEME_POST_TYPE . '_posts_columns', 'astt_theme_columns');
    add_action('manage_' . ASTT_BIG_THEME_POST_TYPE . '_posts_custom_column', 'astt_theme_column_content', 10, 2);
    add_filter('manage_' . ASTT_COMMITMENT_POST_TYPE . '_posts_columns', 'astt_commitment_columns');
    add_action('manage_' . ASTT_COMMITMENT_POST_TYPE . '_posts_custom_column', 'astt_commitment_column_content', 10, 2);
    add_filter('manage_' . ASTT_CONTACT_POST_TYPE . '_posts_columns', 'astt_contact_columns');
    add_action('manage_' . ASTT_CONTACT_POST_TYPE . '_posts_custom_column', 'astt_contact_column_content', 10, 2);
    add_filter('manage_' . ASTT_ORG_POST_TYPE . '_posts_columns', 'astt_org_columns');
    add_action('manage_' . ASTT_ORG_POST_TYPE . '_posts_custom_column', 'astt_org_column_content', 10, 2);
    astt_register_github_updater();
}

function astt_activate(): void
{
    astt_register_post_types();
    flush_rewrite_rules();
}

function astt_deactivate(): void
{
    flush_rewrite_rules();
}

function astt_register_post_types(): void
{
    register_post_type(ASTT_TRANSCRIPT_POST_TYPE, array(
        'labels' => array(
            'name' => __('Transcripts', 'as-transcript-themes'),
            'singular_name' => __('Transcript', 'as-transcript-themes'),
            'add_new_item' => __('Add Transcript', 'as-transcript-themes'),
            'edit_item' => __('Edit Transcript', 'as-transcript-themes'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => false,
        'menu_icon' => 'dashicons-media-text',
        'supports' => array('title', 'editor'),
        'capability_type' => 'post',
    ));

    register_post_type(ASTT_EMAIL_POST_TYPE, array(
        'labels' => array(
            'name' => __('Email Threads', 'as-transcript-themes'),
            'singular_name' => __('Email Thread', 'as-transcript-themes'),
            'add_new_item' => __('Add Email Thread', 'as-transcript-themes'),
            'edit_item' => __('Edit Email Thread', 'as-transcript-themes'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => false,
        'menu_icon' => 'dashicons-email-alt2',
        'supports' => array('title', 'editor'),
        'capability_type' => 'post',
    ));

    register_post_type(ASTT_THEME_POST_TYPE, array(
        'labels' => array(
            'name' => __('Topics', 'as-transcript-themes'),
            'singular_name' => __('Topic', 'as-transcript-themes'),
            'add_new_item' => __('Add Topic', 'as-transcript-themes'),
            'edit_item' => __('Edit Topic', 'as-transcript-themes'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-lightbulb',
        'supports' => array('title', 'editor', 'excerpt', 'page-attributes'),
        'capability_type' => 'post',
    ));

    register_post_type(ASTT_BIG_THEME_POST_TYPE, array(
        'labels' => array(
            'name' => __('Themes', 'as-transcript-themes'),
            'singular_name' => __('Theme', 'as-transcript-themes'),
            'add_new_item' => __('Add Theme', 'as-transcript-themes'),
            'edit_item' => __('Edit Theme', 'as-transcript-themes'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-networking',
        'supports' => array('title', 'editor', 'excerpt', 'page-attributes'),
        'capability_type' => 'post',
    ));

    register_post_type(ASTT_COMMITMENT_POST_TYPE, array(
        'labels' => array(
            'name' => __('Commitments', 'as-transcript-themes'),
            'singular_name' => __('Commitment', 'as-transcript-themes'),
            'add_new_item' => __('Add Commitment', 'as-transcript-themes'),
            'edit_item' => __('Edit Commitment', 'as-transcript-themes'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-yes-alt',
        'supports' => array('title', 'editor', 'page-attributes'),
        'capability_type' => 'post',
    ));

    register_post_type(ASTT_CONTACT_POST_TYPE, array(
        'labels' => array(
            'name' => __('Contacts', 'as-transcript-themes'),
            'singular_name' => __('Contact', 'as-transcript-themes'),
            'add_new_item' => __('Add Contact', 'as-transcript-themes'),
            'edit_item' => __('Edit Contact', 'as-transcript-themes'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-id',
        'supports' => array('title', 'editor'),
        'capability_type' => 'post',
    ));

    register_post_type(ASTT_ORG_POST_TYPE, array(
        'labels' => array(
            'name' => __('Organisations', 'as-transcript-themes'),
            'singular_name' => __('Organisation', 'as-transcript-themes'),
            'add_new_item' => __('Add Organisation', 'as-transcript-themes'),
            'edit_item' => __('Edit Organisation', 'as-transcript-themes'),
        ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-building',
        'supports' => array('title', 'editor'),
        'capability_type' => 'post',
    ));
}

function astt_use_classic_editor_for_sources(bool $use_block_editor, string $post_type): bool
{
    if (in_array($post_type, array(ASTT_TRANSCRIPT_POST_TYPE, ASTT_EMAIL_POST_TYPE), true)) {
        return false;
    }

    return $use_block_editor;
}
