# Plugin Marketplace Planning Overview

## Status

Last updated: 2026-05-25

- This document is a planning overview only.
- No marketplace service, forum API, package format extension, or Plugin Manager marketplace UI is implemented yet.
- The current plugin workflow remains manual: download a plugin archive, extract it, move the plugin code into `zc_plugins/`, then install or enable it through the existing Zen Cart plugin lifecycle.

## Goal

Provide a secure plugin marketplace experience inside Zen Cart `Plugin Manager` so an admin can:

- search a curated catalog of plugins
- inspect plugin details and compatibility
- download a plugin package directly from admin
- verify package integrity and authenticity
- extract the package safely into `zc_plugins/<unique_key>/<version>/`
- continue through the existing install and enable workflow

This should improve discovery and installation without inventing a second plugin runtime.

## Source Of Truth

This plan assumes the forum or plugin library is the source of truth for available plugins.

That means:

- plugin listings are published and moderated through the forum-side system
- the forum-side system owns release records and approval state
- Zen Cart `Plugin Manager` consumes a structured forum-backed API
- Zen Cart should not scrape forum HTML directly

## Document Split

The work is split into two implementation tracks:

- [Zen Cart Client Plan](/home/wilt/Projects/zencart/docs/plugin-marketplace-zencart-client-plan.md)
- [Forum API Plan](/home/wilt/Projects/zencart/docs/plugin-marketplace-forum-api-plan.md)

## Shared Constraints

Both tracks should align on these assumptions:

- curated plugins only in v1
- public read-only catalog API
- no auth required for public plugin search and metadata
- throttling and caching required from day one
- package integrity verification required
- package authenticity verification strongly recommended
- no auto-enable after download
- compatibility failures must block install

## Recommended Delivery Order

1. Define package and API contracts.
2. Build the forum-backed read-only API.
3. Add Zen Cart browse and details UI.
4. Add secure download, verification, and extraction.
5. Add update checks and update flow.
6. Add publisher tooling and moderation improvements.
