# Replace `configuration.use_function`/`set_function` eval() with a registry (BC-preserving)

## Context

`admin/configuration.php`, `admin/modules.php`, and `admin/product_types.php` are core-owned files
that render admin config-field edit widgets by string-concatenating the DB-stored `set_function`
column (a *partial* PHP function call, e.g. `zen_cfg_select_option(['true','false'],`, note the
trailing comma) with the
posted-back value and `eval()`-ing the result (`admin/configuration.php:249-267`). `use_function`
does the same for read-only display formatting, but via a real function call (`zen_call_function()`,
`admin/includes/functions/general.php:454-461`) rather than eval — it's already safer. `val_function`
(validation) is already a JSON-structured, non-eval'd scheme dispatched through `filter_var()`
(`admin/includes/functions/configuration_checks.php:19-65`) — proof this pattern already works
elsewhere in this codebase, and out of scope here.

**This is primarily a core code-quality problem, not a plugin-extensibility one.** Core ships and
maintains ~359 of its own `configuration` rows using `set_function` and ~178 using `use_function`
(`zc_install/sql/install/mysql_zencart.sql`), plus ~20 core module files (`includes/modules/payment/*`,
`shipping/*`, `order_total/*`) that build these same eval'd strings at install time. Every admin page
that renders a config group runs this eval() machinery against core's own stringly-typed data — no
interface, no static analysis, no IDE support, and an easy way to get the string-concatenation wrong
(`admin/modules.php`'s quoting/escaping already differs subtly from `admin/configuration.php`'s).
Fixing that — giving core's own ~24 `zen_cfg_*`/`zen_get_*` renderer functions a typed contract
instead of a raw evaluable string — is the goal. That the same interface is then available for
plugin authors to implement is a welcome side effect, not the driver.

The one hard constraint this must respect: core cannot break the ~359/~178 rows it already ships, nor
any third-party plugin installer that writes `set_function`/`use_function` directly via
`ScriptedInstallHelpers::addConfigurationKey()`/`updateConfigurationKey()`
(`includes/classes/PluginSupport/ScriptedInstallHelpers.php:45-127`) — the single chokepoint nearly
all plugin-authored `configuration` rows flow through. That constraint is what makes this a phased,
additive migration of core's own rendering code rather than a straight rewrite.

