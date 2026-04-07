# Admin Resource Migration Blueprint

## Goal

Build a Filament-like admin-page system for Zen Cart that can generate and standardize the large set of admin pages that are primarily CRUD-driven, while still allowing complex legacy pages to coexist during migration.

The target is not to force every admin page into one generic pattern. The target is to create a strong resource framework for the many pages that are mostly:

- list records
- filter records
- create records
- update records
- delete records
- run a few row-level or page-level actions

## Current State

Zen Cart already has the beginnings of a page-building layer under:

- `includes/classes/ViewBuilders/`

That layer currently provides:

- a table-definition object
- a datasource abstraction
- a formatter
- a controller

After the current pilot work, the direction is now clearer:

- `includes/classes/ViewBuilders/`
  - shared table/list infrastructure only
  - examples: `TableViewDefinition`, `DataTableDataSource`, `SimpleDataFormatter`, `BaseController`
- `includes/classes/Admin/Resources/<ResourceName>/`
  - page-specific datasource/controller/formatter classes for a given admin resource
  - examples: `Admin/Resources/Countries/CountriesDataSource.php`, `Admin/Resources/Manufacturers/ManufacturersController.php`

It is now meaningfully used by:

- `admin/plugin_manager.php`
- `admin/countries.php`
- `admin/manufacturers.php`
- `admin/geo_zones.php`
- `admin/banner_manager.php`

Most admin pages still use the classic pattern:

- one top-level PHP page per route
- inline `switch ($action)` handling
- inline query building
- inline table markup
- inline side infobox construction
- inline form rendering

Representative examples:

- `admin/tax_classes.php`
- `admin/orders_status.php`
- `admin/currencies.php`
- `admin/tax_rates.php`

More complex pages exist and should not be first-wave migration targets:

- `admin/categories.php`
- `admin/customers.php`
- `admin/modules.php`
- `admin/configuration.php`
- `admin/orders.php`

## Design Principles

### 1. Optimize for the common CRUD case

The framework should make simple CRUD pages easy and consistent.

### 2. Preserve progressive migration

Legacy admin pages must continue to work while new pages adopt the framework.

### 3. Separate resource definition from rendering

A resource should declare:

- what records it manages
- which fields exist
- how forms behave
- which actions are available

It should not manually assemble HTML.

### 4. Keep Zen Cart idioms where practical

The framework should integrate with:

- existing admin auth and permissions
- request sanitization
- `messageStack`
- notifier hooks
- existing DB/query abstractions
- existing admin layout

### 5. Support specialized page types later

Not every page is a flat CRUD table. The architecture should allow specialized page types after the first CRUD foundation is stable.

## Proposed Framework Shape

The current `ViewBuilders` layer should evolve into a broader admin resource system.

### Core concepts

- `AdminResource`
- `ListPage`
- `FormPage` or `EditPage`
- `FormDefinition`
- `FieldDefinition`
- `ResourceAction`
- `ResourceRepository`
- `TableDefinition`
- `FilterDefinition`
- `PageRenderer`

## Proposed Classes And Files

### Resource layer

- `includes/classes/Admin/Resources/AdminResource.php`
  - Base class for a managed admin resource.
  - Owns metadata such as label, slug, page title, permissions, and navigation information.

- `includes/classes/Admin/Resources/Countries/`
- `includes/classes/Admin/Resources/Manufacturers/`
- `includes/classes/Admin/Resources/PluginManager/`
- `includes/classes/Admin/Resources/TaxClass/`
  - Resource-specific controller, datasource, and formatter classes should live beside the resource they support.
  - This keeps page-specific behavior out of the shared infrastructure layer.

- `includes/classes/Admin/Resources/Concerns/InteractsWithTable.php`
  - Shared helpers for table pages.

- `includes/classes/Admin/Resources/Concerns/InteractsWithForms.php`
  - Shared helpers for form pages and field hydration.

- `includes/classes/Admin/Resources/Concerns/InteractsWithActions.php`
  - Shared action registration and dispatch helpers.

### Page layer

- `includes/classes/Admin/Pages/ListRecordsPage.php`
  - Standard list page for a resource.
  - Builds listing table, filters, row actions, bulk actions, and create button.

- `includes/classes/Admin/Pages/CreateRecordPage.php`
  - Standard create flow for a resource.

- `includes/classes/Admin/Pages/EditRecordPage.php`
  - Standard edit flow for a resource.

- `includes/classes/Admin/Pages/DeleteRecordAction.php`
  - Standard delete confirmation and execution flow.

