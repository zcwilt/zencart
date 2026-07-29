# Unified webhook/connector infrastructure (`zc_connect.php`)

## Context

[Discussion #6992](https://github.com/zencart/zencart/discussions/6992) ("Webhook Infrastructure")
proposes a single, standardized incoming-webhook endpoint so third-party services (shipping,
payment, inventory) can integrate without each plugin needing its own listener file dropped at the
site root. lat9's original starting-point proposal there: a root `zc_connect.php` replacing
`ipn_main_handler.php` and other plugin-specific webhook routers, loading candidate connector
classes from `/includes/classes/connectors` (core) and
`zc_plugins/xx/vv/catalog/includes/classes/connectors` (plugins) until one claims the request. The
discussion stalled on one open question: can a connector determine ownership of an incoming request
without loading `application_top.php` (and everything it pulls in), to avoid full-bootstrap cost on
every scan/junk request hitting the endpoint?

**That question is already answered in this codebase, just not wired up as a generic router yet.**
`includes/auto_loaders/webhook.core.php` (`@since ZC v2.2.0`) is a working, trimmed bootstrap
profile: DB config, notifier, plugin observers, language, template — but none of the
cart/session/category machinery a normal storefront page pulls in. It's selected via
`$loaderPrefix = 'webhook';` set before `require 'includes/application_top.php';`, and it's already
in production use by two separate entry points:

- `zc_plugins/PayPalRestful/v2.1.1/catalog/includes/modules/payment/paypal/PayPalRestful/ppr_webhook.php`
  — sets `$loaderPrefix = 'webhook';`
- `ipn_main_handler.php` (core, root) — sets `$loaderPrefix = 'paypal_ipn';`, its own separate
  lightweight profile (`includes/auto_loaders/paypal_ipn.core.php`)

So per-entry-point lightweight bootstrap is a proven pattern with two live examples, not a design
question that needs re-litigating.

### What else already exists

`WebhookController::__invoke()` (in the `PayPalRestful` plugin, namespace `PayPalRestful\Webhooks`)
already has a claim/dispatch contract shape baked in via its return value: `true` = handled, `false`
= failed verification, `null` = "not mine, try the next one" — with this comment sitting directly on
the `null` branch:

```php
if ($status === null) {
    // For future dev: null means this webhook handler should be ignored, and go to next one
    // Probably this logic would be in a loop of classes being iterated, and would respond null to loop to the next one.
    return null;
}
```

Nothing currently provides that loop — `ppr_webhook.php` just does
`$controller = new PayPalRestful\Webhooks\WebhookController(); $result = $controller();` directly.
This is the callee side of the exact multi-connector protocol #6992 describes, built and shipped,
with no caller.

The plugin's webhook subsystem is otherwise a complete, working reference implementation worth
mining for the shared contract:

- **`WebhookResponder::shouldRespond()`** — the cheap claim check: header presence
  (`PAYPAL-AUTH-VERSION`, `PAYPAL-AUTH-ALGO`), User-Agent contains `PayPal/`, `event_type` present in
  the JSON body. No I/O.
- **`WebhookResponder::verify()`** — the expensive part. `doCrcCheck()` fetches PayPal's public cert
  over HTTPS (`read_url()`, 10s timeout) before local `openssl_verify()`; if that path returns
  `null` it falls back to `verifyByPostback()`, a *second* outbound HTTPS round-trip (token
  validation + POST the full payload back to PayPal for them to confirm). Worst case: two
  network calls with real timeout budget, per incoming request.
- **`WebhookController::dispatch()`** — event-to-class resolution by naming convention
  (`event_type` → `PayPalRestful\Webhooks\Events\<StudlyCase>`, each implementing
  `WebhookHandlerContract`).
- **Idempotency** — `UNIQUE(webhook_id)` constraint + `INSERT IGNORE`, with a pre-flight `SELECT` as
  a fast path. Handles PayPal's at-least-once redelivery semantics.

### A concrete, currently-live gap

`PayPalRestfulApi::subscribeWebhook()` registers this URL with PayPal's API:

```php
$url = HTTP_SERVER . DIR_WS_CATALOG . 'ppr_webhook.php';
```

— i.e. the site root. But the actual file lives at
`.../catalog/includes/modules/payment/paypal/PayPalRestful/ppr_webhook.php`, deep in the plugin
tree. There is no copy-to-root step anywhere in `Installer/ScriptedInstaller.php`, and no
`.htaccess` rewrite at the site root. As checked into this repo, that registered URL does not
resolve to a file. This needs confirming with lat9/drbyte — either it's a known in-progress gap
(plausible, this is an actively developed repo) or something's missing that isn't visible from code
alone — but it's worth treating as evidence that #6992's problem is still genuinely unsolved even
for the plugin furthest along toward solving it.

## Goals

1. A single, core-owned root entry point (`zc_connect.php`) that any plugin can register a
   connector against, without physically copying files to the site root.
2. Reuse the existing `webhook.core.php` lightweight-bootstrap pattern — don't invent a new one.
3. Formalize the claim/handle split already implicit in `WebhookController`, but make claiming
   **declarative** (a structured signature core can reason about) rather than an opaque predicate
   method, so conflicting registrations are mechanically detectable instead of relying on every
   plugin author's boolean logic being mutually exclusive with every other plugin's.
4. `ipn_main_handler.php` is out of scope for migration. It's a live, stable URL already configured
   in an unknown number of existing merchants' PayPal account settings (Standard/WPP/DP). It stays
   as a real, physically-present file indefinitely; nothing about this plan requires touching it.

## Design

### Connector contract

Two-phase, not one bundled method — split for a concrete reason, not just tidiness: with a single
bundled `attempt(): ?bool`-style method, a router iterating multiple installed connectors from
different plugins has no way to stop one connector's expensive network-bound `verify()` from running
before moving to the next candidate. Two PayPal-family plugins installed simultaneously could each
trigger their own outbound HTTPS call to PayPal before either resolves, sequentially, per incoming
request. A required cheap-first phase, enforced by the interface rather than by convention, avoids
that pile-up.

```php
interface ConnectorContract
{
    /**
     * Cheap, synchronous, no I/O. Declares what this connector claims, so core can detect
     * conflicting registrations at registry-build time rather than trusting every connector's
     * predicate logic to be mutually exclusive with every other installed connector's.
     */
    public static function claimSignature(): array;

    /** Fast runtime check against the actual request, using the declared signature. */
    public function canClaim(): bool;

    /** May do network I/O (signature verification, DB writes, etc). Only called on the single
     *  connector whose canClaim() won. */
    public function handle(): void;
}
```

`claimSignature()` is static and side-effect-free specifically so building the registry never
requires constructing a connector object (and whatever dependencies its constructor pulls in) just
to ask what it claims.

### Declarative discriminators

A small, fixed vocabulary core understands well enough to compare, not arbitrary code:

- `header(name, valuePattern)`
- `userAgentContains(substring)`
- `bodyField(jsonPath, value)`
- `queryParam(name, value)`

A connector's full claim signature is one or more discriminators, implicitly ANDed. Mapping the
existing `WebhookResponder::shouldRespond()` logic onto this vocabulary as a concrete worked
example, a `PayPalRestfulConnector::claimSignature()` would declare:

```php
[
    ['type' => 'header', 'name' => 'PAYPAL-AUTH-VERSION'],
    ['type' => 'header', 'name' => 'PAYPAL-AUTH-ALGO'],
    ['type' => 'userAgentContains', 'value' => 'PayPal/'],
    ['type' => 'bodyField', 'path' => 'event_type'],
]
```

Exact-duplicate collisions (two connectors declaring the identical discriminator set — the realistic
case: two PayPal-family plugins both checking for PayPal's own auth headers) are mechanically
decidable and should hard-block at registry build time. Genuinely fuzzy overlaps (one connector's
substring check subsuming another's) aren't fully decidable in the general case; flag those for
manual review rather than silently allowing or blindly blocking.

### Cached registry, not reactive per-install checks

Rebuilding and validating the *whole* registry as a unit on every plugin-list change (rather than
only checking a newly-installed connector against whatever's currently installed) catches
ordering-dependent collisions too — plugin B installed before plugin A never got a chance to check
against A at B's own install time under a reactive-only approach.

- **Trigger**: rebuild on any change to `installedPlugins` — install, uninstall, enable, disable.
- **Build**: glob `includes/classes/connectors/*.php` (core) and
  `zc_plugins/*/*/catalog/includes/classes/connectors/*.php` (installed plugins only), call each
  class's static `claimSignature()`.
- **Validate**: pairwise-compare every declared signature across the full assembled set.
- **On collision**: **open decision, not yet settled** — fail the entire rebuild (webhook
  processing broken for every connector until resolved) vs. exclude just the colliding pair (an
  unrelated shipping-webhook connector keeps working even if two PayPal plugins conflict with each
  other) plus a persistent admin notification. Leaning toward excluding the pair for resilience, but
  this changes the failure mode store owners experience and should be confirmed with
  lat9/drbyte rather than decided unilaterally here.
- **Store**: `webhook.core.php`'s bootstrap already instantiates `$zc_cache = new cache();` at
  breakpoint 30 — the registry can be stored through that existing cache-driver abstraction, or as a
  compiled flat PHP array file regenerated on rebuild and `require`d directly at `zc_connect.php`
  runtime (cheaper on the hot path; same convention `auto_loaders/*.core.php` already uses for
  plain-PHP-array config).

### `zc_connect.php` request flow

1. `$loaderPrefix = 'webhook';` then `require 'includes/application_top.php';` — reuse the existing
   profile, no new bootstrap invented.
2. Load the cached connector registry.
3. Call `canClaim()` on each candidate in registry order until exactly one returns `true`.
4. Call `handle()` on that single connector. No further candidates are tried once one claims —
   claiming is exclusive by construction, backed by the collision-free-by-validation registry.
5. If zero connectors claim the request: log and respond in a way that doesn't leak information to
   an unauthenticated caller (matches `ppr_webhook.php`'s existing
   `ini_set('display_errors', '0')` + generic-500-on-exception posture).

## Migration plan: `PayPalRestful`

Phased so each step is independently shippable and the plugin keeps working at every intermediate
point — no big-bang cutover.

**Phase 1 — extract, don't yet switch.**
Add `zc_plugins/PayPalRestful/vX/catalog/includes/classes/connectors/PayPalRestfulConnector.php`
implementing `ConnectorContract`:

- `claimSignature()` — the four discriminators listed above, lifted directly from
  `WebhookResponder::shouldRespond()`'s existing header/UA/body checks.
- `canClaim()` — calls the existing `shouldRespond()` (unchanged internals).
- `handle()` — calls the existing `verify()` → `dispatch()` flow (unchanged internals).

This is a thin wrapper, not a rewrite: `WebhookController`, `WebhookResponder`, `WebhookObject`, the
per-event `Events/*.php` classes, and the idempotency/dedup logic all stay exactly as they are.
`ppr_webhook.php` keeps working exactly as today throughout this phase — the new connector class
exists but nothing routes to it yet, so there's no behavior change to verify beyond "the plugin still
works."

**Phase 2 — fix the URL-drift bug, independent of `zc_connect.php`.**
Found while writing this plan:
`PayPalRestfulApi::registerAndUpdateSubscribedWebhooks()` only PATCHes `event_types` for an
existing `webhook_id` — it never compares the currently-registered `url` against what
`subscribeWebhook()` would compute today, and never corrects it. That means *any* future URL change
(domain migration, this `zc_connect.php` work, or literally any other reason `HTTP_SERVER`/the
webhook path changes) silently strands already-registered sites: their `webhook_id` keeps getting
patched for event types forever, against a URL that no longer matches. This should be fixed
regardless of whether `zc_connect.php` ships — `registerAndUpdateSubscribedWebhooks()` needs to
compare `$response['url']` (already fetched via the existing `curlGet("v1/notifications/webhooks/$webhook_id")`
call) against the expected current URL and issue an `op=replace, path=/url` PATCH (same shape as the
existing `event_types` PATCH) when they differ. Do this fix first, on the *current* `ppr_webhook.php`
URL, so it's tested and working before `zc_connect.php` adds a second reason the URL might change.

**Phase 3 — switch the registered URL.**
Once Phase 1's connector exists and `zc_connect.php` can route to it, and Phase 2's drift-correction
is in place: change `subscribeWebhook()`'s `$url` construction to `'zc_connect.php'`. Because of
Phase 2, sites with an existing `webhook_id` self-heal on their next `registerAndUpdateSubscribedWebhooks()`
call (this already runs periodically/on relevant admin actions per the existing module) rather than
needing a manual re-subscribe step or an explicit delete-and-recreate migration.

**Phase 4 — retire `ppr_webhook.php`.**
Once nothing points at it (confirm via the plugin's own webhook-request logging, which already
exists via `Logger`), delete the file and the dead `WebhookController` direct-instantiation entry
point. Given the currently-unreachable-URL gap noted above, this may turn out to be close to
zero-risk in practice — but that needs confirming with lat9/drbyte (open question #2), not assumed.
`purgeOldFiles()` in `ScriptedInstaller.php` already has cleanup logic for legacy root files
(`ppr_listener.php`, `ppr_webhook_main.php`) from an earlier transition; this is the same pattern,
one more file added to that list on upgrade.

**Explicitly not changing in this migration**: `WebhookController`'s internal verification logic,
the `Events/*.php` per-event-type classes, the idempotency table/guard, `WebhookHandlerContract`
(plugin-internal, separate from the new core `ConnectorContract` — the connector's `handle()` is
what bridges the two). None of that needs to know `zc_connect.php` exists.

## Plan: `ipn_main_handler.php`

This is a different kind of problem than `PayPalRestful`'s, and gets a different answer on the URL
— but *not* a different answer on whether it should be built on the same underlying system.
Splitting into two separate questions, since conflating them was the earlier mistake in this doc:

**Does the URL move?** No — and this is a permanent constraint, not a phase that completes later.
An unknown, unbounded number of existing merchants have `ipn_main_handler.php`'s URL typed directly
into their own PayPal account's IPN notification settings, outside Zen Cart's control entirely.
There is no code-side migration that reaches into a third party's PayPal account and changes a URL
they configured. Realistically, close to none of them ever will move it, even if a newer mechanism
exists — it works, they have no reason to touch it. So this file, at this exact path, is not a
legacy shim to be phased out; it is a **permanent, first-class entry point** for as long as Zen Cart
supports PayPal Standard/WPP/DP at all.

**Does it need to participate in the `canClaim()`/registry/collision-detection machinery?** No —
and this is a narrower, different claim than "it doesn't matter." Nothing else will ever receive a
POST at that exact path; PayPal's account config *is* the routing decision, made once, external to
this codebase. The registry exists to disambiguate *multiple* connectors sharing *one* URL
(`zc_connect.php`); `ipn_main_handler.php` has a URL nothing else shares, so registry participation
would add a layer of machinery solving a problem this file doesn't have.

**But it should still be a thin wrapper around the same connector-shaped logic, and this belongs in
the plan, not deferred after it.** The permanence established above is exactly the argument for
doing this now rather than later: this URL is realistically how the majority of existing PayPal
integrations will keep working indefinitely, so leaving its ~600-line monolithic switch statement
structurally disconnected from the new pattern — while only the newer, less-adopted `PayPalRestful`
plugin gets the clean connector shape — would mean the new architecture's benefits (testability,
consistent structure, one place business logic lives) skip the integration most merchants are
actually using. Concretely:

- Extract the transaction-processing logic (everything from `ipn_lookup_transaction()` through the
  `switch ($txn_type)` order-creation/status-update block) into a `PayPalClassicIpnConnector` class
  implementing the same `handle()` contract `ConnectorContract` defines for `zc_connect.php`-routed
  connectors — but this one is **not registered in the connector registry**, since (per above) it
  has nothing to claim against. It's invoked directly, not discovered.
- `ipn_main_handler.php` itself shrinks to a thin, permanent shim:

  ```php
  $loaderPrefix = 'paypal_ipn';
  require 'includes/application_top.php';
  (new PayPalClassicIpnConnector())->handle();
  ```

  (the `$_GET['type'] === 'ec'` Express Checkout branch, see below, stays separate and unaffected).
- This is a same-behavior refactor, not a rewrite: same URL, same `$loaderPrefix`/bootstrap profile,
  same request handling, same `ipn_*()` helper functions in `paypal_functions.php` underneath. The
  win is structural — the logic becomes unit-testable in isolation and matches the shape
  `PayPalRestfulConnector` uses, rather than living only as a giant procedural script.
- Sequencing: this doesn't block `zc_connect.php`/`PayPalRestful` shipping and isn't blocked by it
  either — the two can happen in parallel or either order. It only needs `ConnectorContract` to
  exist (so `handle()`'s shape is defined), not the registry/router itself.

**Also explicitly untouched**: the `$_GET['type'] === 'ec'` branch at the top of
`ipn_main_handler.php` (Express Checkout token handling) is a completely different kind of request —
an interactive browser redirect flow a logged-in customer's browser is sent through, not a passive
server-to-server webhook/IPN listener. It shouldn't be pulled into the connector abstraction even
if the IPN-handling half eventually is; conflating the two would be a scope mistake independent of
this plan's timeline.

## Explicitly out of scope (whole document)

- Outbound webhook delivery (Zen Cart calling *out* to third parties). #6992 and this plan are about
  *incoming* connectors only.
- A generic "pull" API for external services to query store state (mentioned as a tangent in
  #6992's early comments, ruled out of scope there too).

## Open questions requiring lat9/drbyte sign-off

1. Collision handling: fail the whole registry rebuild, or exclude just the colliding pair?
2. Is the `subscribeWebhook()`-registers-an-unreachable-URL gap a known in-progress issue, or does
   something else make `ppr_webhook.php` reachable that isn't visible from a code-only read (e.g. a
   deployment step, a documented manual copy step)?
3. Discriminator vocabulary completeness — do the four proposed types
   (`header`/`userAgentContains`/`bodyField`/`queryParam`) cover every connector shape anyone
   actually needs, including non-PayPal use cases (shipping, inventory) mentioned in the original
   discussion?
