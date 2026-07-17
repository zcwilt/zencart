<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsConfigField;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\Support\zcUnitTestCase;
use Zencart\ConfigField\ConfigFieldRegistry;

/**
 * Proves the Phase 4 core seed-data migration (not_for_release/tools/migrate_seed_config_functions.php)
 * changed nothing about what gets rendered: for every row in
 * zc_install/sql/install/mysql_zencart.sql / product_type_layout that now carries a `renderer` JSON
 * value AND whose wrapped renderer function is DB-free, this reads the row's own stored
 * `configuration_value` and asserts the new registry path produces byte-identical HTML to running
 * the legacy `set_function` string through the exact eval() mechanics admin/configuration.php used
 * before the migration.
 *
 * DB-dependent renderers (pull-down country/zone/tax-class/order-status lists, coupon select) are
 * out of scope here — Phase 1's RenderersTest/FormattersTest already prove those adapters are pure
 * delegation to the same global function, and the structural cross-check in the migration tool's
 * own dry run (function-name + argument-shape) covers the migration-specific risk for those rows.
 */
#[RunTestsInSeparateProcesses]
class SeedMigrationRegressionTest extends zcUnitTestCase
{
    private const DB_FREE_RENDERERS = [
        'zen_cfg_select_option',
        'zen_cfg_select_drop_down',
        'zen_cfg_select_multioption',
        'zen_cfg_select_multioption_pairs',
        'zen_cfg_textarea',
        'zen_cfg_textarea_small',
        'zen_cfg_password_input',
        'zen_cfg_read_only',
    ];

    public function setUp(): void
    {
        parent::setUp();
        if (!defined('CHARSET')) {
            define('CHARSET', 'utf-8');
        }
        require_once DIR_FS_ADMIN . DIR_WS_FUNCTIONS . 'general.php';
        require_once DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'functions_strings.php';
        require_once DIR_FS_ADMIN . 'includes/functions/html_output.php';
    }

    public function testMigratedDbFreeRowsRenderIdenticallyToTheLegacyEvalPath(): void
    {
        $registry = new ConfigFieldRegistry();
        $registry->bootstrapCore();

        $rows = $this->parseSeedRows();
        $this->assertGreaterThan(
            100,
            count($rows),
            'sanity check: expected a large number of migrated seed rows to compare'
        );

        $checked = 0;
        foreach ($rows as $row) {
            $decoded = json_decode($row['renderer'], true);
            if (!isset($decoded['renderer']) || !in_array($decoded['renderer'], self::DB_FREE_RENDERERS, true)) {
                continue;
            }
            if (empty($row['set_function'])) {
                continue;
            }

            $sampleValue = $row['configuration_value'];
            $fieldName = 'cfg_test';

            // Legacy path: byte-for-byte what admin/configuration.php did before the migration.
            $safeValue = addslashes(htmlspecialchars($sampleValue, ENT_COMPAT, CHARSET, true));
            $setFunctionCall = $row['set_function'] . '\'' . $safeValue . '\', \'' . $fieldName . '\')';
            eval('$legacyHtml = ' . $setFunctionCall . ';');

            // New path.
            $newHtml = $registry->render(
                $decoded['renderer'],
                htmlspecialchars($sampleValue, ENT_COMPAT, CHARSET, true),
                $fieldName,
                $decoded['params'] ?? []
            );

            $this->assertSame(
                $legacyHtml,
                $newHtml,
                "Row '{$row['configuration_key']}' ({$decoded['renderer']}) diverged between legacy eval() and the new registry path"
            );
            $checked++;
        }

        $this->assertGreaterThan(50, $checked, 'sanity check: expected a substantial number of DB-free rows to actually be compared');
    }

    /**
     * The committed zc_install/sql/install/mysql_zencart.sql is already migrated (every
     * set_function/use_function row also carries its renderer column/value). The migration
     * tool is purely additive and never strips set_function/use_function, so re-running it
     * (e.g. a developer running it again out of habit, or a future edit reintroducing rows)
     * must recognize already-migrated rows and skip them -- not append a second `renderer`
     * column, which would make the resulting INSERT statement's column list invalid SQL
     * (`..., renderer, renderer) VALUES (..., '...', '...')`).
     *
     * Runs the real tool, dry-run only (no --apply, so the committed seed file is never
     * touched), directly against the actual committed seed file -- the exact scenario the bug
     * occurred in.
     */
    public function testMigrationToolIsIdempotentAgainstTheAlreadyMigratedSeedFile(): void
    {
        $script = DIR_FS_CATALOG . 'not_for_release/tools/migrate_seed_config_functions.php';
        exec('php ' . escapeshellarg($script) . ' 2>&1', $output, $exitCode);
        $outputText = implode("\n", $output);

        $this->assertSame(0, $exitCode, $outputText);
        $this->assertStringContainsString('Rows migrated (renderer column added): 0', $outputText, $outputText);
        $this->assertDoesNotMatchRegularExpression('/renderer,\s*renderer/', $outputText, $outputText);

        if (!preg_match('/Rows already migrated \(renderer column already present\): (\d+)/', $outputText, $m)) {
            $this->fail("Expected an 'already migrated' count in the tool's output:\n" . $outputText);
        }
        $this->assertGreaterThan(
            100,
            (int) $m[1],
            'sanity check: expected a large number of already-migrated rows to have been recognized and skipped'
        );
    }

    /** @return array<int, array{configuration_key: string, configuration_value: string, set_function: ?string, renderer: string}> */
    private function parseSeedRows(): array
    {
        $file = DIR_FS_CATALOG . 'zc_install/sql/install/mysql_zencart.sql';
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $rows = [];

        foreach (['configuration', 'product_type_layout'] as $table) {
            foreach ($lines as $line) {
                if (!str_starts_with($line, "INSERT INTO $table (")) {
                    continue;
                }
                if (!preg_match('/^INSERT INTO ' . preg_quote($table, '/') . ' \(([^)]*)\)/', $line, $m)) {
                    continue;
                }
                $cols = array_map('trim', explode(',', $m[1]));
                $valuesInner = $this->extractValuesSpan($line);
                if ($valuesInner === null) {
                    continue;
                }
                $vals = $this->splitSqlValues($valuesInner);
                if (count($vals) !== count($cols)) {
                    continue;
                }
                $row = array_combine($cols, $vals);
                if (!isset($row['renderer'])) {
                    continue;
                }
                $rendererRaw = $this->sqlUnquote($row['renderer']);
                if ($rendererRaw === null) {
                    continue;
                }

                $rows[] = [
                    'configuration_key' => $this->sqlUnquote($row['configuration_key'] ?? "''") ?? '',
                    'configuration_value' => $this->sqlUnquote($row['configuration_value'] ?? "''") ?? '',
                    'set_function' => isset($row['set_function']) ? $this->sqlUnquote($row['set_function']) : null,
                    'renderer' => $rendererRaw,
                ];
            }
        }

        return $rows;
    }

    private function extractValuesSpan(string $line): ?string
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

    private function splitSqlValues(string $inner): array
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

    private function sqlUnquote(string $token): ?string
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
        return str_replace("''", "'", $out);
    }
}
