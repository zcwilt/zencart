# AdminUi Resource Developer Guide

## Purpose

This guide is for developers building or converting Zen Cart admin pages into the `AdminUi` resource system.

It documents the implementation shape that exists today, not a future idealized API.

Use this guide when you want to:

- convert a legacy admin page to a resource-based page
- build a new CRUD-oriented admin page
- extend an existing resource page with notifier hooks or custom behavior

Related document:

- [`admin-resource-migration-blueprint.md`](admin-resource-migration-blueprint.md)

## Current Architecture

The current resource flow is built from a few layers:

- admin route entrypoint
- resource class
- datasource
- formatter
- controller
- page object
- template

The core classes live under:

- [`../includes/classes/AdminUi`](../includes/classes/AdminUi)
- [`../includes/classes/ViewBuilders`](../includes/classes/ViewBuilders)

In practice the responsibilities are:

- `AdminUi`
  - admin-page composition and resource orchestration
- `ViewBuilders`
  - shared table, formatter, sort, and controller infrastructure
- `DbRepositories`
  - write/query seams for newer resource pages

## Mental Model

For a typical page:

1. The admin route file boots Zen Cart and resolves a resource class.
2. The resource builds the page dependencies.
3. The datasource reads request state and produces query results.
4. The formatter converts query results into table-ready data and URLs.
5. The controller handles actions and builds the right-side infobox state.
6. The resource returns an [`AdminPageData`](../includes/classes/AdminUi/AdminPageData.php) object with a template path and view data.
7. The route includes that template.

## File Layout

For a resource page, the usual layout is:

- route:
  - [`../admin/countries.php`](../admin/countries.php)
- resource:
  - [`../includes/classes/AdminUi/Resources/CountriesResource.php`](../includes/classes/AdminUi/Resources/CountriesResource.php)
- resource support classes:
  - [`../includes/classes/AdminUi/Resources/Countries/CountriesController.php`](../includes/classes/AdminUi/Resources/Countries/CountriesController.php)
  - [`../includes/classes/AdminUi/Resources/Countries/CountriesDataSource.php`](../includes/classes/AdminUi/Resources/Countries/CountriesDataSource.php)
  - [`../includes/classes/AdminUi/Resources/Countries/CountriesDataFormatter.php`](../includes/classes/AdminUi/Resources/Countries/CountriesDataFormatter.php)
- template:
  - [`../admin/includes/templates/countries_resource.php`](../admin/includes/templates/countries_resource.php)

For simpler list-style pages, the resource can use the shared list page:

- [`../includes/classes/AdminUi/Pages/ResourceListPage.php`](../includes/classes/AdminUi/Pages/ResourceListPage.php)
- [`../admin/includes/templates/resource_list.php`](../admin/includes/templates/resource_list.php)

## Step 1: Create The Admin Route

The route file should be thin. Its job is to:

- boot Zen Cart
- resolve the resource
- build the page
- include the resulting template

Example:

```php
use Zencart\AdminUi\Resources\CountriesResource;
use Zencart\AdminUi\Resources\ResourceResolver;

require 'includes/application_top.php';

$resourceClass = ResourceResolver::getInstance()->resolve('countries', CountriesResource::class);
$adminPage = (new $resourceClass($sanitizedRequest, $messageStack))->buildPage();
extract($adminPage->viewData(), EXTR_SKIP);
```

See:

- [`../admin/countries.php`](../admin/countries.php)
- [`../admin/manufacturers.php`](../admin/manufacturers.php)
- [`../admin/plugin_manager.php`](../admin/plugin_manager.php)

## Step 2: Implement The Resource Class

Every resource extends [`AdminResource`](../includes/classes/AdminUi/Resources/AdminResource.php) and implements `buildPage()`.

The resource is responsible for wiring together:

- table definition
- datasource
- formatter
- controller
- repository or other page services
- page object

The base class already provides:

- request access
- `messageStack` access
- build start/end notifier hooks

See:

- [`../includes/classes/AdminUi/Resources/AdminResource.php`](../includes/classes/AdminUi/Resources/AdminResource.php)

Typical responsibilities inside `buildPage()`:

