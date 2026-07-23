<?php

if (!defined('ABSPATH')) {
    exit;
}

function astt_transcript_when(int $post_id): string
{
    return (string) get_post_meta($post_id, '_astt_when', true);
}

function astt_transcript_notes(int $post_id): string
{
    return (string) get_post_meta($post_id, '_astt_notes', true);
}

function astt_source_content(WP_Post $post): string
{
    $content = (string) $post->post_content;
    if ('' !== trim($content)) {
        return $content;
    }

    return (string) get_post_meta($post->ID, '_astt_source_content', true);
}

function astt_transcript_people(int $post_id): array
{
    $people = get_post_meta($post_id, '_astt_people', true);
    return is_array($people) ? $people : array();
}

function astt_source_people(int $post_id): array
{
    $post = get_post($post_id);
    if ($post instanceof WP_Post && ASTT_EMAIL_POST_TYPE === $post->post_type) {
        return astt_email_people($post_id);
    }

    return astt_transcript_people($post_id);
}

function astt_email_people(int $post_id): array
{
    $people = get_post_meta($post_id, '_astt_people', true);
    return is_array($people) ? $people : array();
}

function astt_theme_details(int $post_id): array
{
    $details = get_post_meta($post_id, '_astt_details', true);
    return is_array($details) ? $details : array();
}

function astt_sanitize_people(array $rows): array
{
    $clean = array();

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $who = sanitize_text_field((string) ($row['who'] ?? ''));
        $from = sanitize_text_field((string) ($row['from'] ?? ''));
        $email = sanitize_email((string) ($row['email'] ?? ''));

        if ('' !== $who || '' !== $from) {
            $clean[] = array(
                'who' => substr($who, 0, 160),
                'from' => substr($from, 0, 160),
                'email' => substr($email, 0, 190),
            );
        }
    }

    return array_values($clean);
}

function astt_sanitize_ai_text(string $value, int $max_length = 1200): string
{
    $value = trim(wp_strip_all_tags($value));
    return substr($value, 0, $max_length);
}

function astt_ensure_source_title(int $post_id): void
{
    static $updating = false;

    if ($updating) {
        return;
    }

    $post = get_post($post_id);
    if (!$post instanceof WP_Post || !in_array($post->post_type, array(ASTT_TRANSCRIPT_POST_TYPE, ASTT_EMAIL_POST_TYPE), true)) {
        return;
    }

    $title = trim(wp_strip_all_tags($post->post_title));
    if ('' !== $title && !preg_match('/^auto draft$/i', $title)) {
        return;
    }

    $generated = astt_generate_source_title($post);
    if ('' === $generated) {
        return;
    }

    $updating = true;
    wp_update_post(array(
        'ID' => $post_id,
        'post_title' => $generated,
        'post_name' => sanitize_title($generated),
    ));
    $updating = false;
}

function astt_generate_source_title(WP_Post $post): string
{
    $content = trim(wp_strip_all_tags(astt_source_content($post)));
    if (ASTT_EMAIL_POST_TYPE === $post->post_type) {
        return astt_generate_email_title($post, $content);
    }

    return astt_generate_transcript_title($post, $content);
}

function astt_generate_email_title(WP_Post $post, string $content): string
{
    $subject = astt_extract_email_header($content, 'Subject');
    $date = astt_extract_email_header($content, 'Date') ?: astt_transcript_when($post->ID);
    $from = astt_extract_email_header($content, 'From');

    $parts = array_filter(array(
        $subject ? 'Email - ' . $subject : 'Email Thread',
        $from ? 'from ' . astt_compact_email_identity($from) : '',
        $date ? astt_compact_date_label($date) : '',
    ));

    return astt_trim_title(implode(' - ', $parts));
}

function astt_generate_transcript_title(WP_Post $post, string $content): string
{
    $when = astt_transcript_when($post->ID);
    $line = astt_first_meaningful_line($content);

    $parts = array_filter(array(
        'Transcript',
        $when ? astt_compact_date_label($when) : '',
        $line,
    ));

    return astt_trim_title(implode(' - ', $parts));
}

