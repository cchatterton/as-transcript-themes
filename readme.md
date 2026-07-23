# AS Transcript Themes

Author: AlphaSys  
Version: 0.2.0  
Status: MVP

## Purpose

AS Transcript Themes turns saved meeting transcripts and email threads into topics, themes, and commitments using the WordPress 7 AI Client.

The plugin keeps source inputs simple, then creates clickable workspaces for the things that matter across conversations.

## Key Features

- Adds a Transcript post type for meeting transcript capture.
- Adds an Email post type for pasted email thread capture.
- Adds a Topic post type for concrete discussion items, findings, risks, decisions, and recommendations.
- Adds a Theme post type for higher-level umbrella concepts that topics roll up into.
- Adds a Commitment post type for actions and expectations, with direction and status inferred behind the scenes.
- Adds Contact and Organisation post types for traceable people/org relationships.
- Extracts email participants and organisation domains from pasted email threads.
- Processes Transcript and Email post content on save when the source material changes.
- Uses `wp_ai_client_prompt()` and structured JSON responses through the configured WordPress AI provider.
- Creates or updates Topic, Theme, and Commitment posts from the strongest extracted signals.
- Stores source-derived details as repeatable hidden meta while keeping post content available for your notes and point of view.
- Adds WP admin relationship sidebars so sources, topics, themes, commitments, contacts, and organisations can be clicked through like a small CRM or PM tool.
- Uses classic editor screens for transcript and email source capture.
- Auto-generates source titles from email headers, dates, senders, or first meaningful transcript lines when title is left blank.
- Ranks workspaces by source count, detail frequency, recency, and open commitment heat.
- Adds a manual Ranking page for re-ranking when reviewing work locally.
- Adds dashboard widgets for top topics, top themes, actions owed by you, and expectations owed to you.
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
- Transcript and Email post content is the source material processed for topics, themes, and commitments.
- Topic and Theme post bodies are your evolving notes/workspace content once created.
- Commitment post titles are the commitment; commitment post content is your notes and planning space.
- Existing topics, themes, and commitments are matched by normalized title for the MVP.
- GitHub releases must include `as-transcript-themes.zip` for WordPress-native updates.

## Future Considerations

- Add stronger semantic matching for merge/evolve behaviour across theme names.
- Add queued/background processing for very long transcripts.
- Add manual theme merge tools.
