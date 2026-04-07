# Plugin-Local Testing Plan

## Goal

Support plugin tests that live inside the plugin itself under `zc_plugins`, while still reusing Zen Cart's existing `not_for_release/testFramework` support code, runners, and base test cases.

## Current State

The test framework already has meaningful plugin-testing support, but it is not yet plugin-local.

### Achieved

- plugin feature tests exist and run through the shared framework
  - example: `not_for_release/testFramework/FeatureAdmin/PluginTests/BasicPluginInstallTest.php`
- plugin filesystem tests have their own runner bucket
  - `run-parallel-feature-tests.sh` dispatches a serial `plugin-filesystem` admin bucket
- plugin tests can already reuse the shared base classes
  - `Tests\Support\zcInProcessFeatureTestCaseAdmin`
  - `Tests\Support\zcInProcessFeatureTestCaseStore`
  - `Tests\Support\zcUnitTestCase`
- worker-scoped plugin install paths exist in runtime config
  - `zc_test_config_plugin_directory(...)` supports worker-specific plugin directories
- the framework can install and remove plugin test fixtures in those worker-scoped paths
  - via `Tests\Support\TestFrameworkFilesystem`
- runner behavior and plugin buckets already have unit-test coverage
  - see `not_for_release/testFramework/Unit/testsSundry/TestFrameworkRunnersTest.php`

### Not Yet Achieved

- test discovery does not yet scan `zc_plugins/*/*/tests`
- plugin tests are not yet versioned beside the plugin as the primary source of truth
- plugin installation still copies from framework-owned fixtures under:
  - `not_for_release/testFramework/Support/plugins/<PluginName>`
- there is no plugin-local `tests/bootstrap.php` loading path
- there is no plugin-local fixture or seeder discovery convention
- there is no plugin-local metadata file such as `tests/plugin-test.php`
- there is no filtering model aimed at “all plugin-local tests” or “one plugin only” under `zc_plugins`
- the GDPR / DSAR plugin has not yet been implemented as the first plugin-local reference example

## Core Direction

Plugin tests should be versioned with the plugin they validate.

Instead of treating `not_for_release/testFramework/Support/plugins/...` and `FeatureAdmin/PluginTests/...` as the long-term primary home for plugin tests, the framework should discover and run tests that live directly inside plugin directories.

## Target Plugin Test Layout

For a plugin stored at:

- `zc_plugins/<PluginName>/<version>/`

use a test structure like:

- `zc_plugins/<PluginName>/<version>/tests/FeatureAdmin`
- `zc_plugins/<PluginName>/<version>/tests/FeatureStore`
- `zc_plugins/<PluginName>/<version>/tests/Unit`
- `zc_plugins/<PluginName>/<version>/tests/Fixtures` (optional)
- `zc_plugins/<PluginName>/<version>/tests/Seeders` (optional)
- `zc_plugins/<PluginName>/<version>/tests/bootstrap.php` (optional)

This keeps the tests tied to the plugin version they belong to.

## Gap Analysis

### 1. Test Discovery

Needed:

- discover plugin-local tests under paths such as:
  - `zc_plugins/*/*/tests/FeatureAdmin/*Test.php`
  - `zc_plugins/*/*/tests/FeatureStore/*Test.php`
  - `zc_plugins/*/*/tests/Unit/*Test.php`

Current state:

- runners still discover core tests under `not_for_release/testFramework`
- plugin-oriented feature coverage currently lives under:
  - `not_for_release/testFramework/FeatureAdmin/PluginTests`

### 2. Shared Base Classes

Needed:

- plugin-local tests should extend the same shared framework base classes

Current state:

- this is already in place at the framework level
- the missing piece is wiring plugin-local discovery and bootstrap around those existing base classes

### 3. Plugin-Local Bootstrap Support

Needed:

- optional per-plugin assets such as:
  - `tests/bootstrap.php`
  - `tests/Fixtures/`
  - `tests/Seeders/`

Current state:

- custom seeding exists in the framework
- plugin-local bootstrap, fixture, and seeder loading does not yet exist

### 4. Installation Strategy

Needed:

- when testing a plugin, use the plugin's real source from:
  - `zc_plugins/<PluginName>/<version>`

Current state:

- worker-local destination paths already exist
- source plugin files still come from framework-owned fixtures under:
  - `not_for_release/testFramework/Support/plugins/<PluginName>`

### 5. Plugin Test Metadata

Needed:

- optional metadata in:
  - `tests/plugin-test.php`
  - or a small extension to `manifest.php`

Useful metadata could include:

- plugin name and version under test
- whether the tests mutate plugin filesystem state
- whether tests must run serially
- custom bootstrap path
- custom seeder path

Current state:

- not yet implemented

## Runner Behavior Target

### Filtering

The runners should eventually support filters for:

- all plugin-local tests
- one plugin only
- one plugin plus one test layer, such as admin/store/unit

Examples of intended use:

- run all plugin tests
- run only GDPR plugin tests
- run only plugin unit tests

### Parallelism and Isolation

Tests that install, uninstall, enable, disable, or otherwise modify plugin files should be tagged so they run serially or in isolated worker-local plugin directories.

Current state:

- the framework already has a serial `plugin-filesystem` feature bucket
- worker-local plugin destination paths already exist
- this isolation model should be reused for plugin-local discovery, not redesigned from scratch

## Suggested First Reference Implementation

Use the GDPR / DSAR plugin as the first plugin-local example.

### Initial GDPR test coverage

- plugin install and uninstall
- storefront DSAR page access
- privacy-policy acceptance gating
- request submission
- admin queue rendering
- SLA due and overdue behavior
- anonymization and forced logout/session invalidation
- export expiry handling

## Recommended Implementation Order

### Completed Foundations

1. Keep framework support centralized under `not_for_release/testFramework`.
2. Reuse the existing feature and unit base test cases for plugin tests.
3. Support worker-scoped plugin install paths and a plugin-filesystem runner bucket.

### Remaining Work

1. Define and document the plugin-local `tests/` directory convention as the primary path.
2. Update the test runners to discover tests under `zc_plugins/*/*/tests`.
3. Teach plugin install helpers to source plugin files from `zc_plugins/<PluginName>/<version>` instead of framework fixture copies.
4. Add support for plugin-local bootstrap, fixtures, and seeders.
5. Add filtering and runner options for plugin-local test execution by plugin and layer.
6. Add optional plugin-local metadata for serial/isolation/bootstrap needs.
7. Implement GDPR / DSAR as the first end-to-end plugin-local example.
8. Retire or demote `Support/plugins/...` and `FeatureAdmin/PluginTests/...` from primary-source status once plugin-local coverage is in place.

## What Not To Do

- Do not force each plugin to invent its own separate test runner.
- Do not duplicate the whole framework inside each plugin.
- Do not keep `Support/plugins/...` as the long-term primary source of truth if the goal is plugin-local ownership.
- Do not introduce a second isolation model when worker-scoped plugin directories already exist.

## Summary

The direction is still sound, but the repo is only partway there.

What exists now:

- centralized framework support
- reusable base classes
- plugin-filesystem feature testing
- worker-local plugin destination paths

What still needs to happen:

- move test ownership to `zc_plugins/<PluginName>/<version>/tests`
- discover and run those tests directly
- load plugin-local bootstrap/fixtures/seeders
- use the real plugin source tree as the test source

That is the work required to turn today's plugin-testing support into true plugin-local testing.