function astt_extract_email_header(string $content, string $header): string
{
    if (preg_match('/^' . preg_quote($header, '/') . ':\\s*(.+)$/mi', $content, $matches)) {
        return trim((string) $matches[1]);
    }

    return '';
}

function astt_compact_email_identity(string $value): string
{
    if (preg_match('/(?:"?([^"<\\n]+)"?\\s*)?<([A-Z0-9._%+\\-]+@[A-Z0-9.\\-]+\\.[A-Z]{2,})>/i', $value, $matches)) {
        return trim($matches[1]) ?: strtolower($matches[2]);
    }

    if (preg_match('/\\b([A-Z0-9._%+\\-]+@[A-Z0-9.\\-]+\\.[A-Z]{2,})\\b/i', $value, $matches)) {
        return strtolower($matches[1]);
    }

    return astt_trim_title($value, 60);
}

function astt_compact_date_label(string $value): string
{
    $timestamp = strtotime($value);
    return $timestamp ? wp_date('Y-m-d H:i', $timestamp) : astt_trim_title($value, 32);
}

function astt_first_meaningful_line(string $content): string
{
    foreach (preg_split('/\\R+/', $content) ?: array() as $line) {
        $line = trim($line);
        if (strlen($line) < 8 || preg_match('/^(from|to|cc|bcc|date|subject):/i', $line)) {
            continue;
        }

        return astt_trim_title($line, 80);
    }

    return '';
}

function astt_trim_title(string $value, int $length = 120): string
{
    $value = trim(preg_replace('/\\s+/', ' ', wp_strip_all_tags($value)));
    if (strlen($value) <= $length) {
        return $value;
    }

    return rtrim(substr($value, 0, $length - 1)) . '...';
}

function astt_processing_hash(WP_Post $post): string
{
    return hash('sha256', wp_json_encode(array(
        'type' => $post->post_type,
        'title' => $post->post_title,
        'content' => astt_source_content($post),
        'when' => astt_transcript_when($post->ID),
        'notes' => astt_transcript_notes($post->ID),
        'people' => astt_source_people($post->ID),
    )));
}

function astt_ai_available(): bool
{
    if (!function_exists('wp_ai_client_prompt')) {
        return false;
    }

    $builder = wp_ai_client_prompt('Return the word ready.');
    return is_object($builder) && method_exists($builder, 'is_supported_for_text_generation') && $builder->is_supported_for_text_generation();
}

function astt_theme_schema(): array
{
    return array(
        'type' => 'object',
        'properties' => array(
            'themes' => array(
                'type' => 'array',
                'items' => array(
                    'type' => 'object',
                    'properties' => array(
                        'name' => array('type' => 'string'),
                        'point_of_view' => array('type' => 'string'),
                        'importance' => array('type' => 'string'),
                        'who' => array('type' => 'string'),
                        'what' => array('type' => 'string'),
                        'when' => array('type' => 'string'),
                        'why' => array('type' => 'string'),
                        'summary' => array('type' => 'string'),
                        'evidence' => array(
                            'type' => 'array',
                            'items' => array('type' => 'string'),
                        ),
                    ),
                    'required' => array('name', 'point_of_view', 'importance', 'who', 'what', 'when', 'why', 'summary'),
                ),
            ),
        ),
        'required' => array('themes'),
    );
}

function astt_build_prompt(WP_Post $post): string
{
    $people = astt_source_people($post->ID);
    $people_lines = array();

    foreach ($people as $person) {
        $people_lines[] = '- ' . ($person['who'] ?? '') . ' from ' . ($person['from'] ?? '');
    }

    $source_label = ASTT_EMAIL_POST_TYPE === $post->post_type ? 'email thread' : 'meeting transcript';

    return implode("\n\n", array(
        'You identify durable discussion themes from ' . $source_label . 's.',
        'Find only the big themes that matter across the conversation. Do not list every topic. Demote passing speculation, short tangents, logistics, and items that did not get meaningful attention. Promote topics that recur, carry decisions, reveal tension, show customer need, or shape a point of view. Merge overlapping themes into one stronger theme.',
        'For each theme, provide a concise title, a researched/considered point of view suitable for the Theme post body, and short narrative who/what/when/why details grounded in the source.',
        'Source type: ' . $source_label,
        'Source title: ' . $post->post_title,
        'Source when: ' . (astt_transcript_when($post->ID) ?: 'Not supplied'),
        "People and organisations:\n" . (!empty($people_lines) ? implode("\n", $people_lines) : 'Not supplied'),
        "Source notes:\n" . (astt_transcript_notes($post->ID) ?: 'Not supplied'),
        "Source content:\n" . wp_strip_all_tags(astt_source_content($post)),
    ));
}