This is a **multi-PR, multi-release effort** (current dev version: v3.0.0, confirmed in
`includes/version.php:18-19`), not a single change. Every phase below is independently shippable and
additive-only where possible — no row is forced onto the new path, so a bad phase is reverted with a
normal code revert rather than a runtime toggle (this codebase has no precedent for feature-flagging
core rendering paths, and this plan doesn't introduce one).

## Overview

In place of the raw, eval'd PHP strings, config rows can carry a `renderer` column holding a small
JSON payload (`{"renderer": "zen_cfg_select_option", "params": {"options": ["true","false"]}}`) that
names a registered PHP class — implementing a new `ConfigFieldRendererInterface` (and, for read-only
display, `ConfigFieldFormatterInterface`) — instead of a partial function call to be spliced together
and executed. A `ConfigFieldRegistry` resolves that name to the class instance and calls its typed
`render()`/`format()` method with the value and parameters; core ships adapter classes for all of its
existing `zen_cfg_*`/`zen_get_*` helpers so behavior is unchanged, just no longer produced by
`eval()`. Rows without a `renderer` value keep working exactly as they do today via the untouched
legacy `set_function`/`use_function` path, so the switch-over is opt-in and lossless at every step.

## Design

**Central insight**: `set_function` values in real seed data aren't just a function name — they're a
partial call carrying baked-in literal arguments (e.g. the options list for a dropdown). So the new
renderer contract must carry a `params` array, not just a lookup key — mirroring how `val_function`'s
JSON already carries `options`.

**New interfaces** (`includes/classes/ConfigField/`, namespace `Zencart\ConfigField`, registered in
`includes/psr4Autoload.php` alongside the existing `Zencart\ViewBuilders`/`Zencart\Filters` prefixes):

```php
interface ConfigFieldRendererInterface {
    public function render(string $value, string $fieldName, array $params = []): string; // replaces set_function
}
interface ConfigFieldFormatterInterface {
    public function format(string $value, array $params = []): string; // replaces use_function
}
interface SensitiveConfigFieldInterface {
    public function isSensitive(): bool; // advisory, OR'd with zcObserverLogEventListener::isSensitiveFieldName()
}
```

**Adapter classes, not closures**: one thin adapter class per existing `zen_cfg_*`/`zen_get_*` helper
that core itself already ships (~17 renderers + ~8 formatters covers the full closed set found in
`admin/includes/functions/general.php` and a few `includes/functions/functions_*.php` helpers — this
is core wrapping its own rendering functions in a typed contract, not building something new for
third parties). As a side confirmation of coverage, the same closed set also accounts for 100% of
observed real-world plugin usage, including the `Class->method` case like `currencies->format`. Each
adapter is pure delegation to the existing global function — **the legacy functions are not removed
or duplicated**, so core rendering behavior stays byte-identical. Adapters are individually
testable/documentable and can implement `SensitiveConfigFieldInterface` where a closure couldn't.

```php
// includes/classes/ConfigField/Renderers/SelectOptionRenderer.php
class SelectOptionRenderer implements ConfigFieldRendererInterface {
    public function render(string $value, string $fieldName, array $params = []): string {
        return zen_cfg_select_option($params['options'] ?? [], $value, $fieldName);
    }
}
```

**`ConfigFieldRegistry`** (`includes/classes/ConfigField/ConfigFieldRegistry.php`): a plain
array-keyed strategy registry (register/resolve/has for both renderers and formatters) — modeled on
`Zencart\Filters\FilterFactory` (existing factory-by-name precedent), not on the observer/notifier
pub-sub system (wrong shape: need exactly one handler per key, not broadcast) and not on the
payment/shipping `ModuleFinder` filesystem-scan pattern (wrong shape: closed key space, no need to
scan disk). One instance (`$zcConfigFieldRegistry`) built in `admin/includes/application_top.php`,
populated by `Zencart\ConfigField\CoreRegistryBootstrap` registering all core adapters under keys
equal to the legacy function name they wrap (`'zen_cfg_select_option'`, `'zen_get_country_name'`, etc.
— reusing legacy names as registry keys keeps the seed-data migration in Phase 4 mechanical).

**New DB column** `configuration.renderer` / `product_type_layout.renderer` (TEXT, nullable) — added
via `zc_install/sql/updates/mysql_upgrade_zencart_300.sql` (already exists as this cycle's upgrade
script) and the schema block in `zc_install/sql/install/mysql_zencart.sql:348-349`. JSON payload:
```json
{"renderer": "zen_cfg_select_option", "formatter": "zen_get_order_status_name", "params": {"options": ["true","false"]}}
```
`formatter` is optional and only present on rows that need both edit + read-only display, same as
today's rows that set both `set_function` and `use_function`.

**Resolution order** at all 3 call sites (identical logic, factored into one shared helper —
`Zencart\ConfigField\ConfigFieldRowResolver` — used by all three rather than duplicated):
1. If `renderer` column is non-empty valid JSON: `try { $registry->render(...) } catch (\Throwable) { fall through }`.
2. Else if legacy `set_function` non-empty: run **today's exact eval() code, unchanged**.
3. Else: today's plain `<input type="text">` fallback, unchanged.

Same 3-step shape for `use_function`/`formatter` on the display side (`admin/modules.php:470-486`,
`admin/product_types.php:189-203` — `admin/configuration.php` doesn't consume `use_function` today
and this plan doesn't add that; preserves the existing asymmetry).

**Net effect**: rows that never set the new `renderer` column hit the exact same eval()/`zen_call_function()`
lines that exist today — zero behavior change unless a row explicitly opts in.

## Defining a new renderer

**Core**: adding a new field type means adding a class under `includes/classes/ConfigField/Renderers/`
(or `Formatters/`) that implements `ConfigFieldRendererInterface`/`ConfigFieldFormatterInterface`,
then registering it in `CoreRegistryBootstrap` under a stable key:
```php
// includes/classes/ConfigField/CoreRegistryBootstrap.php
$registry->registerRenderer('zen_cfg_select_option', new Renderers\SelectOptionRenderer());
```
A `configuration` row then opts in by setting `renderer` to
`{"renderer": "zen_cfg_select_option", "params": {"options": ["true","false"]}}` — same shape
whether the row lives in core's own seed SQL or a plugin's installer.

**Plugins** follow the identical interface but register through their own bootstrap rather than
editing core's `CoreRegistryBootstrap`, using the same self-registration idiom encapsulated plugins
already use for observers (`catalog|admin/includes/auto_loaders/config.*.php` populating
`$autoLoadConfig[<loadpoint>][]`, see `zc_plugins/AuctionProductType/v3.0.0/catalog/includes/auto_loaders/config.auction_observer.php`
for the existing pattern this mirrors). Registration is by **class name**, not instance —
`ConfigFieldRegistry::registerRendererClass()`/`registerFormatterClass()` validate the class
implements the right interface immediately (via reflection, no instantiation needed) but defer
actually constructing it until the key is resolved, so a plugin's renderer is never instantiated
on admin pages that never render its config field. A plugin ships:

1. A renderer class in its own namespace, e.g. `Zencart\Plugins\Admin\MyPlugin\ConfigField\MyWidgetRenderer implements ConfigFieldRendererInterface`.
2. An `admin/includes/auto_loaders/config.myplugin_configfield.php` (loadpoint 200+, after core's
   registry is built at breakpoint 176) that includes a one-line bootstrap file calling
   `$zcConfigFieldRegistry->registerRendererClass('myplugin_widget', MyWidgetRenderer::class)`.
3. A `configuration` row (via `ScriptedInstallHelpers::addConfigurationKey()`) whose `renderer`
   property is built with the existing `configFieldRenderer()` helper:
   `'renderer' => $this->configFieldRenderer('myplugin_widget', ['choices' => ['a', 'b']])`.

No plugin in this codebase currently defines a bespoke `set_function`/`use_function` (every one
checked only reuses core's `zen_cfg_*` helpers) — so this path exists to give plugin authors a real
option going forward, not to migrate anything that exists today. A plugin never needs to touch core's
registry-population code; registering under an unclaimed key is enough, and re-registering an
existing key (accidentally or deliberately) simply overrides it, matching the "last one wins" behavior
plugins already expect from other core registries in this codebase.

## Compatibility layer: `ScriptedInstallHelpers`

This section exists solely to satisfy the hard constraint from the Context section — it is not part
of the core rendering fix itself. `ScriptedInstallHelpers::addConfigurationKey()`/
`updateConfigurationKey()` is the one place plugin-authored `configuration` rows enter the database,
so it needs a small, purely additive change to keep accepting legacy `set_function`/`use_function`
forever while also accepting the new `renderer` key for anyone who wants it.

Add `'renderer'` to the existing `$fields` allowlist in both `addConfigurationKey()` (line ~59) and
`updateConfigurationKey()` (line ~101) of `includes/classes/PluginSupport/ScriptedInstallHelpers.php`
— the existing generic `foreach ($fields as $field) { if (isset($properties[$field])) ... }` loop
already threads any new field through for free, no other logic change needed. Add one convenience
helper so plugin authors don't hand-roll JSON:
```php
protected function configFieldRenderer(string $renderer, array $params = [], ?string $formatter = null): string {
    $payload = ['renderer' => $renderer, 'params' => $params];
    if ($formatter !== null) { $payload['formatter'] = $formatter; }
    return json_encode($payload);
}
```
`'use_function'`/`'set_function'` keys are **never removed** from this trait — old plugin installers
keep working unmodified indefinitely.

## Core seed data migration (Phase 4, not Phase 1)

Migrating core's own ~359/~178 rows is the actual deliverable of this effort — it's where essentially
all of the eval() exposure and maintenance burden in this system lives today, since core ships and
executes these rows on every admin page load in every installation. It lands as its own later,
separately-reviewable PR (not Phase 1) purely for sequencing safety: it's the highest-blast-radius
change in this plan (touches every admin config screen at once), so it ships only after the registry
mechanism has proven itself on real call sites with nothing at stake. It migrates by **adding**
`renderer` JSON alongside the existing `set_function`/`use_function` values on the same rows for at
least one full release cycle (belt-and-suspenders: the legacy values stay in place, so a code revert
of this phase falls back to fully-populated legacy data, zero loss). A one-off, offline, developer-run
code-gen script
(`not_for_release/tools/migrate_seed_config_functions.php`) parses the closed set of ~24 known
function-name prefixes out of `mysql_zencart.sql` and the ~20 core module `install()` methods, and
emits reviewable diffs — not an automatic runtime migration.

Once core's own rows are migrated, third-party plugins gain the same interface for free via the
compatibility layer below — that's a downstream consequence of fixing core, not the reason for doing
it.

## Phasing

Phases 1, 2, and 4 are the core-code deliverables (the actual fix). Phase 3 is purely the
compatibility layer that lets third-party plugins opt in once core has proven the mechanism works.

| Phase | Version | Scope | Ships alone? |
|---|---|---|---|
| 1 | v3.0.0 | Interfaces, `ConfigFieldRegistry`, all core adapters, `CoreRegistryBootstrap`, psr4 registration, DB column (nullable, unused), `admin/configuration.php` call site, unit tests | Yes — additive only, no seed data changes |
| 2 | v3.0.0/v3.1.0 | `admin/modules.php` + `admin/product_types.php` call sites via shared `ConfigFieldRowResolver`, `developers_tool_kit.php` sensitivity check update | Yes |
| 3 | v3.1.0 | Compatibility layer: `ScriptedInstallHelpers` `'renderer'` key + helper, plugin-authoring docs | Yes — plugins can start adopting immediately |
| 4 | v3.1.0/v3.2.0 | Core seed data migration — the main payoff: core's own ~359/~178 rows move off eval() (legacy values retained alongside for one cycle) | After 1-3 stable |
| 5 | v3.2.0+ | Drop redundant legacy values from *core's own* seed SQL only (columns/third-party data untouched); advisory (non-blocking) notice when legacy eval path is actually invoked | After 4 stable; no removal of the eval fallback itself is proposed |

## Testing

`not_for_release/testFramework/` has no existing tests touching `configuration.php`, `modules.php`,
`product_types.php`, or the `zen_cfg_*` renderer functions — this closes that gap.

- `Unit/testsConfigField/ConfigFieldRegistryTest.php` — register/resolve/has, override behavior.
- `Unit/testsConfigField/RenderersTest.php` / `FormattersTest.php` — one test per adapter, asserting
  byte-equality against calling the wrapped `zen_cfg_*`/`zen_get_*` function directly.
- `Unit/testsConfigField/LegacyEvalFallbackRegressionTest.php` — **highest-value test**: golden-file
  comparison of real seed `set_function` strings pulled from `mysql_zencart.sql`, run through the
  shared resolver with `renderer` empty, asserting output is byte-identical before/after this change.
- Phase 2 adds `FeatureAdmin` functional tests (pattern: `FeatureAdmin/AdminEndpoints/`) rendering a
  config group page end-to-end for both legacy-only and `renderer`-populated rows.
- Phase 4 re-runs the golden-file test against post-migration seed data, asserting the migration
  itself changed nothing about rendered output.

## Risk / rollback

- Every registry consultation wrapped in `try/catch (\Throwable)`, falling through to the legacy path
  — a broken adapter degrades one field's widget, never fatals the page.
- No row is forced onto the new path — a row only resolves through the registry if its `renderer`
  column is populated, so reverting the code change (no runtime flag needed) instantly and fully
  restores current behavior for every row.
- DB migration is purely additive (`ADD COLUMN renderer TEXT DEFAULT NULL`), trivially reversible,
  zero impact on `set_function`/`use_function`/`val_function`.
- Third-party plugin risk is ~zero through Phase 3 (nothing changes until a plugin explicitly opts in
  to `'renderer'`), and a broken custom renderer only affects that plugin's own fields.

## Critical files

- `includes/classes/ConfigField/*` (new)
- `includes/classes/PluginSupport/ScriptedInstallHelpers.php`
- `admin/configuration.php`, `admin/modules.php`, `admin/product_types.php`, `admin/developers_tool_kit.php`
- `admin/includes/functions/general.php` (unchanged, adapters delegate to it)
- `includes/psr4Autoload.php`
- `zc_install/sql/updates/mysql_upgrade_zencart_300.sql`, `zc_install/sql/install/mysql_zencart.sql`
- `admin/includes/classes/class.admin.zcObserverLogEventListener.php` (sensitivity cross-reference)
