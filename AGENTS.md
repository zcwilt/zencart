Purpose
-------
Concise guidance for automated AI coding agents (and humans) to become productive in this Zen Cart v3.0 PHP codebase. Focus is on immediately actionable facts: where to look, how the app boots, test & dev commands, conventions, and integration points.

Quick orientation (high-value entry points)
----------------------------------------
- Root storefront entry: `index.php` (loads `includes/application_top.php`, uses PageLoader to pull page modules).
- Admin entry: `admin/index.php` (uses `includes/application_bootstrap.php` then may include admin pages or plugin admin pages).
- Central bootstrap/config: `includes/configure.php`, `includes/application_top.php`, `includes/application_bottom.php`.
- Core includes/classes: `includes/classes/` and module files in `includes/modules/`.
- Plugins: `zc_plugins/` (plugins are versioned directories with catalog/admin subfolders).
- Autoload and vendor: `composer.json`, `vendor/` and `includes/psr4Autoload.php`.
- Tests & test bootstrap: `phpunit.xml` and `not_for_release/testFramework/`.

Big-picture architecture
------------------------
- Procedural, page-per-file entrypoints. Each visible page is a thin entry that includes `application_top.php`, then pulls per-page header PHP modules from `includes/modules/pages/PAGE_NAME/` and template fragments.
- Bootstrapping Init System: `includes/application_top.php` constructs an InitSystem that reads autoloader configurations from `includes/auto_loaders/` and then runs them from `includes/init_includes/` and plugin-provided loaders. 
- Plugin namespaces are mapped into PSR-4 prefixes during bootstrap.
- Plugin model: `zc_plugins/<unique_key>/<version>/catalog|admin/...` — installer/PluginManager registers installed plugins and the FileSystem helper loads files from plugin directories. Plugins can supply `extra_configures`, `extra_datafiles`, `classes`, and `pages`.
- Templates: Template selection is handled by the `$template` object and `includes/templates/*` paths; template-specific overrides live under `includes/templates/TEMPLATE/`.

Key developer workflows (commands & examples)
-------------------------------------------
- Production use does not depend on composer autoloading, but for tests, composer manages test suite dependencies.
- Install dependencies: composer install
  - Example: composer install
- Run tests (uses composer scripts)
  - Composer shortcuts defined in `composer.json`:
    - composer run-script unit-tests
    - composer run-script feature-tests
  - PHPUnit bootstrap uses `vendor/autoload.php` and phpunit.xml sets APP_ENV=testing
- Quick local smoke test (builtin PHP server)
  - Example (from project root): php -S 127.0.0.1:8000 -t .
  - Note: The app expects `includes/configure.php` to exist; use `includes/dist-configure.php` as template and adjust `DIR_FS_CATALOG`. Same for `/admin/includes/configure.php`.
- DB and install
  - Copy `includes/dist-configure.php` and `admin/includes/dist-configure.php` to `configure.php` (in those same folders) and make the files writable. Fill DB constants (see `includes/configure.php` example in repo).
  - Make `cache/` and `logs/` writable.

Project-specific conventions and patterns
---------------------------------------
- Entrypoints are procedural files that require `application_top.php` and later `application_bottom.php` (see `index.php` flow comments).
- File/constant mapping: many filenames are registered via `includes/init_includes/init_file_db_names.php` (calling `/filenames.php`) and plugin `filenames.php` — search for `FILENAME_` constants.
- Autoloading: PSR-4 for core application and plugins. During runtime, `Aura\\Autoload` plus `includes/psr4Autoload.php` register autoload prefixes (see `application_top.php`). (For test suite, `composer.json` uses classmap for `includes/classes` and `includes/modules`). 
- Plugin registration: PluginManager + PluginControlRepository provide installed plugin list; FileSystem helper loads plugin-supplied files. Plugins have `unique_key` and `version` used in paths: `zc_plugins/<unique_key>/<version>/...`, and a `manifest.json` file which provides descriptions that get registered in the database.
- Security & input sanitation: `application_top.php` includes early request-sanitizing logic (rejects suspicious query strings, parameter pollution, and crawler `buy_now` attempts). Automated changes to routing/inputs should preserve these checks. Call `zen_output_string_protected()` on any output that includes user input, for XSS protection. The admin-side applies aggressive input-sanitization rules, but new fields that require relaxed sanitization will need proper whitelisting: see https://docs.zen-cart.com/dev/code/admin_sanitization/.
- These same patterns apply to the admin side. 
- Template overrides: The non-admin side supports template-specific overrides for modules and classes. For example, if the active template is `my_template`, the system will look for files in `includes/templates/my_template/` before falling back to the template_default paths. This allows for customization without modifying core files.
- `index.php` flow: includes application_top.php, loops over `header_php` files from PageLoader->listModulePagesFiles('header_php', '.php'), then loads `html_header.php`, `main_template_vars.php`, `tpl_main_page.php`.

