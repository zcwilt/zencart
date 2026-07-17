<?php

declare(strict_types=1);

/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *
 * One-off, offline, developer-run code-gen tool (NOT included in runtime execution paths).
 *
 * Parses every `INSERT INTO configuration (...)` / `INSERT INTO product_type_layout (...)`
 * statement in zc_install/sql/install/mysql_zencart.sql and, for rows whose `set_function`
 * and/or `use_function` value maps to one of the core Zencart\ConfigField renderer/formatter
 * adapters (see includes/classes/ConfigField/CoreRegistryBootstrap.php), appends a `renderer`
 * column + JSON value to the row.
 *
 * The existing `set_function`/`use_function`/`val_function` columns and values are NEVER
 * modified or removed — this is purely additive, so disabling/reverting this migration loses
 * nothing (see docs/config-field-registry-plan.md, "Core seed data migration" section).
 *
 * Rows whose set_function/use_function value doesn't match a known core function (or has none)
 * are left untouched and reported in the summary for manual review.
 *
 * Usage:
 *   php not_for_release/tools/migrate_seed_config_functions.php            # dry run, prints summary + sample diff
 *   php not_for_release/tools/migrate_seed_config_functions.php --apply    # writes the migrated file in place
 *
 * The literal PHP array parsing below uses eval() on strings extracted from this repo's own
 * trusted seed SQL file — a one-time, offline, developer-run build step with no attacker-controlled
 * input, categorically different from evaluating a database value at runtime (which is exactly
 * what this whole effort replaces). Do not reuse this pattern in any runtime code path.
 */

// -----
// Closed set of core `set_function` (renderer) targets and which params key (if any) receives
// the parsed literal-array argument. Must match Zencart\ConfigField\CoreRegistryBootstrap.
//
const RENDERER_PARAM_KEY = [
    'zen_cfg_select_option' => 'options',
    'zen_cfg_select_drop_down' => 'options',
    'zen_cfg_select_multioption' => 'choices',
    'zen_cfg_select_multioption_pairs' => 'choices',
    'zen_cfg_pull_down_country_list' => null,
    'zen_cfg_pull_down_country_list_none' => null,
    'zen_cfg_pull_down_zone_list' => null,
    'zen_cfg_pull_down_tax_classes' => null,
    'zen_cfg_pull_down_zone_classes' => null,
    'zen_cfg_pull_down_order_statuses' => null,
    'zen_cfg_pull_down_htmleditors' => null,
    'zen_cfg_pull_down_exchange_rate_sources' => null,
    'zen_cfg_textarea' => null,
    'zen_cfg_textarea_small' => null,
    'zen_cfg_password_input' => null,
    'zen_cfg_select_coupon_id' => null,
    'zen_cfg_read_only' => null,
];

// -----
// Closed set of core `use_function` (formatter) targets. All take no params.
//
const FORMATTER_FUNCTIONS = [
    'zen_get_country_name',
    'zen_cfg_get_zone_name',
    'zen_get_zone_class_title',
    'zen_get_order_status_name',
    'zen_get_tax_class_title',
    'zen_get_configuration_group_value',
    'zen_cfg_password_display',
    'currencies->format',
];

const TABLES = ['configuration', 'product_type_layout'];

function extractValuesSpan(string $line): ?string
{
    $pos = stripos($line, 'VALUES');
    if ($pos === false) {
        return null;
    }
    $i = $pos + 6;
    $len = strlen($line);
    while ($i < $len && ctype_space($line[$i])) {
        $i++;
    }
    if (!isset($line[$i]) || $line[$i] !== '(') {
        return null;
    }
    $depth = 0;
    $start = $i;
    $inString = false;
    for (; $i < $len; $i++) {
        $ch = $line[$i];
        if ($inString) {
            if ($ch === '\\') {
                $i++;
                continue;
            }
            if ($ch === "'") {
                $inString = false;
            }
            continue;
        }
        if ($ch === "'") {
            $inString = true;
            continue;
        }
        if ($ch === '(') {
            $depth++;
            continue;
        }
        if ($ch === ')') {
            $depth--;
            if ($depth === 0) {
                return substr($line, $start + 1, $i - $start - 1);
            }
        }
    }
    return null;
}

