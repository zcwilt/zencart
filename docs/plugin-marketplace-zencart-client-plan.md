# Plugin Marketplace Zen Cart Client Plan

## Status

Last updated: 2026-05-25

- This document is a planning proposal only.
- No Plugin Manager marketplace UI or package-install flow is implemented yet.
- It assumes the forum-backed API is the authoritative source for available plugins and approved releases.

## Goal

Extend Zen Cart `Plugin Manager` so it can:

- browse a forum-backed plugin marketplace
- display plugin detail and compatibility metadata
- download plugin packages directly from admin
- verify package integrity and authenticity
- extract valid packages safely into `zc_plugins/<unique_key>/<version>/`
- continue through the existing plugin install or enable lifecycle

## Non-Goals

This proposal does not assume:

- scraping forum HTML directly
- replacing the current plugin runtime architecture
- auto-enabling plugins after download
- a full dependency solver in the first release
- bypassing installer scripts or plugin-control state

## Why This Must Preserve The Existing Plugin Lifecycle

Marketplace install should change how plugin files arrive on disk, not how Zen Cart runs installed plugins.

The existing plugin lifecycle should remain intact:

- plugin files land under `zc_plugins/<unique_key>/<version>/`
- Plugin Manager discovers the plugin locally
- install and upgrade scripts remain the mechanism for DB and config changes
- plugin enablement still follows the current install or activate flow

## Zen Cart Client Responsibilities

Plugin Manager in Zen Cart should handle:

- marketplace search requests
- plugin detail rendering
- compatibility evaluation against the local environment
- package download to a temporary directory
- checksum and signature verification
- archive validation and safe extraction
- update notifications for installed plugins
- install and update orchestration
- admin-facing logging and error reporting

## Package Contract Expectations

The client should expect each approved release to expose:

- `unique_key`
- plugin version
- display name
- author identity
- supported Zen Cart versions
- supported PHP versions
- dependency declarations
- conflict declarations
- release notes
- package checksum
- signature metadata if signing is used

The extracted package must map cleanly into the established plugin structure:

```text
zc_plugins/<unique_key>/<version>/
  manifest.php
  ...
```

The archive validator should reject packages with:

- mismatched `unique_key`
- malformed versions
- missing required manifest files
- unexpected top-level structure
- path traversal attempts
- symlinks or unsafe filesystem entries

## Security Model

Security is the dominant constraint for the client implementation.

Minimum requirements:

- only trusted or curated plugins should be installable in v1
- package downloads must be integrity-checked
- archive extraction must be path-safe
- admin must receive explicit install confirmation before activation
- compatibility failures must block install

Recommended stronger requirements:

- digital signatures for release packages
- side-by-side version installs rather than in-place overwrite
- explicit trust state in the UI, such as `Verified` or `Curated`

## Compatibility And Dependency Handling

Before install or update, Plugin Manager should evaluate:

- Zen Cart version compatibility
- PHP version compatibility
- required plugins
- known conflicting plugins
- any required extensions or environment constraints declared by the plugin

Recommended first-release behavior:

- block install on hard incompatibilities
- warn on softer risks
- avoid a full automatic dependency resolver in v1

## Install Lifecycle

The marketplace install flow should be:

1. Admin selects a plugin and version.
2. Plugin Manager fetches metadata and evaluates compatibility.
3. Zen Cart downloads the package to a temporary directory.
4. Zen Cart verifies checksum and signature metadata.
5. Zen Cart validates archive layout and package identity.
6. Zen Cart extracts the package into the expected `zc_plugins` version directory.
7. Plugin Manager refreshes local plugin discovery.
8. Admin confirms install or enable through the existing lifecycle.

## Update Lifecycle

Plugin Manager should also support update checks for installed plugins.

Recommended update flow:

1. Collect installed plugin inventory.
2. Query the forum API for newer compatible versions.
3. Show available updates in Plugin Manager.
4. Run the same secure download and verification flow used for fresh installs.
5. Extract the new version alongside existing versions where possible.
6. Hand off to the normal plugin upgrade or install script flow.

Versioned side-by-side directories are preferable to overwriting existing plugin code in place.

## Rollback And Failure Handling

The install and update process must handle failure safely.

Failure cases include:

- download interruption
- checksum or signature mismatch
- invalid archive layout
- filesystem write failure
- installer script failure
- compatibility drift between lookup time and install time

The system should:

- clean temporary files on failure
- preserve the previous installed state during updates
- log actionable diagnostics to `logs/`
- avoid leaving Plugin Manager in a partially updated state

## Plugin Manager UX Changes

Recommended UI changes inside admin:

- keep `Installed` behavior for current local plugins
- add a `Browse` view for marketplace search
- add an `Updates` view for installed plugin updates
- add detail views or modals for version history, compatibility, and release notes
- show trust state clearly

Recommended install UX:

1. Search and select plugin.
2. Review compatibility and trust metadata.
3. Confirm download and install.
4. See progress and success or failure state.
5. Proceed into the normal install or enable action.

## Phased Delivery

### Milestone 1: Client Contract Alignment

Deliverables:

- align expected package metadata with the forum API
- define local error handling and install state model
- define Plugin Manager UI states and flows

### Milestone 2: Read-Only Browse Experience

Deliverables:

- Plugin Manager `Browse` UI
- plugin search and detail rendering
- compatibility display
- no package install yet

### Milestone 3: One-Click Install

Deliverables:

- temp download handling
- checksum and signature verification
- archive validation
- safe extraction into `zc_plugins`
- handoff into the current install lifecycle

### Milestone 4: Updates

Deliverables:

- installed-plugin inventory checks
- `Updates` UI
- secure update download and extraction flow
- improved failure handling and diagnostics

## Backlog By Epic

### Epic 1: Plugin Manager Browse Experience

- add marketplace settings
- add `Browse` tab
- add search, filtering, and detail views
- surface compatibility and trust metadata

### Epic 2: Secure Download And Install

- implement package download to temp storage
- implement checksum and signature verification
- implement archive validation
- implement safe extraction into `zc_plugins`
- refresh plugin discovery and hand off to install flow

### Epic 3: Compatibility And Dependencies

- evaluate Zen Cart and PHP version support
- detect required plugins and conflicts
- block on hard incompatibilities
- warn on softer risks

### Epic 4: Updates

- compare installed versions with marketplace versions
- surface update notifications
- install updates through the same secure workflow
- preserve previous working state where possible

### Epic 5: Rollback And Recovery

- track install and update progress states
- clean temp files on failure
- improve recovery messaging and logs
- define rollback or disable-and-recover behavior

### Epic 6: Testing

- unit tests for metadata parsing
- unit tests for compatibility evaluation
- unit tests for archive validation
- integration tests for browse and install flows
- integration tests for failure paths and updates

### Epic 7: Documentation

- admin usage guide for store owners
- technical guide for package verification and install flow
- security explanation for trust indicators and verification

## V1 Acceptance Criteria

- Admin can search a curated plugin catalog from Plugin Manager.
- Admin can inspect plugin details, compatibility, and trust state before install.
- Zen Cart can securely download a package to a temporary directory.
- Zen Cart can verify package integrity and authenticity.
- Zen Cart can safely extract a valid package into `zc_plugins`.
- The installed files remain compatible with the existing plugin lifecycle.
- Invalid or incompatible packages are blocked with clear admin feedback.
