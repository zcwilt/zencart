# Zen AI Assist Plan

This file is the canonical Zen AI Assist plan.

The older root-level copy at `docs/zen-ai-assist-docs-plan.md` should not be used as the source of truth.

## Product Shape

Zen AI Assist should behave more like a Zen Cart-specific Laravel Boost analogue than a generic docs index.

The intended product layers are:

1. MCP-first tools for agents and editors
2. structured Zen Cart workflow skills
3. docs-plus-code retrieval grounded in official docs and current repo behavior
4. CLI commands as maintenance and fallback surfaces

Docs ingestion, JSON catalogs, and console commands are supporting infrastructure. The product itself is the guided Zen Cart development layer.

## Current State

Zen AI Assist now exists as an encapsulated plugin under:

- `zc_plugins/zen-ai-assist/v1.0.0/`

The current implementation includes:

- local docs fetch and cache
- heading-aware docs chunking
- repo catalog generation
- docs search, ask, and compare commands
- plugin doctor and plugin scaffold commands
- stdio MCP server
- bundled guidance topics
- structured skills loaded from `resources/skills/catalog.json`
- MCP skill tools
- a skill-aware ask flow that can optionally attach plugin runtime diagnostics
- lightweight query classification, richer repo relationship metadata, and more opinionated next-step guidance
- shared cache/catalog storage at `cache/zen-ai-assist/` so plugin version upgrades do not normally discard generated data
- multi-source guidance and skill discovery for bundled content, core extension content, and installed plugins

## Current MCP Surface

Implemented MCP tools:

- `search_docs`
- `search_repo`
- `compare_docs_to_code`
- `inspect_plugin_manifest`
- `inspect_plugin_installer`
- `inspect_bootstrap_loaders`
- `lookup_filename_constant`
- `list_page_modules`
- `read_recent_logs`
- `list_installed_plugins`
- `list_guidance_topics`
- `read_guidance_topic`
- `list_skill_topics`
- `read_skill_topic`
- `list_skills`
- `get_skill`
- `match_skill_for_task`
- `validate_work_against_skill`
- `ask_with_skill_context`
- `plugin_doctor`

The important newer behavior is:

- `ask_with_skill_context` can match a workflow skill for a task
- when the matched skill is plugin-oriented and `plugin_root` is supplied, the answer automatically attaches:
  - `plugin_doctor` output
  - installed-plugin runtime state from the doctor result

That moves Zen AI Assist closer to a guided workflow layer instead of a raw search surface.

## Skill Model

Skills are first-class records, not just loose Markdown notes.

For Zen AI Assist, any plugin-oriented skill should be read as referring to encapsulated plugins under `zc_plugins/<unique_key>/<version>/`. Zen AI Assist should not generalize those skills to older dropped-in plugin patterns.

The current model uses:

- `resources/skills/catalog.json` for structured metadata
- `resources/skills/*.md` for human-readable workflow content

Each skill can carry:

- `id`
- `title`
- `summary`
- `intent`
- `tags`
- `when_to_use`
- `required_context`
- `source_refs`
- `workflow_steps`
- `validation_steps`
- `anti_patterns`
- `expected_outputs`
- `validation_rules`
- `content_file`

Current bundled skills:

- `plugin-scaffold`
- `admin-page`
- `create-storefront-page`
- `build-scripted-installer`
- `wire-language-files`
- `add-observer-hook`
- `plugin-doctor`
- `docs-compare`
- `create-core-unit-test`
- `create-core-feature-test`
- `create-plugin-test`
- `debug-test-failure`

## Architecture Direction

Zen AI Assist should continue to prioritize:

- official Zen Cart docs for intended conventions
- local repo code for actual runtime behavior
- explicit mismatch reporting when docs and code disagree
- Zen Cart-specific workflows over generic PHP advice

The MCP server should remain the main user-facing interface. CLI commands should stay available, but the common agent workflow should not depend on the user manually composing CLI commands.

## What Is Done

Completed foundation work:

- encapsulated plugin layout
- console command registration
- docs fetch/cache pipeline
- docs and repo catalogs
- MCP server transport and tool listing
- manifest and installer inspection
- runtime inspection helpers for bootstrap, filenames, page modules, logs, and installed plugins
- plugin doctor
- structured skills and skill validation
- skill matching
- skill-aware ask flow
- runtime-state classification for installed-plugin inspection, including bootstrap and DB failure modes
- plugin structure checks for page wiring, language files, templates, menu-definition presence, and observer naming
- repo relationship metadata for manifests, installers, page modules, admin entrypoints, and test assets
- opinionated answer shaping with workflow hints, references, and recommended next steps
- file-based content registration for guidance and skills from core and installed plugins, with source metadata

## What Is Still Open

### 1. Runtime Inspection Depth

Still needed:

- deeper DB-aware classification when a full store context is available
- broader reuse of existing runtime-state metadata across MCP tools that depend on live context
- more consistent runtime-state payloads between `list_installed_plugins`, `plugin_doctor`, and skill-aware answer flows
- clearer operator guidance for remediation when runtime inspection is degraded

Primary files:

- `catalog/includes/classes/ZenAiAssistRuntimeInspector.php`
- `catalog/includes/classes/ZenAiAssistDoctorService.php`
- `tests/Unit/ZenAiAssistDoctorAndSkillsTest.php`

### 2. Semantic Doctor Checks

Still needed:

- inspect language-file contents more semantically, not just for readable PHP includes
- validate admin menu definitions against actual admin entrypoints and installer/menu wiring expectations
- validate observer and autoloader naming, placement, and likely runtime intent more deeply
- inspect template override and page wiring consistency beyond baseline file presence

Primary files:

- `catalog/includes/classes/ZenAiAssistDoctorService.php`
- `catalog/includes/classes/ZenAiAssistRuntimeInspector.php`
- plugin fixture tests under `tests/`

### 3. Retrieval Quality

Still needed:

- stronger ranking for plugin keys, page names, constants, and version hints
- better mismatch/confidence messaging
- better blending between docs evidence, repo evidence, runtime context, and skill context
- more deterministic prioritization when multiple near-matches exist across docs and repo records

Primary files:

- `catalog/includes/classes/ZenAiAssistSearchService.php`
- `catalog/includes/classes/ZenAiAssistComparisonService.php`
- `catalog/includes/classes/ZenAiAssistAnswerService.php`

### 4. Repo Relationship Semantics

Still needed:

- richer metadata for templates, language files, observers, bootstrap inputs, and tests
- stronger relationships between manifests, installers, pages, templates, language files, observers, and test assets
- more explicit runtime hints in repo catalog records

Primary files:

- `catalog/includes/classes/ZenAiAssistRepoCatalogBuilder.php`
- `catalog/includes/classes/ZenAiAssistRuntimeInspector.php`

### 5. Higher-Level Skill Composition

Still needed:

- richer composition between matched skills and runtime tools beyond the current plugin-oriented attach behavior
- more opinionated workflow responses that turn skill metadata into more executable next steps
- wider skill coverage for common Zen Cart tasks
- clearer precedence and collision handling for duplicate skill ids or guidance topic slugs contributed by multiple sources
- stronger testing workflows covering both:
  - core unit/storefront/admin tests under `not_for_release/testFramework/`
  - encapsulated plugin-local tests under `zc_plugins/<PluginName>/<version>/tests/`

Primary files:

- `catalog/includes/classes/ZenAiAssistAnswerService.php`
- `catalog/includes/classes/ZenAiAssistSkillService.php`
- `catalog/includes/classes/ZenAiAssistMcpServer.php`
- `resources/skills/catalog.json`

## Recommended Order

The best remaining order is:

1. reuse and normalize runtime-state reporting across the MCP surface
2. deepen semantic doctor checks
3. improve repo relationship metadata and runtime hints
4. improve docs/code/runtime/skill retrieval quality
5. keep expanding higher-level skill composition and test guidance

## Guardrails

- prefer official Zen Cart docs over forum advice for baseline guidance
- prefer current local code over docs when runtime behavior disagrees
- keep source URLs attached to docs evidence
- keep the local-first, file-based architecture unless a later phase proves it insufficient
- keep Zen AI Assist centered on Zen Cart workflows, not generic framework-agnostic answers