Integration points and external dependencies
-------------------------------------------
- Plugins: `zc_plugins/` is the place for third-party extensions and versioned code; new automations should inspect existing plugins for common structure. For very deep code inspection, reference `PluginManager` and `FileSystem` usage in `includes/application_top.php`.
- Composer-managed dev deps: phpunit, symfony components, guzzle. No production PHP libraries are required in composer.json aside from PHP extensions. (The app uses its own autoloading for core classes and modules, and plugins manage their own dependencies if needed.) 
- (There are some 3rd-party libraries included directly in `includes/classes/vendors/` that are not managed by composer; these are bundled directly to avoid end-users needing to use composer.)
- Payment/webhook listeners at repo root: The following PayPal-related listeners are processor-specific: `ipn_main_handler.php`, `ppr_listener.php`, `ppr_webhook.php`.

Plugin development (quick reference)
-----------------------------------
Short Summary:
- Directory layout: `zc_plugins/<unique_key>/<version>/catalog/...` and `zc_plugins/<unique_key>/<version>/admin/...`.
- Minimal files: `manifest.json` at the plugin root (describes unique_key/version and human metadata) and the plugin-provided `catalog/includes/` or `admin/includes/` folders for `classes`, `extra_configures`, `extra_datafiles`, and `modules/pages/`.
- Discovery: `includes/application_top.php` uses `PluginManager` + `PluginControlRepository` to produce `$installedPlugins`; `FileSystem->loadFilesFromPluginsDirectory()` is used to pull in `extra_configures`, `extra_datafiles`, `filenames.php`, and database files. `admin/index.php` will call `FileSystem->findPluginAdminPage()` to dispatch plugin admin pages.
- PSR-4: To expose plugin classes via the app autoloader, runtime PSR-4 prefixes are added in `application_top.php` using `$psr4Autoloader->addPrefix()` for `Zencart\Plugins\Catalog\<UniqueKey>` and `Zencart\Plugins\Admin\<UniqueKey>`.
- Installer Scripts: To run installation scripts, create a `zc_plugins/<unique_key>/<version>/install/` folder and build your installer instructions there (see dev docs).

Minimal example manifest.json
```json
{
  "unique_key": "myplugin",
  "version": "1.0.0",
  "name": "My Plugin",
  "description": "Short description",
  "author": "You"
}
```

Minimal plugin file structure layout (example)
```
- zc_plugins/myplugin/1.0.0/manifest.json
- zc_plugins/myplugin/1.0.0/filenames.php
- zc_plugins/myplugin/1.0.0/catalog/includes/classes/MyClass.php
- zc_plugins/myplugin/1.0.0/catalog/includes/extra_configures/myconf.php
- zc_plugins/myplugin/1.0.0/catalog/includes/modules/pages/my_page/header_php.php
- zc_plugins/myplugin/1.0.0/admin/includes/classes/AdminHelper.php
```
Quick tips for agents that create plugins
- Add any filename constants via a plugin `filenames.php` if you need new FILENAME_* constants — `FileSystem` loader will include plugin `filenames.php` files during bootstrap.
- If you add PSR-4 namespaced classes, follow the `Zencart\Plugins\Catalog\<UniqueKey>` namespace root so runtime autoloader registration in `application_top.php` points to your plugin `catalog/includes/classes/` path.
- Test by enabling the plugin via admin `Plugin Manager` (or insert a `plugin_control` DB record in tests), then exercise plugin pages (storefront/admin) and run relevant PHPUnit feature tests.

Official docs
-------------
Reference the official plugin developer docs when writing plugins: https://docs.zen-cart.com/dev/plugins/

Enablement & lifecycle (practical examples)
------------------------------------------
Plugins are discovered only when present in the `plugin_control` (and optional `plugin_control_versions`) tables. Two common ways to enable a plugin for local development/tests:

1) Admin UI: install/enable the plugin using the admin `Plugin Manager` UI (preferred for end-to-end testing).