function astt_process_transcript(int $post_id, bool $force = false): void
{
    astt_process_source($post_id, $force);
}

function astt_process_email(int $post_id, bool $force = false): void
{
    astt_process_source($post_id, $force);
}

function astt_process_source(int $post_id, bool $force = false): void
{
    $post = get_post($post_id);
    if (!$post instanceof WP_Post || !in_array($post->post_type, array(ASTT_TRANSCRIPT_POST_TYPE, ASTT_EMAIL_POST_TYPE), true)) {
        return;
    }

    if ('' === trim(wp_strip_all_tags(astt_source_content($post)))) {
        update_post_meta($post_id, '_astt_status', 'skipped_empty');
        update_post_meta($post_id, '_astt_status_message', __('No source content to process.', 'as-transcript-themes'));
        return;
    }

    $hash = astt_processing_hash($post);
    if (!$force && $hash === get_post_meta($post_id, '_astt_processed_hash', true)) {
        return;
    }

    if (!astt_ai_available()) {
        update_post_meta($post_id, '_astt_status', 'ai_unavailable');
        update_post_meta($post_id, '_astt_status_message', __('WordPress AI text generation is not available. Configure an AI provider in Settings > Connectors.', 'as-transcript-themes'));
        return;
    }

    update_post_meta($post_id, '_astt_status', 'processing');
    update_post_meta($post_id, '_astt_status_message', __('Processing source themes.', 'as-transcript-themes'));

    $json = wp_ai_client_prompt(astt_build_prompt($post))
        ->using_temperature(0.2)
        ->using_system_instruction('Return only structured JSON that matches the supplied schema. Be selective. Identify durable themes, not all topics.')
        ->as_json_response(astt_theme_schema())
        ->generate_text();

    if (is_wp_error($json)) {
        update_post_meta($post_id, '_astt_status', 'error');
        update_post_meta($post_id, '_astt_status_message', $json->get_error_message());
        return;
    }

    $data = json_decode((string) $json, true);
    if (!is_array($data) || !isset($data['themes']) || !is_array($data['themes'])) {
        update_post_meta($post_id, '_astt_status', 'error');
        update_post_meta($post_id, '_astt_status_message', __('The AI response was not valid theme JSON.', 'as-transcript-themes'));
        return;
    }

    $count = astt_apply_themes($post, $data['themes']);
    astt_rank_themes();
    update_post_meta($post_id, '_astt_processed_hash', $hash);
    update_post_meta($post_id, '_astt_processed_at', gmdate('c'));
    update_post_meta($post_id, '_astt_status', 'complete');
    update_post_meta($post_id, '_astt_status_message', sprintf(__('%d themes processed.', 'as-transcript-themes'), $count));
}

function astt_apply_themes(WP_Post $source, array $themes): int
{
    $count = 0;

    foreach ($themes as $theme) {
        if (!is_array($theme)) {
            continue;
        }

        $name = astt_sanitize_ai_text((string) ($theme['name'] ?? ''), 120);
        if ('' === $name) {
            continue;
        }

        $theme_id = astt_find_or_create_theme($name);
        if (!$theme_id) {
            continue;
        }

        $body = astt_sanitize_ai_text((string) ($theme['point_of_view'] ?? ''), 6000);
        if ('' !== $body) {
            wp_update_post(array(
                'ID' => $theme_id,
                'post_content' => $body,
            ));
        }

        $details = astt_theme_details($theme_id);
        $details[] = astt_theme_detail_from_ai($source, $theme);
        update_post_meta($theme_id, '_astt_details', array_slice($details, -50));

        astt_connect_source_entities($source);
        astt_connect_theme_entities($theme_id, $source->ID);

        $source_ids = get_post_meta($theme_id, '_astt_source_posts', true);
        $source_ids = is_array($source_ids) ? array_map('absint', $source_ids) : array();
        $source_ids[] = $source->ID;
        update_post_meta($theme_id, '_astt_source_posts', array_values(array_unique($source_ids)));
        update_post_meta($theme_id, '_astt_last_seen_at', gmdate('c'));

        $count++;
    }

    return $count;
}

