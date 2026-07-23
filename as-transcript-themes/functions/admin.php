<?php

if (!defined('ABSPATH')) {
    exit;
}

function astt_register_meta_boxes(): void
{
    add_meta_box(
        'astt_transcript_context',
        __('Transcript Context', 'as-transcript-themes'),
        'astt_render_transcript_context_meta_box',
        ASTT_TRANSCRIPT_POST_TYPE,
        'normal',
        'high'
    );

    add_meta_box(
        'astt_transcript_processing',
        __('Theme Processing', 'as-transcript-themes'),
        'astt_render_transcript_processing_meta_box',
        ASTT_TRANSCRIPT_POST_TYPE,
        'side',
        'high'
    );

    add_meta_box(
        'astt_email_context',
        __('Email Context', 'as-transcript-themes'),
        'astt_render_email_context_meta_box',
        ASTT_EMAIL_POST_TYPE,
        'normal',
        'high'
    );

    add_meta_box(
        'astt_email_processing',
        __('Theme Processing', 'as-transcript-themes'),
        'astt_render_email_processing_meta_box',
        ASTT_EMAIL_POST_TYPE,
        'side',
        'high'
    );

    add_meta_box(
        'astt_theme_details',
        __('Theme Details', 'as-transcript-themes'),
        'astt_render_theme_details_meta_box',
        ASTT_THEME_POST_TYPE,
        'normal',
        'high'
    );

    foreach (array(ASTT_TRANSCRIPT_POST_TYPE, ASTT_EMAIL_POST_TYPE, ASTT_THEME_POST_TYPE, ASTT_CONTACT_POST_TYPE, ASTT_ORG_POST_TYPE) as $post_type) {
        add_meta_box(
            'astt_relationships',
            __('Relationships', 'as-transcript-themes'),
            'astt_render_relationships_meta_box',
            $post_type,
            'side',
            'default'
        );
    }

    add_meta_box(
        'astt_contact_details',
        __('Contact Details', 'as-transcript-themes'),
        'astt_render_contact_details_meta_box',
        ASTT_CONTACT_POST_TYPE,
        'normal',
        'high'
    );

    add_meta_box(
        'astt_org_details',
        __('Organisation Details', 'as-transcript-themes'),
        'astt_render_org_details_meta_box',
        ASTT_ORG_POST_TYPE,
        'normal',
        'high'
    );
}

function astt_register_admin_menu(): void
{
    add_submenu_page(
        'edit.php?post_type=' . ASTT_THEME_POST_TYPE,
        __('Theme Ranking', 'as-transcript-themes'),
        __('Theme Ranking', 'as-transcript-themes'),
        'edit_posts',
        'astt-theme-ranking',
        'astt_render_theme_ranking_page'
    );
}

function astt_handle_theme_ranking_action(): void
{
    if (empty($_POST['astt_rescore_themes'])) {
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('You do not have permission to rescore themes.', 'as-transcript-themes'));
    }

    check_admin_referer('astt_rescore_themes');
    $count = astt_rank_themes();
    astt_queue_admin_notice(sprintf(__('%d themes rescored.', 'as-transcript-themes'), $count));

    wp_safe_redirect(admin_url('edit.php?post_type=' . ASTT_THEME_POST_TYPE . '&page=astt-theme-ranking'));
    exit;
}