- `includes/classes/Admin/Pages/ViewRecordPage.php`
  - Optional details page for resources that need read-only display.

### Table layer

- `includes/classes/Admin/Tables/TableDefinition.php`
  - Successor or wrapper around the current `TableViewDefinition`.
  - Defines columns, sorting, pagination, row actions, and bulk actions.

- `includes/classes/Admin/Tables/Column.php`
  - Base column object.

- `includes/classes/Admin/Tables/Columns/TextColumn.php`
  - Standard text output column.

- `includes/classes/Admin/Tables/Columns/BadgeColumn.php`
  - Status-like column with visual state.

- `includes/classes/Admin/Tables/Columns/IconColumn.php`
  - Icon/status presentation column.

- `includes/classes/Admin/Tables/Filters/FilterDefinition.php`
  - Base filter object for list screens.

### Form layer

- `includes/classes/Admin/Forms/FormDefinition.php`
  - Declarative form schema object.

- `includes/classes/Admin/Forms/Field.php`
  - Base field object.

- `includes/classes/Admin/Forms/Fields/TextInput.php`
- `includes/classes/Admin/Forms/Fields/Textarea.php`
- `includes/classes/Admin/Forms/Fields/Select.php`
- `includes/classes/Admin/Forms/Fields/Checkbox.php`
- `includes/classes/Admin/Forms/Fields/RadioGroup.php`
- `includes/classes/Admin/Forms/Fields/Hidden.php`

These should handle:

- labels
- defaults
- required flags
- help text
- disabled and visible conditions
- value normalization
- validation rules

### Action layer

- `includes/classes/Admin/Actions/ResourceAction.php`
  - Base action type.

- `includes/classes/Admin/Actions/CreateAction.php`
- `includes/classes/Admin/Actions/EditAction.php`
- `includes/classes/Admin/Actions/DeleteAction.php`
- `includes/classes/Admin/Actions/ToggleAction.php`
- `includes/classes/Admin/Actions/BulkAction.php`

These should encapsulate:

- authorization
- labels and icons
- request handling
- redirect behavior
- messageStack feedback

### Data layer

- `includes/classes/Admin/Data/ResourceRepository.php`
  - Base abstraction for resource persistence and record lookup.

- `includes/classes/Admin/Data/QueryBuilderDataSource.php`
  - Standard query-backed datasource for DB rows.

- `includes/classes/Admin/Data/ArrayDataSource.php`
  - Useful for resources like plugin management that are not purely table-backed.

- `includes/classes/Admin/Data/Record.php`
  - Lightweight DTO or wrapper when array/object normalization is needed.

### Rendering layer

- `includes/classes/Admin/Rendering/PageRenderer.php`
  - Shared rendering entry point for resource pages.

- `includes/classes/Admin/Rendering/TableRenderer.php`
  - Renders standard resource tables.

- `includes/classes/Admin/Rendering/FormRenderer.php`
  - Renders standard form schemas using existing admin markup conventions.

- `admin/includes/templates/resource_list.php`
  - Shared list page template.

- `admin/includes/templates/resource_form.php`
  - Shared form page template.

- `admin/includes/templates/resource_panel.php`
  - Shared side panel / infobox partial if side-panel UX is retained.

### Shared infrastructure layer

- `includes/classes/ViewBuilders/`
  - Keep as the shared list/table infrastructure layer.
  - It should contain only reusable primitives, not page-specific controllers or datasources.
  - Examples:
    - `TableViewDefinition`
    - `DataTableDataSource`
    - `SimpleDataFormatter`
    - `BaseController`
    - `NativePaginator`
    - `SortState`
    - `DerivedItemsManager`

## Responsibilities

### `AdminResource`

Responsible for:

- defining resource identity
- defining labels and navigation metadata
- returning the repository/datasource
- returning table schema
- returning form schema
- registering pages and actions

Not responsible for:

- rendering HTML directly
- executing raw SQL inline

### `ListRecordsPage`

Responsible for:

- reading request state
- building the query through the repository/datasource
- applying filters and sorting
- paginating results
- passing structured data to the renderer

### `FormDefinition`

Responsible for:

- defining fields
- defining defaults
- defining layout hints
- defining validation rules
- mapping stored values to form values and back

### `ResourceAction`

Responsible for:

- request dispatch
- permission checks
- mutation execution
- success/error feedback
- redirect target

### `ResourceRepository`

Responsible for:

- base query construction
- record retrieval
- create/update/delete persistence
- domain-specific constraints and preconditions