function astt_find_or_create_theme(string $name): int
{
    $theme_key = sanitize_title($name);
    $existing = get_posts(array(
        'post_type' => ASTT_THEME_POST_TYPE,
        'post_status' => 'any',
        'fields' => 'ids',
        'posts_per_page' => 1,
        'meta_key' => '_astt_theme_key',
        'meta_value' => $theme_key,
    ));

    if (!empty($existing)) {
        return (int) $existing[0];
    }

    $theme_id = (int) wp_insert_post(array(
        'post_type' => ASTT_THEME_POST_TYPE,
        'post_status' => 'publish',
        'post_title' => $name,
        'post_content' => '',
    ));

    if ($theme_id) {
        update_post_meta($theme_id, '_astt_theme_key', $theme_key);
    }

    return $theme_id;
}

function astt_connect_source_entities(WP_Post $source): void
{
    $people = astt_source_people($source->ID);
    $contact_ids = array();
    $org_ids = array();

    foreach ($people as $person) {
        if (!is_array($person)) {
            continue;
        }

        $org_id = astt_find_or_create_organisation((string) ($person['from'] ?? ''));
        if ($org_id) {
            $org_ids[] = $org_id;
        }

        $contact_id = astt_find_or_create_contact((string) ($person['who'] ?? ''), (string) ($person['email'] ?? ''), $org_id);
        if ($contact_id) {
            $contact_ids[] = $contact_id;
            astt_add_related_id($contact_id, '_astt_source_posts', $source->ID);
        }

        if ($org_id) {
            astt_add_related_id($org_id, '_astt_source_posts', $source->ID);
        }
    }

    update_post_meta($source->ID, '_astt_contact_ids', array_values(array_unique(array_map('absint', $contact_ids))));
    update_post_meta($source->ID, '_astt_org_ids', array_values(array_unique(array_map('absint', $org_ids))));
}

function astt_connect_theme_entities(int $theme_id, int $source_id): void
{
    $contact_ids = get_post_meta($source_id, '_astt_contact_ids', true);
    $org_ids = get_post_meta($source_id, '_astt_org_ids', true);

    $theme_contact_ids = get_post_meta($theme_id, '_astt_contact_ids', true);
    $theme_org_ids = get_post_meta($theme_id, '_astt_org_ids', true);

    $theme_contact_ids = is_array($theme_contact_ids) ? $theme_contact_ids : array();
    $theme_org_ids = is_array($theme_org_ids) ? $theme_org_ids : array();

    update_post_meta($theme_id, '_astt_contact_ids', array_values(array_unique(array_map('absint', array_merge($theme_contact_ids, is_array($contact_ids) ? $contact_ids : array())))));
    update_post_meta($theme_id, '_astt_org_ids', array_values(array_unique(array_map('absint', array_merge($theme_org_ids, is_array($org_ids) ? $org_ids : array())))));

    foreach (is_array($contact_ids) ? $contact_ids : array() as $contact_id) {
        astt_add_related_id(absint($contact_id), '_astt_theme_ids', $theme_id);
    }

    foreach (is_array($org_ids) ? $org_ids : array() as $org_id) {
        astt_add_related_id(absint($org_id), '_astt_theme_ids', $theme_id);
    }
}

function astt_add_related_id(int $post_id, string $meta_key, int $related_id): void
{
    if (!$post_id || !$related_id) {
        return;
    }

    $ids = get_post_meta($post_id, $meta_key, true);
    $ids = is_array($ids) ? $ids : array();
    $ids[] = $related_id;
    update_post_meta($post_id, $meta_key, array_values(array_unique(array_map('absint', $ids))));
}

