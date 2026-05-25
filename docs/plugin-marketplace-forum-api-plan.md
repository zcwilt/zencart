# Plugin Marketplace Forum API Plan

## Status

Last updated: 2026-05-25

- This document is a planning proposal only.
- No forum-backed marketplace API is implemented yet.
- It assumes the forum or plugin library is the source of truth for available plugins and approved releases.

## Goal

Provide a public, structured API over forum-managed plugin records so Zen Cart `Plugin Manager` can:

- search approved plugins
- retrieve plugin details and version history
- check for updates
- retrieve package download metadata
- rely on a stable machine-readable source instead of forum HTML

## Non-Goals

This proposal does not assume:

- a public write API for plugin submission in the first release
- unaudited community publication in the first release
- direct Zen Cart knowledge of forum internals
- auth requirements for public catalog reads

## Forum As Source Of Truth

This plan assumes:

- plugin listings are published and moderated through the forum or plugin library
- release records originate from the forum-side workflow
- approval, delisting, and publication state are owned by the forum-side system
- the API is a structured interface over that forum-side data

The Zen Cart client should consume the API as the authoritative source for:

- what plugins exist
- which versions are approved and available
- what package file should be downloaded
- what compatibility and trust metadata should be shown in admin

## API Design Principles

The forum API should be:

- public for read operations
- unauthenticated for catalog search and plugin metadata
- rate-limited from day one
- cached aggressively
- versioned, for example `/api/v1/...`
- stable enough for Zen Cart core to depend on

Authentication is still appropriate for publisher or moderator actions, but not required for the public catalog endpoints used by Zen Cart stores.

## Forum API Responsibilities

The forum-backed API should handle:

- catalog indexing
- plugin metadata normalization
- search and filtering
- version history
- compatibility metadata distribution
- release artifact hosting or signed redirects
- publisher approval and moderation workflows
- checksum and signature publication
- release revocation or delisting

Plugin Manager should not scrape forum pages directly.

## Forum API Data Model

The forum-side implementation needs a normalized plugin record and release record even if the underlying forum storage is more flexible.

At minimum, the API should expose:

- plugin identifier and `unique_key`
- plugin display name
- author identity
- summary and long description
- plugin category or type
- current approved version
- all approved versions
- supported Zen Cart versions
- supported PHP versions
- dependencies and conflicts
- release notes
- package file URL
- checksum and signature metadata
- publication, approval, and delisting state

The API layer can derive this from forum or plugin-library records, but Zen Cart should not need to understand forum internals.

## Package And Release Metadata

The forum-side release record should provide enough metadata for the client to validate a package before extraction.

Each approved release should expose:

- `unique_key`
- plugin version
- display name
- author or vendor identity
- supported Zen Cart versions
- supported PHP versions
- dependency declarations
- conflict declarations
- release notes
- package checksum
- signature metadata if signing is adopted
- canonical package file location or redirect metadata

## Forum API

The first version of the forum-backed API can remain simple and read-heavy.

Suggested endpoints:

- `GET /api/v1/plugins`
- `GET /api/v1/plugins/{unique_key}`
- `GET /api/v1/plugins/{unique_key}/versions`
- `GET /api/v1/plugins/updates`
- `GET /api/v1/plugins/{unique_key}/download/{version}`

Expected response areas:

- plugin identity
- summary and description
- author metadata
- release metadata
- compatibility metadata
- trust indicators
- checksum or signature metadata
- download URL or signed redirect token

Publishing workflows should stay outside the Zen Cart client in the first release.

## Endpoint Notes

Recommended behavior by endpoint:

- `GET /api/v1/plugins`
  - search and filter approved plugins
  - support pagination
  - support filters such as Zen Cart version, category, and author
- `GET /api/v1/plugins/{unique_key}`
  - return canonical plugin detail plus current approved release
- `GET /api/v1/plugins/{unique_key}/versions`
  - return approved version history and compatibility metadata
- `GET /api/v1/plugins/updates`
  - accept installed plugin inventory and return newer compatible releases
- `GET /api/v1/plugins/{unique_key}/download/{version}`
  - return download metadata or a signed redirect for the package artifact

## Operational Requirements

Even without authentication, the public API should include:

- per-IP throttling
- stronger throttles on search and download-related endpoints
- cache headers suitable for CDN or reverse-proxy caching
- request logging for abuse detection
- ability to revoke or hide compromised releases quickly
- monitoring around error rates and traffic spikes

For v1, read-only public endpoints are sufficient. Publisher submission, moderation, and release approval can remain internal or authenticated forum-side workflows.

## Security Model

The public API may be unauthenticated, but trust still depends on release governance and package verification.

Minimum requirements:

- only approved or curated plugins are exposed through the API
- release metadata includes integrity data
- delisted or revoked releases stop appearing as installable
- the API can communicate trust state clearly

Recommended stronger requirements:

- digital signatures for package releases
- verified publisher identities
- explicit release-state transitions for approval, delisting, and revocation

## Phased Delivery

### Milestone 1: API Contract

Deliverables:

- normalized plugin and release schema
- public response format
- route design and versioning rules
- release-state model

### Milestone 2: Read-Only Catalog

Deliverables:

- search endpoint
- plugin detail endpoint
- version-history endpoint
- package download metadata endpoint

### Milestone 3: Update Support

Deliverables:

- update-check endpoint
- compatibility filtering for installed inventories
- improved caching and query efficiency

### Milestone 4: Moderation And Operations

Deliverables:

- approval and delisting workflow integration
- signature or checksum publication process
- abuse throttling and monitoring
- incident response for compromised packages

## Backlog By Epic

### Epic 1: Forum API Foundation

- choose the forum-side storage model for normalized plugin and release records
- define API versioning and route structure
- define public response schema and error shape
- define approval and delisting states

### Epic 2: Forum API Read Endpoints

- implement plugin search endpoint
- implement plugin detail endpoint
- implement version-history endpoint
- implement update-check endpoint
- implement download-metadata endpoint

### Epic 3: Moderation And Publishing Integration

- map forum publication workflow to approved API-visible releases
- store checksums and signature metadata
- support hiding, delisting, or revoking releases
- support release-note editing and compatibility metadata updates

### Epic 4: Forum API Operations

- add throttling and abuse protection
- add caching strategy
- add structured logging and monitoring
- add incident response path for compromised packages

### Epic 5: Testing

- schema and response-shape tests
- endpoint behavior tests
- pagination and filtering tests
- throttling and abuse-protection tests
- delisting and revocation behavior tests

### Epic 6: Documentation

- API reference for Zen Cart consumers
- moderation and release-approval guide
- package metadata publishing guide
- operational runbook for revocation and abuse response

## V1 Acceptance Criteria

- The forum exposes a public read-only API for approved plugins.
- Zen Cart can search and retrieve plugin detail without scraping forum HTML.
- The API can return approved version history and update metadata.
- The API can return package download metadata including integrity information.
- Throttling, caching, and release-state handling are in place.
- Delisted or revoked releases no longer appear as installable through the API.