- define table metadata with [`TableViewDefinition`](../includes/classes/ViewBuilders/TableViewDefinition.php)
- process request/query state through a datasource
- create a formatter from the query results
- construct the controller and call `processRequest()`
- return [`AdminPageData`](../includes/classes/AdminUi/AdminPageData.php)

## Step 3: Define The Table

The table definition is still based on [`TableViewDefinition`](../includes/classes/ViewBuilders/TableViewDefinition.php).

That is where you define things like:

- row key
- page parameter names
- search support
- sort support
- filters
- columns
- selected-row action

Good examples:

- [`../includes/classes/AdminUi/Resources/CountriesResource.php`](../includes/classes/AdminUi/Resources/CountriesResource.php)
- [`../includes/classes/AdminUi/Resources/PluginManagerResource.php`](../includes/classes/AdminUi/Resources/PluginManagerResource.php)

Use table metadata for behavior that belongs to the grid itself. Avoid burying table-specific rules in the template.

## Step 4: Implement The Data Source

The datasource should own request-to-query translation and result retrieval.

Typical datasource responsibilities:

- process search
- process filters
- process sorting
- process pagination
- build the final query
- execute the query
- expose split-page state if needed

Examples:

- [`../includes/classes/AdminUi/Resources/Countries/CountriesDataSource.php`](../includes/classes/AdminUi/Resources/Countries/CountriesDataSource.php)
- [`../includes/classes/AdminUi/Resources/PluginManager/PluginManagerDataSource.php`](../includes/classes/AdminUi/Resources/PluginManager/PluginManagerDataSource.php)

Guidance:

- read request state through [`Request`](../includes/classes/Request.php), not raw `$_GET` and `$_POST`
- prefer `integer()`, `string()`, `query()`, and `post()` helpers
- keep page/pager parameters inside the datasource or formatter instead of rebuilding them in templates

## Step 5: Implement The Formatter

The formatter translates raw query data into UI-facing row data.

Typical formatter responsibilities:

- turn rows into cell arrays for templates
- build selected/unselected row links
- produce sortable header links
- expose search and filter metadata
- expose persistent link parameters for controller URLs

Examples:

- [`../includes/classes/AdminUi/Resources/Countries/CountriesDataFormatter.php`](../includes/classes/AdminUi/Resources/Countries/CountriesDataFormatter.php)
- [`../includes/classes/ViewBuilders/SimpleDataFormatter.php`](../includes/classes/ViewBuilders/SimpleDataFormatter.php)

The formatter should be the main source of truth for list URLs. Avoid having templates or controllers re-derive request state unnecessarily.

## Step 6: Implement The Controller

The controller handles action processing and infobox/form state.

Most current resource controllers extend [`BaseController`](../includes/classes/ViewBuilders/BaseController.php).

Typical controller responsibilities:

- process `action` or page-specific action parameters
- perform inserts, updates, deletes, and toggles
- redirect after writes
- populate infobox header/content
- provide page-specific action URLs for the template

Examples:

- [`../includes/classes/AdminUi/Resources/Countries/CountriesController.php`](../includes/classes/AdminUi/Resources/Countries/CountriesController.php)
- [`../includes/classes/AdminUi/Resources/GeoZones/GeoZonesController.php`](../includes/classes/AdminUi/Resources/GeoZones/GeoZonesController.php)
- [`../includes/classes/AdminUi/Resources/BannerManager/BannerManagerController.php`](../includes/classes/AdminUi/Resources/BannerManager/BannerManagerController.php)

Guidance:

- keep direct database access out of controllers where practical
- prefer small repositories under [`../includes/classes/DbRepositories`](../includes/classes/DbRepositories)
- use the request wrapper instead of raw superglobals
- keep controller state explicit rather than mutating `$_GET`

## Step 7: Choose Shared List Template Or Custom Template

There are two main template paths today.

### Option A: Shared List Template

Use [`ResourceListPage`](../includes/classes/AdminUi/Pages/ResourceListPage.php) and [`resource_list.php`](../admin/includes/templates/resource_list.php) when the page is primarily:

- one table
- one infobox
- optional search/filter toolbar
- optional grouped sections
- standard footer actions

Good fit:

- [`../includes/classes/AdminUi/Resources/PluginManagerResource.php`](../includes/classes/AdminUi/Resources/PluginManagerResource.php)
- [`../includes/classes/AdminUi/Resources/TaxClassResource.php`](../includes/classes/AdminUi/Resources/TaxClassResource.php)

Use [`ListViewConfig`](../includes/classes/AdminUi/Pages/ListViewConfig.php) for:

- grouped table rendering
- group labels
- column width hints

Use [`ListFooterConfig`](../includes/classes/AdminUi/Pages/ListFooterConfig.php) for:

- count HTML
- links HTML
- primary action button

### Option B: Custom Template

Use a page-specific template when the page has more structure than a standard list.

Good examples:

- [`../admin/includes/templates/countries_resource.php`](../admin/includes/templates/countries_resource.php)
- [`../admin/includes/templates/geo_zones_resource.php`](../admin/includes/templates/geo_zones_resource.php)
- [`../admin/includes/templates/banner_manager_resource.php`](../admin/includes/templates/banner_manager_resource.php)

Choose a custom template when the page needs:

- multiple coordinated lists
- deeply custom forms
- non-standard layout
- special client-side behavior
- legacy page parity that does not map cleanly to `resource_list.php`

Even in custom templates, reuse the shared partials when possible:

- [`../admin/includes/templates/partials/resource_toolbar.php`](../admin/includes/templates/partials/resource_toolbar.php)
- [`../admin/includes/templates/partials/resource_infobox.php`](../admin/includes/templates/partials/resource_infobox.php)
- [`../admin/includes/templates/partials/resource_footer.php`](../admin/includes/templates/partials/resource_footer.php)
- [`../admin/includes/templates/partials/resource_list_behaviors.php`](../admin/includes/templates/partials/resource_list_behaviors.php)

## Repositories And Data Access

For newer resource pages, write and query logic should move into repositories where that materially improves separation.

Examples:

- [`../includes/classes/DbRepositories/CountriesRepository.php`](../includes/classes/DbRepositories/CountriesRepository.php)
- [`../includes/classes/DbRepositories/GeoZonesRepository.php`](../includes/classes/DbRepositories/GeoZonesRepository.php)
- [`../includes/classes/DbRepositories/BannerManagerRepository.php`](../includes/classes/DbRepositories/BannerManagerRepository.php)

Guidance:

- avoid introducing new `global $db` usage inside controllers
- let the resource construct and inject the repository
- keep repositories focused on persistence and record lookup

It is acceptable for a resource itself to still obtain the DB dependency from the existing bootstrap if that is the current seam.

## Request Handling Guidance

Use the richer [`Request`](../includes/classes/Request.php) API in new resource code.

Prefer:

- `query()`
- `post()`
- `integer()`
- `string()`
- `boolean()`
- `only()`
- `except()`

Avoid:

- raw `$_GET`
- raw `$_POST`
- hand-casting `input()` everywhere

Legacy compatibility still exists, but new `AdminUi` code should use the richer API.

## Notifier Hooks

The resource system already exposes useful notifier seams.

### Resource lifecycle

- `NOTIFY_ADMIN_RESOURCE_BUILD_PAGE_START`
- `NOTIFY_ADMIN_RESOURCE_BUILD_PAGE_END`

See:

- [`../includes/classes/AdminUi/Resources/AdminResource.php`](../includes/classes/AdminUi/Resources/AdminResource.php)

### Resource resolution

- `NOTIFY_ADMIN_RESOURCE_RESOLVE_START`
- `NOTIFY_ADMIN_RESOURCE_RESOLVE_END`

See:

- [`../includes/classes/AdminUi/Resources/ResourceResolver.php`](../includes/classes/AdminUi/Resources/ResourceResolver.php)

### Shared page/template rendering

- `NOTIFY_ADMIN_LIST_PAGE_BUILD_START`
- `NOTIFY_ADMIN_LIST_PAGE_BUILD_END`
- `NOTIFY_ADMIN_RESOURCE_TOOLBAR_START`
- `NOTIFY_ADMIN_RESOURCE_INFOBOX_START`
- `NOTIFY_ADMIN_RESOURCE_FOOTER_START`

See:

- [`../includes/classes/AdminUi/Pages/ResourceListPage.php`](../includes/classes/AdminUi/Pages/ResourceListPage.php)
- [`../admin/includes/templates/partials/resource_toolbar.php`](../admin/includes/templates/partials/resource_toolbar.php)
- [`../admin/includes/templates/partials/resource_infobox.php`](../admin/includes/templates/partials/resource_infobox.php)
- [`../admin/includes/templates/partials/resource_footer.php`](../admin/includes/templates/partials/resource_footer.php)

If you are adding a new shared seam, prefer notifier hooks over page-specific template overrides.

## Resource Resolution And Overrides

Routes should resolve their resource class through [`ResourceResolver`](../includes/classes/AdminUi/Resources/ResourceResolver.php).

That keeps the default resource in core while allowing controlled overrides.

Pattern:

```php
$resourceClass = ResourceResolver::getInstance()->resolve('countries', CountriesResource::class);
$adminPage = (new $resourceClass($sanitizedRequest, $messageStack))->buildPage();
```

Use stable names like:

- `countries`
- `manufacturers`
- `plugin_manager`
- `geo_zones`

## Suggested Implementation Checklist

When converting a page:

1. Reduce the route file to resource resolution and template inclusion.
2. Create an `AdminResource` subclass.
3. Build the table definition.
4. Move request/query logic into a datasource.
5. Move row formatting and list-link generation into a formatter.
6. Move action handling and infobox building into a controller.
7. Move persistence into a repository where appropriate.
8. Decide whether `resource_list.php` is sufficient or a custom template is needed.
9. Reuse shared partials instead of duplicating toolbar/footer/infobox markup.
10. Add unit render coverage for the new page.

## Testing Expectations

At minimum, add or update unit coverage in:

- [`../not_for_release/testFramework/Unit/testsSundry`](../not_for_release/testFramework/Unit/testsSundry)

Typical tests for a new resource page:

- rendered page test
- resource resolver behavior if applicable
- pagination/sort infrastructure tests if new shared behavior was added
- controller or formatter behavior tests when logic is non-trivial

Useful examples:

- [`RenderedCountriesResourceTest.php`](../not_for_release/testFramework/Unit/testsSundry/RenderedCountriesResourceTest.php)
- [`RenderedGeoZonesResourceTest.php`](../not_for_release/testFramework/Unit/testsSundry/RenderedGeoZonesResourceTest.php)
- [`RenderedBannerManagerResourceTest.php`](../not_for_release/testFramework/Unit/testsSundry/RenderedBannerManagerResourceTest.php)
- [`RenderedResourceListTest.php`](../not_for_release/testFramework/Unit/testsSundry/RenderedResourceListTest.php)
- [`ResourceResolverTest.php`](../not_for_release/testFramework/Unit/testsSundry/ResourceResolverTest.php)

Useful commands:

```bash
composer run test:unit -- --filter RenderedCountriesResourceTest
composer run test:unit:parallel -- --filter Rendered
```

## Practical Recommendations

Prefer these patterns:

- thin admin route file
- explicit resource wiring in `buildPage()`
- request handling through `Request`
- repository-backed writes
- formatter-owned list URLs
- shared partial reuse
- notifier hooks for extensibility

Avoid these patterns in new `AdminUi` code:

- large route files with inline `switch ($action)`
- templates that build business logic or SQL
- controllers mutating global request state
- new controller-level `global $db` usage
- duplicated toolbar/footer/infobox markup

## Good Starting References

If you want a simple starting point:

- [`../includes/classes/AdminUi/Resources/CountriesResource.php`](../includes/classes/AdminUi/Resources/CountriesResource.php)

If you want a shared-list example:

- [`../includes/classes/AdminUi/Resources/PluginManagerResource.php`](../includes/classes/AdminUi/Resources/PluginManagerResource.php)

If you want a more complex custom-template example:

- [`../includes/classes/AdminUi/Resources/GeoZonesResource.php`](../includes/classes/AdminUi/Resources/GeoZonesResource.php)
- [`../includes/classes/AdminUi/Resources/BannerManagerResource.php`](../includes/classes/AdminUi/Resources/BannerManagerResource.php)