function astt_find_or_create_contact(string $name, string $email = '', int $org_id = 0): int
{
    $email = sanitize_email($email);
    $key = '' !== $email ? strtolower($email) : sanitize_title($name);
    if ('' === $key) {
        return 0;
    }

    $existing = get_posts(array(
        'post_type' => ASTT_CONTACT_POST_TYPE,
        'post_status' => 'any',
        'fields' => 'ids',
        'posts_per_page' => 1,
        'meta_key' => '_astt_contact_key',
        'meta_value' => $key,
    ));

    $contact_id = !empty($existing) ? (int) $existing[0] : 0;
    if (!$contact_id) {
        $contact_id = (int) wp_insert_post(array(
            'post_type' => ASTT_CONTACT_POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $name ?: $email,
        ));
        update_post_meta($contact_id, '_astt_contact_key', $key);
    }

    if ($contact_id && '' !== $email) {
        update_post_meta($contact_id, '_astt_email', $email);
    }

    if ($contact_id && $org_id) {
        update_post_meta($contact_id, '_astt_org_id', $org_id);
        $contact_ids = get_post_meta($org_id, '_astt_contact_ids', true);
        $contact_ids = is_array($contact_ids) ? $contact_ids : array();
        $contact_ids[] = $contact_id;
        update_post_meta($org_id, '_astt_contact_ids', array_values(array_unique(array_map('absint', $contact_ids))));
    }

    return $contact_id;
}

function astt_find_or_create_organisation(string $name): int
{
    $name = trim(sanitize_text_field($name));
    if ('' === $name) {
        return 0;
    }

    $key = sanitize_title($name);
    $existing = get_posts(array(
        'post_type' => ASTT_ORG_POST_TYPE,
        'post_status' => 'any',
        'fields' => 'ids',
        'posts_per_page' => 1,
        'meta_key' => '_astt_org_key',
        'meta_value' => $key,
    ));

    if (!empty($existing)) {
        return (int) $existing[0];
    }

    $org_id = (int) wp_insert_post(array(
        'post_type' => ASTT_ORG_POST_TYPE,
        'post_status' => 'publish',
        'post_title' => $name,
    ));

    if ($org_id) {
        update_post_meta($org_id, '_astt_org_key', $key);
        if (false !== strpos($name, '.')) {
            update_post_meta($org_id, '_astt_domain', strtolower($name));
        }
    }

    return $org_id;
}

function astt_related_source_ids(int $post_id): array
{
    $ids = get_post_meta($post_id, '_astt_source_posts', true);
    if (!is_array($ids)) {
        $ids = get_post_meta($post_id, '_astt_source_transcripts', true);
    }

    return is_array($ids) ? array_values(array_unique(array_map('absint', $ids))) : array();
}

function astt_related_contact_ids(int $post_id): array
{
    $ids = get_post_meta($post_id, '_astt_contact_ids', true);
    if (!is_array($ids) && ASTT_ORG_POST_TYPE === get_post_type($post_id)) {
        $ids = get_posts(array(
            'post_type' => ASTT_CONTACT_POST_TYPE,
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => -1,
            'meta_key' => '_astt_org_id',
            'meta_value' => $post_id,
        ));
    }

    return is_array($ids) ? array_values(array_unique(array_map('absint', $ids))) : array();
}

function astt_related_org_ids(int $post_id): array
{
    $ids = get_post_meta($post_id, '_astt_org_ids', true);
    return is_array($ids) ? array_values(array_unique(array_map('absint', $ids))) : array();
}

function astt_related_theme_ids(int $post_id): array
{
    $ids = get_post_meta($post_id, '_astt_theme_ids', true);
    if (is_array($ids)) {
        return array_values(array_unique(array_map('absint', $ids)));
    }

    if (!in_array(get_post_type($post_id), array(ASTT_TRANSCRIPT_POST_TYPE, ASTT_EMAIL_POST_TYPE), true)) {
        return array();
    }

    $themes = get_posts(array(
        'post_type' => ASTT_THEME_POST_TYPE,
        'post_status' => 'any',
        'fields' => 'ids',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_astt_source_posts',
                'value' => '"' . $post_id . '"',
                'compare' => 'LIKE',
            ),
        ),
    ));

    return array_values(array_unique(array_map('absint', $themes)));
}