/** Splits a VALUES(...) inner span on top-level commas, respecting quoted strings. */
function splitSqlValues(string $inner): array
{
    $values = [];
    $current = '';
    $inString = false;
    $len = strlen($inner);
    for ($i = 0; $i < $len; $i++) {
        $ch = $inner[$i];
        if ($inString) {
            $current .= $ch;
            if ($ch === '\\') {
                $i++;
                if ($i < $len) {
                    $current .= $inner[$i];
                }
                continue;
            }
            if ($ch === "'") {
                $inString = false;
            }
            continue;
        }
        if ($ch === "'") {
            $inString = true;
            $current .= $ch;
            continue;
        }
        if ($ch === ',') {
            $values[] = trim($current);
            $current = '';
            continue;
        }
        $current .= $ch;
    }
    if (trim($current) !== '') {
        $values[] = trim($current);
    }
    return $values;
}

/** Unescapes a single-quoted SQL string token (backslash-escaping) to its raw content, or null if not a quoted string. */
function sqlUnquote(string $token): ?string
{
    $token = trim($token);
    if (strlen($token) < 2 || $token[0] !== "'" || $token[-1] !== "'") {
        return null;
    }
    $inner = substr($token, 1, -1);
    $out = '';
    $len = strlen($inner);
    for ($i = 0; $i < $len; $i++) {
        $ch = $inner[$i];
        if ($ch === '\\' && $i + 1 < $len) {
            $out .= $inner[$i + 1];
            $i++;
            continue;
        }
        $out .= $ch;
    }
    // A handful of legacy rows use standard SQL '' quote-doubling instead of backslash-escaping
    // for embedded quotes (e.g. "zen_cfg_select_option([''0'', ''1''], "); collapse those too so
    // the literal-array eval below sees plain PHP single quotes.
    return str_replace("''", "'", $out);
}

/** Re-escapes a raw string value for embedding as a single-quoted SQL string literal (backslash style, matching this file's convention). */
function sqlQuote(string $raw): string
{
    return "'" . addcslashes($raw, "\\'") . "'";
}

/**
 * Given the raw (unescaped) set_function string, e.g. "zen_cfg_select_option(['true', 'false'], ",
 * returns ['renderer' => string, 'params' => array] or null if the function isn't a recognized
 * core renderer, or its argument shape doesn't match what that renderer expects.
 */
function parseSetFunction(string $raw): ?array
{
    if (!preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*)$/s', $raw, $m)) {
        return null;
    }
    $funcName = $m[1];
    $rest = $m[2];

    if (!array_key_exists($funcName, RENDERER_PARAM_KEY)) {
        return null;
    }
    $paramKey = RENDERER_PARAM_KEY[$funcName];

    // Strip a single trailing comma (with optional trailing whitespace) — the stored value is a
    // partial call missing its closing value/key args, e.g. "...], " or "...],".
    $literal = rtrim($rest);
    $literal = rtrim($literal, ',');
    $literal = trim($literal);

    if ($paramKey === null) {
        if ($literal !== '') {
            return null; // unexpected trailing content for a no-arg renderer; needs manual review
        }
        return ['renderer' => $funcName, 'params' => []];
    }

    if ($literal === '') {
        return null; // expected a literal array but found none; needs manual review
    }

    $parsed = safeEvalArrayLiteral($literal);
    if ($parsed === null) {
        return null;
    }

    return ['renderer' => $funcName, 'params' => [$paramKey => $parsed]];
}

function parseUseFunction(string $raw): ?array
{
    $raw = trim($raw);
    if (!in_array($raw, FORMATTER_FUNCTIONS, true)) {
        return null;
    }
    return ['formatter' => $raw];
}

/** Evaluates a PHP array-literal string (short `[...]` or `array(...)` syntax) extracted from trusted seed SQL. */
function safeEvalArrayLiteral(string $literal): ?array
{
    // Guard against anything that isn't plausibly a plain array literal before eval'ing.
    if (!preg_match('/^(\[|array\s*\()/', $literal)) {
        return null;
    }
    try {
        $result = eval('return ' . $literal . ';');
    } catch (\Throwable) {
        return null;
    }
    return is_array($result) ? $result : null;
}