### `PageRenderer`

Responsible for:

- producing consistent markup
- using shared admin layout and CSS patterns
- rendering tables, forms, actions, and side panels

## Migration Tiers

### Tier 1: Simple CRUD pilots

Best first-wave targets:

- `admin/tax_classes.php`
- `admin/orders_status.php`
- `admin/currencies.php`

These pages have:

- straightforward list queries
- simple create/edit/delete flows
- few domain dependencies
- modest field complexity

Already converted from the first two waves:

- `admin/countries.php`
- `admin/manufacturers.php`
- `admin/geo_zones.php`
- `admin/banner_manager.php`

### Tier 2: CRUD plus domain-specific fields

Second-wave targets:

- `admin/tax_rates.php`
- `admin/zones.php`
- `admin/customer_groups.php`

These introduce:

- related records
- multi-language inputs
- validation complexity
- more action variants

### Tier 3: Specialized workflows

Later migration candidates:

- `admin/configuration.php`
- `admin/modules.php`
- `admin/categories.php`
- `admin/customers.php`
- `admin/orders.php`

These should likely use specialized page types rather than the default CRUD stack.

## Pilot Strategy

The migration should use two early pilots with different goals.

### Pilot 0: Framework pilot

Recommended framework pilot:

- `admin/plugin_manager.php`

Why this should come first:

- it already uses the current `ViewBuilders` layer
- it is the least disruptive place to reshape the framework seams
- it lets us evolve the table, datasource, formatter, controller, and renderer model before touching classic legacy CRUD pages
- it reduces migration risk because we are improving an existing abstraction path instead of forcing a legacy page directly into a still-moving framework

Framework pilot success criteria:

- the current `ViewBuilders` pieces can be re-expressed as the first version of the new resource/page architecture
- `plugin_manager.php` becomes thinner and more declarative
- shared rendering can be extracted without changing page behavior
- datasource and controller contracts become stable enough for legacy page migration

Proposed framework-pilot files:

- `includes/classes/Admin/Resources/PluginManagerResource.php`
- `includes/classes/Admin/Pages/ListRecordsPage.php`
- `includes/classes/Admin/Rendering/PageRenderer.php`
- `includes/classes/Admin/Rendering/TableRenderer.php`
- `admin/plugin_manager.php`
  - reduced further toward a thin bootstrap/entry file
- `admin/includes/templates/resource_list.php`

Status:

- completed
- the framework pilot expanded beyond `plugin_manager` into a shared resource entry pattern, page-data transport object, resource resolver, shared list template, shared partials, and notifier-aware template seams

### Pilot 1: First legacy CRUD migration

Recommended first legacy CRUD migration:

- `admin/countries.php`

Why this was the best first legacy CRUD target:

- very small CRUD surface
- clear list view
- simple create, edit, delete flows
- lightweight validation and persistence rules
- no large embedded JS workflow
- no complicated relational editing UI

Legacy CRUD pilot success criteria:

- a resource can define table columns declaratively
- a resource can define create/edit/delete forms declaratively
- standard actions can replace inline `switch ($action)` code
- page rendering can be shared
- existing admin layout and UX can be preserved
- feature and unit tests can cover the new pattern cleanly

Proposed legacy CRUD pilot files:

- `includes/classes/Admin/Resources/TaxClassResource.php`
- `includes/classes/Admin/Data/TaxClassRepository.php`
- `admin/tax_classes.php`
  - reduced to a thin bootstrap/entry file
- `admin/includes/templates/resource_list.php`
- `admin/includes/templates/resource_form.php`

## Phased Rollout Sequence

### Phase 0: Foundation audit and cleanup

1. Audit `includes/classes/ViewBuilders`.
2. Fix contract issues in the existing layer.
   - Example: align `DataTableDataSource::buildInitialQuery()` with actual `Request` usage.
3. Decide whether `ViewBuilders` becomes:
   - the direct base for the new framework, or
   - a compatibility wrapper around the new framework.

### Phase 1: Resource framework minimum viable core

Build the smallest viable framework that supports:

- resource definition
- list page
- table definition
- form definition
- create/edit/delete actions
- repository abstraction
- shared page rendering

Deliverables:

- base resource classes
- base table and form classes
- base create/edit/delete actions
- shared list and form templates
- compatibility strategy for the current `ViewBuilders` classes

### Phase 2: Framework pilot migration

Rework `admin/plugin_manager.php` as the framework pilot.

Deliverables:

- `PluginManagerResource`
- improved page/list rendering pipeline
- stabilized datasource/controller/formatter contracts
- migration notes about which `ViewBuilders` pieces were kept, replaced, or wrapped
- tests covering the new resource rendering path

### Phase 3: Validate and harden

Use the pilot to improve:

- naming
- rendering seams
- action lifecycle
- form field APIs
- validation ergonomics
- test coverage strategy

Do not begin broad migration until the pilot API feels stable.

### Phase 4: First legacy CRUD migration

Rebuild `admin/tax_classes.php` with the new framework.

Deliverables:

- `TaxClassResource`
- `TaxClassRepository`
- thin route file
- migration notes
- tests covering list/create/edit/delete behavior

### Phase 5: Batch migrate Tier 1 pages

Target:

- `countries.php`
- `orders_status.php`
- `currencies.php`

Goal:

- prove that multiple classic CRUD pages can share the same framework with minimal custom code

### Phase 6: Add richer framework features

After Tier 1 pages succeed, add:

- filter objects
- bulk actions
- badge/icon columns
- row-action menus
- relation-aware select fields
- better form sections and layout controls

### Phase 7: Migrate Tier 2 pages

Target:

- `tax_rates.php`
- `manufacturers.php`
- `geo_zones.php`
- `zones.php`
- `customer_groups.php`

Goal:

- prove the framework can handle more relational and structured CRUD pages

### Phase 8: Introduce specialized page types

Before migrating complex pages, add page types such as:

- settings page
- nested resource page
- wizard page
- detail page
- composite form page

This phase is especially relevant for:

- `configuration.php`
- `modules.php`
- `categories.php`
- `customers.php`

### Phase 9: Migrate or partially wrap complex pages

Not every page needs full migration immediately.

For some pages, the best result may be:

- resource-style list page
- legacy edit page
- or resource-style wrapper around legacy behavior

## Route Strategy

The simplest migration path is to keep existing route files such as:

- `admin/tax_classes.php`
- `admin/countries.php`

but reduce them to thin entry points that:

- bootstrap admin
- instantiate the resource/page class
- dispatch the request
- render the shared template

That keeps URLs stable and avoids a risky route-system rewrite in the first phase.

## Plugin Customization And Override Strategy

One of the design goals for the admin-resource layer should be that plugins can customize migrated pages without forking core route files or copying entire templates.

### What is already customizable today

The current shared `ViewBuilders` layer already exposes a few useful seams:

- `DataTableDataSource` fires `NOTIFY_DATASOURCE_CONSTRUCTOR_END`
  - observers can mutate the `TableViewDefinition` after datasource construction
  - this is already enough to add columns, row actions, button actions, and tweak pagination defaults

- `DataTableDataSource` fires `NOTIFY_DATASOURCE_PROCESSREQUEST`
  - observers can inspect or replace the built query payload
  - this is suitable for altering array-backed or query-backed record sets before formatting

- `BaseController` fires `NOTIFY_TABLEVIEW_PROCESSREQUEST`
  - observers can react after the selected controller action has run
  - this can be used to alter infobox content, respond to custom actions, or add follow-up UI content

- `TableViewDefinition` is mutable
  - plugins can add or reorder columns
  - plugins can add row actions and button actions
  - plugins can adjust pager variables, selected-row actions, and row-count behavior

- `DerivedItemsManager` already supports closure-based derived items
  - a plugin can inject computed columns without replacing the whole formatter

There is already an example observer showing this pattern:

- `admin/includes/classes/observers/auto.TableControllerTest.php.example`

### What is not yet a stable plugin extension seam

The newer resource/page-builder layer is still much lighter on formal extension points.

At the moment:

- `AdminResource`
- `ResourceListPage`
- `PaginatedResourceListPageBuilder`
- `CrudListPageBuilder`
- `ListViewConfig`

do not expose first-class notifier hooks of their own.

That means plugins can currently customize migrated pages best by influencing the shared table/list seam underneath them, not by targeting the higher-level resource objects directly.

This is workable for the current pilot stage, but it is not enough for a long-term plugin API.

### Recommended extension model

For the migration framework, we should support customization at three levels.

#### 1. Low-level runtime mutation

Use notifiers where plugins need to make small, local changes without replacing classes.

Good notifier use cases:

- add or remove a column
- add a row action
- alter button visibility
- append infobox content
- tweak pagination defaults
- alter query results before formatting

Recommended rule:

- notifiers should mutate definitions or payloads
- notifiers should not be responsible for replacing entire page architectures

