# Changelog

All notable changes to AS Transcript Themes are recorded here.

## 0.1.4 - 2026-07-23

- Removed the pre-flight AI text support check that could report false unavailable status even when a connector was configured.
- Changed processing to attempt the real AI Client generation request and surface the actual `WP_Error` message if generation fails.

## 0.1.3 - 2026-07-23

- Simplified source editing so Transcript and Email post content is the main input area again.
- Removed unnecessary source-content, participant, email context, contact detail, organisation detail, and theme detail meta boxes.
- Kept only Transcript Notes, source processing status, and clickable relationship sidebars.
- Preserved old source-content meta as a fallback for records created in 0.1.2.

## 0.1.2 - 2026-07-23

- Added dedicated source-content textareas for Transcript and Email records.
- Made source-content meta the canonical content processed by the AI theme extractor.
- Removed the main editor body from Transcript and Email source capture screens.
- Kept old editor content as a fallback for records created before this release.

## 0.1.1 - 2026-07-23

- Separated Transcripts, Emails, Themes, Contacts, and Organisations into their own WordPress admin menus.
- Disabled the block editor for Transcript and Email source capture.
- Added automatic source title generation so transcript and email sources can be saved without manually entering a post title.
- Moved Theme Ranking under the Themes admin menu.

## 0.1.0 - 2026-07-21

- Added Transcript and Theme custom post types.
- Added Email, Contact, and Organisation custom post types.
- Added transcript context fields for meeting date, participants, and meeting notes.
- Added email-thread capture with participant and organisation extraction from email addresses.
- Added AI-powered theme extraction using the WordPress 7 AI Client.
- Added theme creation and update flow with transcript-derived who, what, when, why summaries.
- Added bidirectional admin relationship navigation between sources, themes, contacts, and organisations.
- Added theme bigness ranking through `menu_order` and ranking meta.
- Added a manual Theme Ranking admin page with a rescore action.
- Added GitHub release updater with native WordPress update checks.