2) Direct DB insert (this is ONLY for tests or CI, never in application code): example SQL to mark a plugin installed and available to the bootstrap loader:

```sql
INSERT INTO plugin_control
  (unique_key, name, description, type, managed, status, author, version, zc_versions, infs)
VALUES
  ('myplugin', 'My Plugin', 'Short description', 'free', 0, 1, 'You', '1.0.0', '*', 0);

INSERT INTO plugin_control_versions
  (unique_key, version, author, zc_versions, infs)
VALUES
  ('myplugin', '1.0.0', 'You', '*', 0);
```

After the DB entry exists, `application_top.php` will discover the plugin and the `FileSystem` loader will include plugin `extra_configures`, `filenames.php`, and other files as part of bootstrap.

PSR-4 mapping example (runtime)
--------------------------------
At runtime `application_top.php` adds PSR-4 prefixes for each installed plugin. For example, a plugin with `unique_key` = `myplugin` and `version` = `1.0.0` will be registered like:

Namespace: `Zencart\Plugins\Catalog\Myplugin` -> Path: `zc_plugins/myplugin/1.0.0/catalog/includes/classes/`

So a class `Zencart\Plugins\Catalog\Myplugin\Utils\Helper` should be placed in:
`zc_plugins/myplugin/1.0.0/catalog/includes/classes/Utils/Helper.php`

filenames.php note
------------------
If your plugin introduces new page entrypoints or constants, add a `filenames.php` under the plugin root (older plugins might use `catalog/includes/`) that defines `FILENAME_*` constants. The `FileSystem` loader will include plugin `filenames.php` during bootstrap so your new constants are available at runtime.



Where to find tests & how the test bootstrap works
--------------------------------------------------
- PHPUnit configuration: `phpunit.xml` uses `vendor/autoload.php` and sets APP_ENV=testing and reduced bcrypt rounds.
- Tests live in `not_for_release/testFramework/` grouped into Unit, FeatureStore, FeatureAdmin. The test autoloading is configured in `composer.json` under `autoload-dev`.
- There is a test-support bootstrap at `not_for_release/testFramework/Support/application_testing.php` that will be loaded if present by `application_top.php`.
- Use the `composer` shortcuts defined in `composer.json` to run specific test suites:
  - composer run-script unit-tests
  - composer run-script feature-tests
- Developer documentation for tests: https://docs.zen-cart.com/dev/testframework/testing/

Actionable examples for agents (copy-paste)
-----------------------------------------
- Install deps and run unit tests:
  - composer install
  - composer run-script unit-tests
  - composer run-script feature-tests
- Run feature tests for only the storefront:
    - composer run-script feature-tests-store
- Run feature tests for only the Admin side:
    - composer run-script feature-tests-admin
- Quick bootstrap for ad-hoc PHP scripts/tests (but the app doesn't have any intended CLI entrypoints.):
  - <?php
    require 'includes/application_top.php';
    // ... run logic that depends on DB and bootstrapped services
    require DIR_WS_INCLUDES . 'application_bottom.php';

Quick pointers for common tasks
------------------------------
- Adding a new page/module: create files under `includes/modules/pages/<page_name>/` (`header_php.php`, optional `main_template_vars.php`, optional jscript-related files) and register any new filename constants via `filenames.php` pattern.
- Adding plugin code: place under `zc_plugins/<unique_key>/<version>/` with relevant `/catalog` and/or `/admin` folders, and ensure PluginControl entries reflect installation; use PluginManager FileSystem helpers to mirror existing patterns.
- Debugging: enable `STRICT_ERROR_REPORTING` which turns `display_errors` on, or `DEBUG_AUTOLOAD` (for troubleshooting autoload config array load-order) in a local `includes/local/configure.php`. Logs are in `logs/`.

Files to inspect next (for humans and automated extractors)
------------------------------------------------------
```
- includes/configure.php
- includes/application_top.php (expect to never edit this file though)
- includes/defined_paths.php
- includes/init_includes/
- includes/modules/pages/
- zc_plugins/
- admin/includes/configure.php
- admin/includes/application_top.php (expect to never edit this file)
- composer.json and phpunit.xml
- not_for_release/testFramework/Support/application_testing.php
```

Contacts & references
---------------------
- Developer docs: https://docs.zen-cart.com/dev/
- Project README: `README.md`

End of AGENTS.md