#### 2. Resource-level replacement or decoration

For larger customizations, plugins should be able to replace or decorate a resource class.

Good replacement/decorator use cases:

- change the datasource/controller pairing for a page
- swap in a different list-view config
- add domain-specific row actions or custom infobox flows
- use a plugin-specific template for a migrated resource

Recommended implementation direction:

- add a small resource registry or resolver
- let core route files ask the registry for the active resource class
- allow plugins to register a replacement or decorator for a named resource

Example future shape:

- route requests `plugin_manager`
- registry resolves `PluginManagerResource`
- plugin may replace it with `VendorPluginManagerResource`
- or wrap it with a decorator that modifies definition/config before build

This is cleaner than teaching plugins to overwrite route files directly.

#### 3. Renderer-level slotting

The shared templates should support limited, intentional insertion points.

Good renderer slot use cases:

- content before the table
- content after the table
- extra infobox actions
- footer actions
- per-column cell decoration hooks

Recommended implementation direction:

- keep the main renderer shared
- add named slots or renderer notifiers rather than requiring template copies

This reduces markup drift and keeps plugins from cloning the full template just to inject one extra button.

### Recommended notifier additions

If the new resource layer is going to be plugin-friendly, we should add notifier hooks at the resource/page-builder level.

Recommended additions:

- `NOTIFY_ADMIN_RESOURCE_BUILD_PAGE_START`
  - allows inspection/replacement of resource build inputs

- `NOTIFY_ADMIN_RESOURCE_BUILD_PAGE_END`
  - allows final mutation of the built `AdminPageData`

- `NOTIFY_ADMIN_LIST_PAGE_BUILD_START`
  - allows mutation of heading, formatter, controller, and config before render packaging

- `NOTIFY_ADMIN_LIST_PAGE_BUILD_END`
  - allows mutation of `AdminPageData` after packaging

- `NOTIFY_ADMIN_CRUD_LIST_BUILD_START`
  - allows mutation of table definition, datasource class, controller class, pagination config, and primary action config

- `NOTIFY_ADMIN_CRUD_LIST_BUILD_END`
  - allows mutation of the final built page object

These should be additive, not replacements for the existing `ViewBuilders` notifications.

### Recommended customization policy for plugins

When documenting this system for plugin authors, we should encourage this order of preference:

1. Use notifiers for small mutations.
2. Use resource replacement/decorators for page-level behavior changes.
3. Use renderer slots for small markup additions.
4. Copy a core template only as a last resort.

That policy will help reduce drift and keep migrated admin pages customizable without making them fragile.

### Current practical conclusion

For the two pilot conversions we have now:

- `plugin_manager`
- `tax_classes`

plugins can already customize quite a bit through the existing `ViewBuilders` notifier seam and mutable `TableViewDefinition`.

However, if we want migrated resources to become a stable public extension surface, we should treat resource-level notifiers and a resource registry/resolver as a required hardening step before broad page migration.

## Sorting Strategy

The `countries` resource is a good place to establish the first reusable sorting seam because it is:

- already migrated to the new resource layer
- still simple enough to reason about
- representative of the kind of list page we will want to sort elsewhere

### Current implementation direction

The current view-builder sorting infrastructure should live at the table-definition and formatter layer, not inside individual templates.

Implemented approach:

- `TableViewDefinition`
  - owns sortable-column metadata
  - owns sort parameter names
  - owns default sort configuration

- `SimpleDataFormatter`
  - turns sortable column definitions into header metadata
  - builds sort links
  - preserves active sort state in row links and footer links

- `DataTableDataSource`
  - resolves validated sort state from request + table definition
  - exposes a reusable helper so individual datasources do not each reimplement request parsing

- page-specific datasource
  - maps the validated sort state to SQL `ORDER BY`
  - can still add page-specific tie-breakers or limitations

### Countries-specific first step

For `countries`, the first enabled sort should be:

- `countries_name`

This gives us a working end-to-end pattern while keeping the initial SQL-driven sort simple and reusable.

Important note:

- `countries` now uses standard numeric pagination only.
- The old alphabetic paginator was removed so sorting applies to the full SQL result set before pagination.
- That makes the behavior much easier to reason about and easier to reuse across other migrated admin pages.

### Reusable contract for sortable columns

Recommended `TableViewDefinition` column metadata:

- `sortable`
  - boolean
- `sortKey`
  - SQL field/expression to order by
- `defaultDirection`
  - `asc` or `desc`

Recommended table-level metadata:

- `sortParameter`
  - default `sort`
