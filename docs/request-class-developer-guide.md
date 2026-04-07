# Request Class Developer Guide

## Purpose

This guide documents the current Zen Cart [`Request`](../includes/classes/Request.php) wrapper for developers.

It covers:

- the legacy behavior that remains supported
- the newer bag-specific and typed helpers
- recommended usage for new code
- migration guidance for older code

## Current Role Of The Request Class

[`Request`](../includes/classes/Request.php) is the shared wrapper around incoming request data.

It is used during sanitization/bootstrap and then passed into newer application code, especially:

- `AdminUi` resources and controllers
- page builders and datasources
- newer infrastructure tests

The bootstrap capture point is:

- [`includes/init_includes/init_sanitize.php`](../includes/init_includes/init_sanitize.php)
- [`admin/includes/init_includes/init_sanitize.php`](../admin/includes/init_includes/init_sanitize.php)

## Design Goals

The current class is designed to do two things at once:

1. preserve older merged-request behavior
2. support clearer, more explicit request access in newer code

That means the class is intentionally a compatibility layer, not a strict break from past behavior.

## Construction

### `Request::capture()`

Use `capture()` to build the request from PHP superglobals.

```php
$request = Request::capture();
```

This captures:

- `$_GET`
- `$_POST`
- `$_COOKIE`
- `$_SERVER`
- `$_REQUEST`

`capture()` preserves the legacy merged request semantics by using `$_REQUEST` as the main parameter bag when it is available.

### `Request::fromArrays()`

Use `fromArrays()` when you need explicit construction, especially in tests.

```php
$request = Request::fromArrays(
    ['page' => '2'],
    ['search' => 'Belgium'],
    ['theme' => 'classic'],
    ['REQUEST_METHOD' => 'POST']
);
```

You can also pass an explicit merged request bag as the fifth argument:

```php
$request = Request::fromArrays($query, $post, $cookie, $server, $request);
```

This is useful when you need full control over merged compatibility behavior.

## Internal Bags

The class currently stores:

- merged request data
- query data
- post data
- cookie data
- server data

Those correspond to:

- `paramBag`
- `queryBag`
- `postBag`
- `cookieBag`
- `serverBag`

Newer code should prefer explicit bag access when the source matters.

## Legacy-Compatible API

These methods remain important for backward compatibility.

### `input($key, $default = null)`

Reads from the merged request bag.

```php
$page = $request->input('page', 1);
```

Use this when you intentionally want merged request semantics.

### `has($key)`

Checks for presence using `isset()`.

```php
if ($request->has('search')) {
    // ...
}
```

Important behavior:

- `has()` returns `false` for `null` values
- this is preserved intentionally for backward compatibility

## Newer API

These methods were added to make request access more explicit and safer for new code.

### `exists($key)`

Checks for presence using `array_key_exists()`.

```php
if ($request->exists('nullable_field')) {
    // key is present even if value is null
}
```

Use this when you need to distinguish:

- missing key
- present key with `null`

### `all()`

Returns the merged request bag.

```php
$all = $request->all();
```

### `only(array $keys)`

Returns only the selected keys from the merged bag.

```php
$paging = $request->only(['page', 'sort', 'direction']);
```

### `except(array $keys)`

Returns the merged bag with selected keys removed.

```php
$persistent = $request->except(['action', 'id']);
```

## Bag-Specific Access

Use these methods when you want the actual source of the value to matter.

### `query($key = null, $default = null)`

Reads from the GET bag.

```php
$page = $request->query('page', '1');
$allQuery = $request->query();
```

### `post($key = null, $default = null)`

Reads from the POST bag.

```php
$title = $request->post('title', '');
$allPost = $request->post();
```

### `cookie($key = null, $default = null)`

Reads from the COOKIE bag.

### `server($key = null, $default = null)`

Reads from the SERVER bag.

```php
$method = $request->server('REQUEST_METHOD', 'GET');
```

## Typed Helpers

These methods are preferred in newer code when the expected type is known.

### `integer($key, int $default = 0): int`

```php
$page = $request->integer('page', 1);
```

Use this instead of manual casting like:

```php
$page = (int) $request->input('page', 1);
```

### `string($key, string $default = ''): string`

```php
$action = $request->string('action', '');
```

Behavior:

- returns the default if the key is missing
- returns the default if the value is an array
- otherwise casts to string