function astt_theme_detail_from_ai(WP_Post $source, array $theme): array
{
    return array(
        'source_id' => $source->ID,
        'source_type' => $source->post_type,
        'source_title' => get_the_title($source),
        'transcript_id' => $source->ID,
        'transcript_title' => get_the_title($source),
        'recorded_at' => astt_transcript_when($source->ID),
        'processed_at' => gmdate('c'),
        'who' => astt_sanitize_ai_text((string) ($theme['who'] ?? ''), 500),
        'what' => astt_sanitize_ai_text((string) ($theme['what'] ?? ''), 700),
        'when' => astt_sanitize_ai_text((string) ($theme['when'] ?? ''), 300),
        'why' => astt_sanitize_ai_text((string) ($theme['why'] ?? ''), 700),
        'summary' => astt_sanitize_ai_text((string) ($theme['summary'] ?? ''), 900),
        'importance' => astt_sanitize_ai_text((string) ($theme['importance'] ?? ''), 400),
        'evidence' => array_map(static function ($item) {
            return astt_sanitize_ai_text((string) $item, 260);
        }, (array) ($theme['evidence'] ?? array())),
    );
}

function astt_rank_themes(): int
{
    $theme_ids = get_posts(array(
        'post_type' => ASTT_THEME_POST_TYPE,
        'post_status' => 'any',
        'fields' => 'ids',
        'posts_per_page' => -1,
    ));

    $ranked = array();
    foreach ($theme_ids as $theme_id) {
        $theme_id = absint($theme_id);
        $source_ids = get_post_meta($theme_id, '_astt_source_posts', true);
        if (!is_array($source_ids)) {
            $source_ids = get_post_meta($theme_id, '_astt_source_transcripts', true);
        }
        $source_ids = is_array($source_ids) ? array_values(array_unique(array_map('absint', $source_ids))) : array();
        $details = astt_theme_details($theme_id);
        $last_seen = (string) get_post_meta($theme_id, '_astt_last_seen_at', true);

        $score = (count($source_ids) * 100) + (count($details) * 10) + astt_recency_score($last_seen);
        $ranked[] = array(
            'id' => $theme_id,
            'score' => $score,
        );
    }

    usort($ranked, static function (array $a, array $b): int {
        if ($a['score'] === $b['score']) {
            return $a['id'] <=> $b['id'];
        }

        return $b['score'] <=> $a['score'];
    });

    foreach ($ranked as $index => $item) {
        update_post_meta($item['id'], '_astt_theme_score', $item['score']);
        wp_update_post(array(
            'ID' => $item['id'],
            'menu_order' => $item['score'],
        ));
        update_post_meta($item['id'], '_astt_theme_rank', $index + 1);
    }

    return count($ranked);
}

function astt_recency_score(string $last_seen): int
{
    $timestamp = $last_seen ? strtotime($last_seen) : 0;
        if (!$timestamp) {
        return 0;
    }

    $days_old = max(0, (int) floor((time() - $timestamp) / DAY_IN_SECONDS));
    return max(0, 30 - min(30, $days_old));
}

function astt_extract_email_people(string $content): array
{
    preg_match_all('/(?:"?([^"<\\n]+)"?\\s*)?<([A-Z0-9._%+\\-]+@[A-Z0-9.\\-]+\\.[A-Z]{2,})>|\\b([A-Z0-9._%+\\-]+@[A-Z0-9.\\-]+\\.[A-Z]{2,})\\b/i', $content, $matches, PREG_SET_ORDER);

    $people = array();
    foreach ($matches as $match) {
        $email = strtolower((string) ($match[2] ?: $match[3] ?? ''));
        if ('' === $email || isset($people[$email])) {
            continue;
        }

        $name = trim((string) ($match[1] ?? ''));
        $name = trim($name, " \t\n\r\0\x0B\"'");
        if ('' === $name) {
            $name = substr($email, 0, (int) strpos($email, '@'));
        }

        $domain = substr($email, (int) strpos($email, '@') + 1);
        $people[$email] = array(
            'who' => substr(sanitize_text_field($name), 0, 160),
            'from' => substr(sanitize_text_field($domain), 0, 160),
            'email' => substr(sanitize_email($email), 0, 190),
        );
    }

    return array_values($people);
}