function buildRendererJson(array $payload): string
{
    // Stable key order for readable diffs/review: renderer, formatter, params.
    $ordered = [];
    if (isset($payload['renderer'])) {
        $ordered['renderer'] = $payload['renderer'];
    }
    if (isset($payload['formatter'])) {
        $ordered['formatter'] = $payload['formatter'];
    }
    $ordered['params'] = $payload['params'] ?? [];

    return json_encode($ordered, JSON_UNESCAPED_SLASHES);
}

// -----
// Main
//
$apply = in_array('--apply', $argv, true);
$sqlFile = __DIR__ . '/../../zc_install/sql/install/mysql_zencart.sql';
$original = file_get_contents($sqlFile);
$lines = explode("\n", $original);

$migrated = 0;
$skippedNoFunction = 0;
$skippedAlreadyMigrated = 0;
$skippedUnrecognized = [];
$sampleDiffs = [];

foreach ($lines as $lineNo => &$line) {
    $matchedTable = null;
    foreach (TABLES as $table) {
        if (str_starts_with($line, "INSERT INTO $table (")) {
            $matchedTable = $table;
            break;
        }
    }
    if ($matchedTable === null) {
        continue;
    }

    if (!preg_match('/^INSERT INTO ' . preg_quote($matchedTable, '/') . ' \(([^)]*)\)/', $line, $m)) {
        continue;
    }
    $cols = array_map('trim', explode(',', $m[1]));
    $valuesInner = extractValuesSpan($line);
    if ($valuesInner === null) {
        $skippedUnrecognized[] = "line " . ($lineNo + 1) . ": could not extract VALUES span";
        continue;
    }
    $vals = splitSqlValues($valuesInner);
    if (count($vals) !== count($cols)) {
        $skippedUnrecognized[] = "line " . ($lineNo + 1) . ": column/value count mismatch";
        continue;
    }
    $row = array_combine($cols, $vals);

    if (isset($row['renderer'])) {
        $skippedAlreadyMigrated++;
        continue;
    }

    $payload = [];

    if (isset($row['set_function'])) {
        $raw = sqlUnquote($row['set_function']);
        if ($raw !== null && $raw !== '') {
            $parsed = parseSetFunction($raw);
            if ($parsed !== null) {
                $payload['renderer'] = $parsed['renderer'];
                $payload['params'] = $parsed['params'];
            } else {
                $skippedUnrecognized[] = "line " . ($lineNo + 1) . ": unrecognized set_function shape: " . $raw;
            }
        }
    }

    if (isset($row['use_function'])) {
        $raw = sqlUnquote($row['use_function']);
        if ($raw !== null && $raw !== '') {
            $parsed = parseUseFunction($raw);
            if ($parsed !== null) {
                $payload['formatter'] = $parsed['formatter'];
            } else {
                $skippedUnrecognized[] = "line " . ($lineNo + 1) . ": unrecognized use_function shape: " . $raw;
            }
        }
    }

    if (empty($payload['renderer']) && empty($payload['formatter'])) {
        $skippedNoFunction++;
        continue;
    }

    $rendererJson = buildRendererJson($payload);
    $newCols = $cols;
    $newCols[] = 'renderer';
    $newVals = $vals;
    $newVals[] = sqlQuote($rendererJson);

    $newLine = "INSERT INTO $matchedTable (" . implode(', ', $newCols) . ') VALUES (' . implode(', ', $newVals) . ');';

    if (count($sampleDiffs) < 8) {
        $sampleDiffs[] = "- " . $line . "\n+ " . $newLine;
    }

    $line = $newLine;
    $migrated++;
}
unset($line);

echo "=== Migration summary ===\n";
echo "Rows migrated (renderer column added): $migrated\n";
echo "Rows already migrated (renderer column already present): $skippedAlreadyMigrated\n";
echo "Rows with no set_function/use_function: $skippedNoFunction\n";
echo "Rows with an unrecognized shape (left untouched): " . count($skippedUnrecognized) . "\n";
foreach ($skippedUnrecognized as $msg) {
    echo "  - $msg\n";
}

echo "\n=== Sample diffs (first " . count($sampleDiffs) . ") ===\n";
foreach ($sampleDiffs as $diff) {
    echo $diff . "\n\n";
}

if ($apply) {
    file_put_contents($sqlFile, implode("\n", $lines));
    echo "\nApplied: wrote $migrated migrated row(s) to $sqlFile\n";
} else {
    echo "\nDry run only — no file was modified. Re-run with --apply to write changes.\n";
}
