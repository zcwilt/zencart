# Plugin-Local Testing Plan

## Goal

Support tests that live with the plugin they validate under `zc_plugins/<PluginName>/<version>/tests`, while reusing Zen Cart's central `not_for_release/testFramework` base classes, helpers, runners, and CI grouping.

Plugin tests should be versioned with the plugin. The framework should remain centralized; plugins should own only their test files, fixtures, seeders, and optional test metadata.

## Authoring Convention

For a plugin stored at:

- `zc_plugins/<PluginName>/<version>/`

use this test layout:

- `tests/FeatureAdmin/*Test.php` for admin/in-process feature tests
- `tests/FeatureStore/*Test.php` for storefront/in-process feature tests
- `tests/Unit/*Test.php` for isolated unit tests
- `tests/Fixtures/` for optional plugin-local fixtures
- `tests/Seeders/` for optional plugin-local seeders
- `tests/bootstrap.php` for optional plugin-local bootstrap code
- `tests/plugin-test.php` for optional plugin test metadata

Plugin-local tests should extend the normal framework base classes:

- `Tests\Support\zcInProcessFeatureTestCaseAdmin`
- `Tests\Support\zcInProcessFeatureTestCaseStore`
- `Tests\Support\zcUnitTestCase`

Plugin-local tests that install, uninstall, enable, disable, or mutate plugin filesystem state should use:

```php
/**
 * @group serial
 * @group plugin-filesystem
 */
```

Read-only plugin-local tests can use:

```php
/**
 * @group parallel-candidate
 */
```

## MVP Implemented

The initial plugin-local testing MVP is now in place.

Implemented:

- plugin-local discovery through `not_for_release/testFramework/run-plugin-tests.sh`
- Composer scripts for `plugin-tests`, `plugin-tests-unit`, `plugin-tests-store`, `plugin-tests-admin`, and `plugin-tests-filesystem`
- path-based filtering with `--plugin`, `--suite`, `--require-group`, and normal PHPUnit `--filter`
- plugin-local admin feature tests included in the aggregate feature runner's serial `plugin-filesystem` bucket
- group reporting support for plugin-local admin and storefront feature tests
- `Tests\Support\Traits\PluginLocalTestConcerns`
- plugin root detection from a test file path
- plugin-local metadata loading from `tests/plugin-test.php`
- plugin-local bootstrap loading from `tests/bootstrap.php`
- helper support for installing the current plugin source into the test filesystem
- helper support for installing the current plugin through the plugin installer stack
- `zc_plugins/.gitignore` rules that allow plugin-local `tests/` directories to be tracked without unignoring full plugin source trees
- GDPR / DSAR plugin-local admin install/uninstall reference coverage
- GDPR / DSAR plugin-local storefront reference coverage

Current reference plugin:

- `zc_plugins/gdpr-dsar/v1.0.0`

Current reference tests:

- `zc_plugins/gdpr-dsar/v1.0.0/tests/FeatureAdmin/GdprDsarPluginInstallTest.php`
- `zc_plugins/gdpr-dsar/v1.0.0/tests/FeatureStore/GdprDsarStorefrontTest.php`

## Commands

Run all plugin-local tests:

```bash
composer plugin-tests
```

Dry-run plugin-local discovery:

```bash
composer plugin-tests -- --dry-run
```

Run only one plugin:

```bash
composer plugin-tests -- --plugin gdpr-dsar
```

Run one plugin and suite:

```bash
composer plugin-tests -- --plugin gdpr-dsar --suite FeatureAdmin
composer plugin-tests -- --plugin gdpr-dsar --suite FeatureStore
composer plugin-tests -- --plugin gdpr-dsar --suite Unit
```

Run only plugin filesystem mutation tests:

```bash
composer plugin-tests-filesystem -- --plugin gdpr-dsar
```

Run one class or method:

```bash
composer plugin-tests -- --plugin gdpr-dsar --filter GdprDsarPluginInstallTest
```

## Next Milestone: Real Plugin Coverage

The framework plumbing is usable. The next phase should focus on meaningful plugin behavior coverage.

Recommended order:

1. Expand GDPR / DSAR storefront tests around policy acceptance gating.
2. Add GDPR / DSAR request submission coverage and verify the request/audit rows.
3. Add GDPR / DSAR admin queue tests that use seeded request rows.
4. Add SLA due and overdue coverage.
5. Add export expiry coverage.
6. Add anonymization and forced logout/session invalidation coverage.
7. Add a small plugin-local unit test to prove the `tests/Unit` path with a real plugin.

## Next Milestone: Plugin-Local Data Support

The MVP supports explicit bootstrap loading, but seeders and fixtures are still manual.

Recommended order:

1. Add a plugin-local seeder convention under `tests/Seeders`.
2. Let `tests/bootstrap.php` explicitly register or require plugin-local seeders first.
3. Add helper methods for resolving plugin-local fixture paths.
4. Only add automatic seeder/fixture discovery after at least two plugins need the same behavior.

Seeder class names should avoid collisions with central seeders. Prefer plugin-specific namespaces.

## Later: Discovery and Metadata

Current filtering is path-based and intentionally simple.

Later improvements:

- use `tests/plugin-test.php` metadata for richer filtering
- support metadata fields such as `filesystem_mutation`, `serial`, `bootstrap`, `seeders`, and `fixtures`
- optionally expose plugin-local tests as PHPUnit XML suites if IDE or CI tooling needs that
- introduce a PHPUnit extension/listener or runner-generated bootstrap map if explicit helper bootstrapping becomes noisy

Do not add PHPUnit XML suites until there is a concrete need. Passing discovered file paths to PHPUnit is simpler and already works.

## Later: Isolation

Plugin filesystem mutation tests currently run serially. That is safe, but it limits parallelism.

Later improvements:

- install plugin source into worker-local plugin directories for isolated mutation tests
- make filesystem isolation metadata-driven
- allow read-only plugin tests to stay parallel while install/uninstall tests run isolated or serial

## Open Risks

- Host execution currently depends on the local PHP environment having the required PDO driver.
- DDEV execution needs a valid `.ddev/config.yaml`; without it, local DDEV test commands cannot run from this checkout.
- Storefront plugin-local tests need the plugin installed through the real installer stack before plugin-provided pages, language files, and tables are available.
- The original central plugin install tests still use framework-owned fixtures from `not_for_release/testFramework/Support/plugins`; do not move them until plugin-local coverage is proven with real plugins.

## What Not To Do

- Do not force each plugin to invent its own test runner.
- Do not duplicate the central test framework inside each plugin.
- Do not make `not_for_release/testFramework/Support/plugins` the long-term source of truth for real plugin tests.
- Do not migrate every existing plugin-related test at once.