function astt_render_theme_ranking_page(): void
{
    $themes = get_posts(array(
        'post_type' => ASTT_THEME_POST_TYPE,
        'post_status' => 'any',
        'posts_per_page' => -1,
        'orderby' => array(
            'menu_order' => 'DESC',
            'title' => 'ASC',
        ),
    ));
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Theme Ranking', 'as-transcript-themes'); ?></h1>
        <p><?php esc_html_e('Theme bigness is currently scored from source transcript count, repeated detail entries, and a small recency bonus.', 'as-transcript-themes'); ?></p>
        <form method="post">
            <?php wp_nonce_field('astt_rescore_themes'); ?>
            <p>
                <button type="submit" class="button button-primary" name="astt_rescore_themes" value="1">
                    <?php esc_html_e('Rescore themes', 'as-transcript-themes'); ?>
                </button>
            </p>
        </form>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Rank', 'as-transcript-themes'); ?></th>
                    <th><?php esc_html_e('Theme', 'as-transcript-themes'); ?></th>
                    <th><?php esc_html_e('Score', 'as-transcript-themes'); ?></th>
                    <th><?php esc_html_e('Sources', 'as-transcript-themes'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($themes)) : ?>
                    <tr><td colspan="4"><?php esc_html_e('No themes yet.', 'as-transcript-themes'); ?></td></tr>
                <?php endif; ?>
                <?php foreach ($themes as $theme) : ?>
                    <?php
                    $source_ids = astt_related_source_ids($theme->ID);
                    $source_ids = is_array($source_ids) ? $source_ids : array();
                    ?>
                    <tr>
                        <td><?php echo esc_html((string) get_post_meta($theme->ID, '_astt_theme_rank', true)); ?></td>
                        <td><a href="<?php echo esc_url(get_edit_post_link($theme->ID)); ?>"><?php echo esc_html(get_the_title($theme)); ?></a></td>
                        <td><?php echo esc_html((string) get_post_meta($theme->ID, '_astt_theme_score', true)); ?></td>
                        <td><?php echo esc_html((string) count($source_ids)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function astt_render_transcript_context_meta_box(WP_Post $post): void
{
    wp_nonce_field('astt_save_transcript_meta', 'astt_transcript_nonce');

    $when = astt_transcript_when($post->ID);
    $notes = astt_transcript_notes($post->ID);
    $people = astt_transcript_people($post->ID);
    if (empty($people)) {
        $people = array(array('who' => '', 'from' => ''));
    }
    ?>
    <div class="astt-field">
        <label for="astt_when"><?php esc_html_e('When', 'as-transcript-themes'); ?></label>
        <input type="datetime-local" id="astt_when" name="astt_when" value="<?php echo esc_attr(astt_datetime_for_input($when)); ?>">
    </div>

    <div class="astt-field">
        <label><?php esc_html_e('Who from where', 'as-transcript-themes'); ?></label>
        <div class="astt-repeater" data-astt-repeater>
            <?php foreach ($people as $index => $person) : ?>
                <div class="astt-repeater-row" data-astt-row>
                    <input type="text" name="astt_people[<?php echo esc_attr((string) $index); ?>][who]" value="<?php echo esc_attr((string) ($person['who'] ?? '')); ?>" placeholder="<?php esc_attr_e('Who', 'as-transcript-themes'); ?>">
                    <input type="text" name="astt_people[<?php echo esc_attr((string) $index); ?>][from]" value="<?php echo esc_attr((string) ($person['from'] ?? '')); ?>" placeholder="<?php esc_attr_e('From where', 'as-transcript-themes'); ?>">
                    <button type="button" class="button astt-remove-row" data-astt-remove-row><?php esc_html_e('Remove', 'as-transcript-themes'); ?></button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="button" data-astt-add-row><?php esc_html_e('Add person', 'as-transcript-themes'); ?></button>
    </div>

    <div class="astt-field">
        <label for="astt_notes"><?php esc_html_e('Meeting notes', 'as-transcript-themes'); ?></label>
        <textarea id="astt_notes" name="astt_notes" rows="7"><?php echo esc_textarea($notes); ?></textarea>
        <p class="description"><?php esc_html_e('Use notes to influence which themes matter, what was important, and what should be ignored.', 'as-transcript-themes'); ?></p>
    </div>
    <?php
}

function astt_render_email_context_meta_box(WP_Post $post): void
{
    wp_nonce_field('astt_save_email_meta', 'astt_email_nonce');

    $when = astt_transcript_when($post->ID);
    $notes = astt_transcript_notes($post->ID);
    $people = astt_email_people($post->ID);
    if (empty($people) && '' !== trim(wp_strip_all_tags($post->post_content))) {
        $people = astt_extract_email_people(wp_strip_all_tags($post->post_content));
    }
    ?>
    <div class="astt-field">
        <label for="astt_when"><?php esc_html_e('When', 'as-transcript-themes'); ?></label>
        <input type="datetime-local" id="astt_when" name="astt_when" value="<?php echo esc_attr(astt_datetime_for_input($when)); ?>">
    </div>

    <div class="astt-field">
        <label for="astt_notes"><?php esc_html_e('Thread notes', 'as-transcript-themes'); ?></label>
        <textarea id="astt_notes" name="astt_notes" rows="5"><?php echo esc_textarea($notes); ?></textarea>
        <p class="description"><?php esc_html_e('Use notes to influence which email-thread themes matter, what was important, and what should be ignored.', 'as-transcript-themes'); ?></p>
    </div>

    <div class="astt-field">
        <label><?php esc_html_e('Extracted people and organisations', 'as-transcript-themes'); ?></label>
        <?php if (empty($people)) : ?>
            <p><?php esc_html_e('No email addresses found yet. Paste the email thread into the editor and save.', 'as-transcript-themes'); ?></p>
        <?php else : ?>
            <table class="widefat striped astt-people-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Who', 'as-transcript-themes'); ?></th>
                        <th><?php esc_html_e('Organisation', 'as-transcript-themes'); ?></th>
                        <th><?php esc_html_e('Email', 'as-transcript-themes'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($people as $person) : ?>
                        <tr>
                            <td><?php echo esc_html((string) ($person['who'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($person['from'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($person['email'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <p class="description"><?php esc_html_e('Email participants are extracted from pasted email addresses. Domains become organisation clues.', 'as-transcript-themes'); ?></p>
    </div>
    <?php
}

function astt_render_relationships_meta_box(WP_Post $post): void
{
    $contact_ids = astt_related_contact_ids($post->ID);
    $org_ids = astt_related_org_ids($post->ID);
    $source_ids = astt_related_source_ids($post->ID);
    $theme_ids = astt_related_theme_ids($post->ID);

    if (ASTT_CONTACT_POST_TYPE === $post->post_type) {
        $org_id = absint(get_post_meta($post->ID, '_astt_org_id', true));
        if ($org_id) {
            $org_ids[] = $org_id;
        }
    }

    if (ASTT_ORG_POST_TYPE === $post->post_type) {
        $contact_ids = array_merge($contact_ids, astt_related_contact_ids($post->ID));
    }
    ?>
    <div class="astt-relationships">
        <?php astt_render_relationship_list(__('Themes', 'as-transcript-themes'), $theme_ids); ?>
        <?php astt_render_relationship_list(__('Sources', 'as-transcript-themes'), $source_ids); ?>
        <?php astt_render_relationship_list(__('Contacts', 'as-transcript-themes'), $contact_ids); ?>
        <?php astt_render_relationship_list(__('Organisations', 'as-transcript-themes'), $org_ids); ?>
    </div>
    <?php
}

function astt_render_contact_details_meta_box(WP_Post $post): void
{
    $email = (string) get_post_meta($post->ID, '_astt_email', true);
    $org_id = absint(get_post_meta($post->ID, '_astt_org_id', true));
    ?>
    <table class="form-table">
        <tr>
            <th><?php esc_html_e('Email', 'as-transcript-themes'); ?></th>
            <td><?php echo esc_html($email ?: __('Not known', 'as-transcript-themes')); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e('Organisation', 'as-transcript-themes'); ?></th>
            <td>
                <?php if ($org_id) : ?>
                    <a href="<?php echo esc_url(get_edit_post_link($org_id)); ?>"><?php echo esc_html(get_the_title($org_id)); ?></a>
                <?php else : ?>
                    <?php esc_html_e('Not known', 'as-transcript-themes'); ?>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <?php
}

function astt_render_org_details_meta_box(WP_Post $post): void
{
    $domain = (string) get_post_meta($post->ID, '_astt_domain', true);
    ?>
    <table class="form-table">
        <tr>
            <th><?php esc_html_e('Domain', 'as-transcript-themes'); ?></th>
            <td><?php echo esc_html($domain ?: __('Not known', 'as-transcript-themes')); ?></td>
        </tr>
    </table>
    <?php
}

function astt_render_relationship_list(string $label, array $ids): void
{
    $ids = array_values(array_filter(array_unique(array_map('absint', $ids))));
    ?>
    <p><strong><?php echo esc_html($label); ?></strong></p>
    <?php if (empty($ids)) : ?>
        <p class="description"><?php esc_html_e('None yet.', 'as-transcript-themes'); ?></p>
        <?php return; ?>
    <?php endif; ?>
    <ul class="astt-relationship-list">
        <?php foreach ($ids as $id) : ?>
            <li><a href="<?php echo esc_url(get_edit_post_link($id)); ?>"><?php echo esc_html(get_the_title($id)); ?></a></li>
        <?php endforeach; ?>
    </ul>
    <?php
}

function astt_render_transcript_processing_meta_box(WP_Post $post): void
{
    astt_render_source_processing_meta_box($post, __('Reprocess themes', 'as-transcript-themes'), __('Saving a changed transcript will process themes automatically.', 'as-transcript-themes'));
}

function astt_render_email_processing_meta_box(WP_Post $post): void
{
    astt_render_source_processing_meta_box($post, __('Reprocess themes', 'as-transcript-themes'), __('Saving a changed email thread will extract people and process themes automatically.', 'as-transcript-themes'));
}

function astt_render_source_processing_meta_box(WP_Post $post, string $button_label, string $description): void
{
    $status = (string) get_post_meta($post->ID, '_astt_status', true);
    $message = (string) get_post_meta($post->ID, '_astt_status_message', true);
    $processed_at = (string) get_post_meta($post->ID, '_astt_processed_at', true);
    ?>
    <p><strong><?php esc_html_e('Status', 'as-transcript-themes'); ?></strong><br><?php echo esc_html($status ?: __('Not processed', 'as-transcript-themes')); ?></p>
    <?php if ('' !== $message) : ?>
        <p><strong><?php esc_html_e('Message', 'as-transcript-themes'); ?></strong><br><?php echo esc_html($message); ?></p>
    <?php endif; ?>
    <?php if ('' !== $processed_at) : ?>
        <p><strong><?php esc_html_e('Last processed', 'as-transcript-themes'); ?></strong><br><?php echo esc_html(get_date_from_gmt(gmdate('Y-m-d H:i:s', strtotime($processed_at)), 'Y-m-d H:i')); ?></p>
    <?php endif; ?>
    <p>
        <button type="submit" class="button button-secondary" name="astt_reprocess" value="1">
            <?php echo esc_html($button_label); ?>
        </button>
    </p>
    <p class="description"><?php echo esc_html($description); ?></p>
    <?php
}

function astt_render_theme_details_meta_box(WP_Post $post): void
{
    $details = astt_theme_details($post->ID);
    $source_ids = astt_related_source_ids($post->ID);
    ?>
    <p>
        <strong><?php esc_html_e('Sources', 'as-transcript-themes'); ?></strong>
        <?php echo esc_html((string) count($source_ids)); ?>
    </p>

    <?php if (empty($details)) : ?>
        <p><?php esc_html_e('No source details have been attached yet.', 'as-transcript-themes'); ?></p>
    <?php else : ?>
        <div class="astt-theme-details">
            <?php foreach (array_reverse($details) as $detail) : ?>
                <section class="astt-theme-detail">
                    <h3><?php echo esc_html((string) ($detail['source_title'] ?? $detail['transcript_title'] ?? __('Source', 'as-transcript-themes'))); ?></h3>
                    <dl>
                        <dt><?php esc_html_e('Who', 'as-transcript-themes'); ?></dt>
                        <dd><?php echo esc_html((string) ($detail['who'] ?? '')); ?></dd>
                        <dt><?php esc_html_e('What', 'as-transcript-themes'); ?></dt>
                        <dd><?php echo esc_html((string) ($detail['what'] ?? '')); ?></dd>
                        <dt><?php esc_html_e('When', 'as-transcript-themes'); ?></dt>
                        <dd><?php echo esc_html((string) ($detail['when'] ?? '')); ?></dd>
                        <dt><?php esc_html_e('Why', 'as-transcript-themes'); ?></dt>
                        <dd><?php echo esc_html((string) ($detail['why'] ?? '')); ?></dd>
                        <dt><?php esc_html_e('Summary', 'as-transcript-themes'); ?></dt>
                        <dd><?php echo esc_html((string) ($detail['summary'] ?? '')); ?></dd>
                    </dl>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
}

function astt_save_transcript(int $post_id, WP_Post $post): void
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    if (empty($_POST['astt_transcript_nonce']) || !wp_verify_nonce((string) $_POST['astt_transcript_nonce'], 'astt_save_transcript_meta')) {
        return;
    }

    $when = sanitize_text_field((string) wp_unslash($_POST['astt_when'] ?? ''));
    $notes = sanitize_textarea_field((string) wp_unslash($_POST['astt_notes'] ?? ''));
    $people = astt_sanitize_people((array) wp_unslash($_POST['astt_people'] ?? array()));

    update_post_meta($post_id, '_astt_when', $when);
    update_post_meta($post_id, '_astt_notes', $notes);
    update_post_meta($post_id, '_astt_people', $people);

    if (in_array($post->post_status, array('auto-draft', 'trash'), true)) {
        return;
    }

    astt_ensure_source_title($post_id);
    astt_process_transcript($post_id, !empty($_POST['astt_reprocess']));
    astt_queue_admin_notice((string) get_post_meta($post_id, '_astt_status_message', true));
}

function astt_save_email(int $post_id, WP_Post $post): void
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    if (empty($_POST['astt_email_nonce']) || !wp_verify_nonce((string) $_POST['astt_email_nonce'], 'astt_save_email_meta')) {
        return;
    }

    $when = sanitize_text_field((string) wp_unslash($_POST['astt_when'] ?? ''));
    $notes = sanitize_textarea_field((string) wp_unslash($_POST['astt_notes'] ?? ''));
    $people = astt_extract_email_people(wp_strip_all_tags($post->post_content));

    update_post_meta($post_id, '_astt_when', $when);
    update_post_meta($post_id, '_astt_notes', $notes);
    update_post_meta($post_id, '_astt_people', $people);

    if (in_array($post->post_status, array('auto-draft', 'trash'), true)) {
        return;
    }

    astt_ensure_source_title($post_id);
    astt_process_email($post_id, !empty($_POST['astt_reprocess']));
    astt_queue_admin_notice((string) get_post_meta($post_id, '_astt_status_message', true));
}

function astt_queue_admin_notice(string $message): void
{
    if ('' === $message) {
        return;
    }

    set_transient('astt_notice_' . get_current_user_id(), $message, MINUTE_IN_SECONDS);
}

function astt_admin_notices(): void
{
    $key = 'astt_notice_' . get_current_user_id();
    $message = get_transient($key);
    if (!$message) {
        return;
    }

    delete_transient($key);
    echo '<div class="notice notice-info is-dismissible"><p>' . esc_html((string) $message) . '</p></div>';
}

function astt_sort_theme_admin_list(WP_Query $query): void
{
    if (!is_admin() || !$query->is_main_query() || ASTT_THEME_POST_TYPE !== $query->get('post_type')) {
        return;
    }

    if ($query->get('orderby')) {
        return;
    }

    $query->set('orderby', array(
        'menu_order' => 'DESC',
        'title' => 'ASC',
    ));
}

function astt_datetime_for_input(string $value): string
{
    if ('' === $value) {
        return '';
    }

    $timestamp = strtotime($value);
    return $timestamp ? gmdate('Y-m-d\TH:i', $timestamp) : '';
}

function astt_theme_columns(array $columns): array
{
    $columns['astt_score'] = __('Score', 'as-transcript-themes');
    $columns['astt_sources'] = __('Sources', 'as-transcript-themes');
    $columns['astt_contacts'] = __('Contacts', 'as-transcript-themes');
    $columns['astt_orgs'] = __('Organisations', 'as-transcript-themes');
    return $columns;
}

function astt_theme_column_content(string $column, int $post_id): void
{
    if ('astt_score' === $column) {
        echo esc_html((string) get_post_meta($post_id, '_astt_theme_score', true));
        return;
    }

    if ('astt_sources' === $column) {
        echo esc_html((string) count(astt_related_source_ids($post_id)));
        return;
    }

    if ('astt_contacts' === $column) {
        echo esc_html((string) count(astt_related_contact_ids($post_id)));
        return;
    }

    if ('astt_orgs' === $column) {
        echo esc_html((string) count(astt_related_org_ids($post_id)));
    }
}

function astt_contact_columns(array $columns): array
{
    $columns['astt_email'] = __('Email', 'as-transcript-themes');
    $columns['astt_org'] = __('Organisation', 'as-transcript-themes');
    $columns['astt_themes'] = __('Themes', 'as-transcript-themes');
    $columns['astt_sources'] = __('Sources', 'as-transcript-themes');
    return $columns;
}

function astt_contact_column_content(string $column, int $post_id): void
{
    if ('astt_email' === $column) {
        echo esc_html((string) get_post_meta($post_id, '_astt_email', true));
        return;
    }

    if ('astt_org' === $column) {
        $org_id = absint(get_post_meta($post_id, '_astt_org_id', true));
        echo $org_id ? '<a href="' . esc_url(get_edit_post_link($org_id)) . '">' . esc_html(get_the_title($org_id)) . '</a>' : '';
        return;
    }

    if ('astt_themes' === $column) {
        echo esc_html((string) count(astt_related_theme_ids($post_id)));
        return;
    }

    if ('astt_sources' === $column) {
        echo esc_html((string) count(astt_related_source_ids($post_id)));
    }
}

function astt_org_columns(array $columns): array
{
    $columns['astt_domain'] = __('Domain', 'as-transcript-themes');
    $columns['astt_contacts'] = __('Contacts', 'as-transcript-themes');
    $columns['astt_themes'] = __('Themes', 'as-transcript-themes');
    $columns['astt_sources'] = __('Sources', 'as-transcript-themes');
    return $columns;
}

function astt_org_column_content(string $column, int $post_id): void
{
    if ('astt_domain' === $column) {
        echo esc_html((string) get_post_meta($post_id, '_astt_domain', true));
        return;
    }

    if ('astt_contacts' === $column) {
        echo esc_html((string) count(astt_related_contact_ids($post_id)));
        return;
    }

    if ('astt_themes' === $column) {
        echo esc_html((string) count(astt_related_theme_ids($post_id)));
        return;
    }

    if ('astt_sources' === $column) {
        echo esc_html((string) count(astt_related_source_ids($post_id)));
    }
}