### `boolean($key, bool $default = false): bool`

```php
$enabled = $request->boolean('enabled');
```

Recognized truthy string values:

- `1`
- `true`
- `on`
- `yes`

Recognized falsy string values:

- `0`
- `false`
- `off`
- `no`
- empty string

### `inputArray($key, array $default = []): array`

```php
$ids = $request->inputArray('ids');
```

Use this when a request value is expected to be an array.

### `filled($key): bool`

```php
if ($request->filled('version')) {
    // ...
}
```

Behavior:

- returns `false` if the key is missing
- returns `false` for empty arrays
- trims strings and treats whitespace-only strings as empty

## Recommended Usage In New Code

For new code, prefer:

- `query()` for URL/querystring state
- `post()` for submitted form values
- `integer()`, `string()`, and `boolean()` when the expected type is known
- `exists()` when `null` must be treated as present
- `only()` and `except()` when building persistent parameter sets

Avoid in new code:

- raw `$_GET`
- raw `$_POST`
- raw `$_REQUEST`
- repeated inline casting from `input()`

## When To Use `input()`

`input()` is still appropriate when:

- you are working in older compatibility-heavy code
- you intentionally want merged request behavior
- you are incrementally updating legacy code and do not want to change semantics yet

It should not be the first choice in newer `AdminUi` and infrastructure code.

## Migration Guidance

Here is the practical migration path for existing code.

### Before

```php
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
```

### Better

```php
$page = $request->integer('page', 1);
$action = $request->string('action', '');
```

### Before

```php
$title = isset($_POST['title']) ? $_POST['title'] : '';
```

### Better

```php
$title = $request->post('title', '');
```

### Before

```php
if (isset($_REQUEST['version']) && trim($_REQUEST['version']) !== '') {
    // ...
}
```

### Better

```php
if ($request->filled('version')) {
    // ...
}
```

## Current Examples In The Codebase

### AdminUi controllers

Examples:

- [`../includes/classes/AdminUi/Resources/Countries/CountriesController.php`](../includes/classes/AdminUi/Resources/Countries/CountriesController.php)
- [`../includes/classes/AdminUi/Resources/GeoZones/GeoZonesController.php`](../includes/classes/AdminUi/Resources/GeoZones/GeoZonesController.php)
- [`../includes/classes/AdminUi/Resources/BannerManager/BannerManagerController.php`](../includes/classes/AdminUi/Resources/BannerManager/BannerManagerController.php)
- [`../includes/classes/AdminUi/Resources/PluginManager/PluginManagerController.php`](../includes/classes/AdminUi/Resources/PluginManager/PluginManagerController.php)

These use methods like:

- `integer()`
- `string()`
- `post()`
- `filled()`

### Datasources and page builders

Examples:

- [`../includes/classes/ViewBuilders/DataTableDataSource.php`](../includes/classes/ViewBuilders/DataTableDataSource.php)
- [`../includes/classes/AdminUi/Pages/PaginatedResourceListPageBuilder.php`](../includes/classes/AdminUi/Pages/PaginatedResourceListPageBuilder.php)

These use the typed helpers to keep request normalization in one place.

## Compatibility Notes

Important compatibility points:

- `capture()` still works for legacy callers
- `input()` still reads the merged request bag
- `has()` still uses `isset()` semantics
- the class is still a singleton via the shared trait

That means:

- old code should continue to work
- new code can be clearer without forcing a full rewrite

## Testing

The main infrastructure coverage for the updated request class is:

- [`../not_for_release/testFramework/Unit/testsSundry/RequestInfrastructureTest.php`](../not_for_release/testFramework/Unit/testsSundry/RequestInfrastructureTest.php)

That test covers:

- legacy merged input behavior
- bag-specific access
- typed helpers
- `exists()` versus `has()` null handling

Preferred command:

```bash
composer run test:unit -- --filter RequestInfrastructureTest
```

## Practical Recommendations

If you are touching legacy code:

- keep behavior stable first
- switch from raw superglobals to `Request`
- then adopt bag-specific and typed helpers where the intent is clear

If you are writing new infrastructure or `AdminUi` code:

- use explicit bag access
- use typed helpers
- avoid merged semantics unless they are truly needed

If you are writing tests:

- use `Request::fromArrays()` for precise request setup
- only use `capture()` when you are deliberately testing superglobal compatibility