- `sortDirectionParameter`
  - default `direction`
- `defaultSort`
  - array with `column` and `direction`
- `persistedParameters`
  - extra request params that should survive sort/pagination/row-selection links

### Why this shape is useful

This gives us a sorting model that:

- works for custom templates like `countries_resource.php`
- also works for shared templates like `resource_list.php`
- keeps request parsing and validation out of templates
- lets plugins alter sortability by mutating `TableViewDefinition`
- gives later pages a consistent contract without committing us to a heavy query builder first

### Follow-on work after countries

Once the `countries` sort path is stable, the next likely candidates are:

- `orders_status`
- `currencies`
- `tax_rates`

Before enabling wider multi-column sorting on `countries`, decide one of these policies:

1. Keep standard numeric pagination for all sortable migrated resource pages.
2. Allow page-specific opt-in sorting only on columns that have a clear SQL sort expression and stable UX.
3. Add richer filtering separately instead of reviving alphabetic pagination.

## Search And Filtering Strategy

Search should follow the same pattern as sorting:

- declared in `TableViewDefinition`
- executed in the datasource/view-builder layer
- rendered by shared templates only when enabled

### Recommended contract

Column-level metadata:

- `searchable`
  - boolean
- `searchKey`
  - optional backing field to search against when the display field is derived

Table-level metadata:

- `searchParameter`
  - default `search`
- `searchPlaceholder`
  - text shown in the rendered input
- `persistedParameters`
  - request parameters that should survive search submissions and resets

### Current implementation direction

The first implementation should optimize for the migrated resource pattern where datasources often return array rows:

- `DataTableDataSource`
  - resolves the search term from the request
  - filters array results across declared searchable columns

- `SimpleDataFormatter`
  - exposes search form metadata to templates
  - preserves compatible request state such as sort and select-filter params

- `resource_list.php`
  - renders a search form only when the formatter reports that search is enabled

### Live pilot

The first live search pilot should be:

- `countries`

Reason:

- it is already a migrated resource
- country-name search is a clear, low-risk user-facing behavior
- it proves the important rule that SQL-first pages must apply search before pagination is calculated

### Scope of the first iteration

The first iteration should support:

- one free-text search box
- case-insensitive partial matching
- searching across multiple declared columns
- preserving active sort/filter state during search
- SQL-first pages applying the search term before result counts and page slices are calculated

The first iteration should not yet try to support:

- field-specific advanced search builders
- SQL query rewriting for every datasource type
- multi-input filter bars in the generic renderer
- live search on every migrated page before route/resource-level regressions exist

### Follow-on work

After the simple search input is stable, the next step is to unify it with existing filter definitions so a list page can render:

- select filters
- text search
- reset controls

from one shared list-toolbar contract instead of mixing separate mechanisms.

## Testing Strategy

### Unit coverage

Add unit tests for:

- resource definitions
- form schema defaults
- action dispatch behavior
- repository validation rules
- renderer output for common page fragments

### Feature coverage

Add in-process admin feature tests for pilot resources:

- list page loads
- create succeeds
- edit succeeds
- delete succeeds
- validation failures show expected messages

### Regression goal

Every migrated page should have at least one happy-path feature test before the legacy implementation is replaced.

## What Not To Do

- Do not start with `categories.php` or `customers.php`.
- Do not make HTML generation live inside resource classes.
- Do not require a full route overhaul before the first pilot.
- Do not attempt to solve every admin page type in version one.
- Do not break the existing admin theme and layout while proving the framework.

## Recommended Immediate Next Steps

1. Fix the `ViewBuilders` contract mismatches and decide the compatibility strategy.
2. Create the base `AdminResource`, `ListRecordsPage`, `FormDefinition`, and `ResourceAction` classes.
3. Build shared list and form templates under `admin/includes/templates/`.
4. Implement `PluginManagerResource` as the framework pilot.
5. Implement `TaxClassResource` as the first legacy CRUD migration after the framework pilot stabilizes.
6. Add tests for each pilot before migrating the next Tier 1 page.

## Summary

The right migration path is:

- keep current admin routes stable
- build a new resource framework beside the legacy page scripts
- migrate the simplest CRUD page first
- harden the abstractions
- then migrate similar pages in batches

The first framework pilot should be:

- `admin/plugin_manager.php`

The first legacy CRUD migration should be:

- `admin/tax_classes.php`

The first outcome should be a framework that can confidently replace the repeated pattern found across many admin CRD pages without yet trying to absorb the most specialized workflows.
