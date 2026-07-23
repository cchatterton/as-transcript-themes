# AS Transcript Themes

Author: AlphaSys  
Version: 0.1.2  
Status: MVP

## Purpose

AS Transcript Themes turns saved meeting transcripts and email threads into durable discussion themes using the WordPress 7 AI Client.

The plugin is designed to identify big themes that are trending across conversations, not every passing topic mentioned in a source.

## Key Features

- Adds a Transcript post type for meeting transcript capture.
- Adds an Email post type for pasted email thread capture.
- Adds a Theme post type for durable themes and evolving points of view.
- Adds Contact and Organisation post types for traceable people/org relationships.
- Captures transcript context: who, from where, meeting date, and meeting notes.
- Extracts email participants and organisation domains from pasted email threads.
- Processes transcript content on save when the source material changes.
- Uses `wp_ai_client_prompt()` and structured JSON responses through the configured WordPress AI provider.
- Creates or updates Theme posts from the strongest extracted themes.
- Stores transcript-derived who, what, when, why summary details as repeatable Theme post meta.
- Adds WP admin relationship sidebars so sources, themes, contacts, and organisations can be clicked through like a small CRM or PM tool.
- Uses classic editor screens for transcript and email source capture.
- Provides dedicated plain textarea source fields for transcript and email thread content.
- Auto-generates source titles from email headers, dates, senders, or first meaningful transcript lines when title is left blank.
- Ranks themes by bigness using source transcript count, detail frequency, and recency.
- Adds a manual Theme Ranking page for re-ranking when reviewing themes locally.
- Includes GitHub release updates from the WordPress Plugins screen.

## Folder Structure

```text
as-transcript-themes/
├── as-transcript-themes.php
├── readme.md
├── functions/
│   ├── setup.php
│   ├── admin.php
│   ├── assets.php
│   ├── rest.php
│   ├── helpers.php
│   └── updater.php
├── scripts/
│   └── as-transcript-themes.js
├── styles/
│   └── as-transcript-themes.css
└── templates/
    └── .gitkeep
```

## Important Notes

- Requires WordPress 7.0 or later.
- Requires a configured AI provider through Settings > Connectors.
- Processing happens server-side on transcript and email saves.
- The source textarea, not the notes field, is the content processed for themes.
- Existing themes are matched by exact title for the MVP.
- Theme post bodies are replaced with the latest AI point of view when a matching theme is updated.
- GitHub releases must include `as-transcript-themes.zip` for WordPress-native updates.

## Future Considerations

- Add stronger semantic matching for merge/evolve behaviour across theme names.
- Add queued/background processing for very long transcripts.
- Add manual theme merge tools.
