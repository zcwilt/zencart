<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\ConfigField;

/**
 * Resolves a configuration/product_type_layout row's `renderer` JSON column into
 * rendered HTML or formatted display text via the ConfigFieldRegistry, returning
 * null whenever the row hasn't opted into the new system (or resolution fails) so
 * callers fall back to the legacy set_function/use_function eval() path unchanged.
 *
 * Shared by all admin call sites (admin/configuration.php, admin/modules.php,
 * admin/product_types.php) so their resolution logic can't drift from each other.
 * @since ZC v3.0.0
 */
class ConfigFieldRowResolver
{
    public function __construct(protected ConfigFieldRegistry $registry)
    {
    }

    /**
     * @param ?string $rendererJson The row's `renderer` column value, e.g.
     *                               '{"renderer":"zen_cfg_select_option","params":{"options":["true","false"]}}'.
     */
    public function renderField(?string $rendererJson, string $value, string $fieldName): ?string
    {
        $payload = $this->decode($rendererJson);
        if ($payload === null || empty($payload['renderer'])) {
            return null;
        }

        try {
            return $this->registry->render($payload['renderer'], $value, $fieldName, $payload['params'] ?? []);
        } catch (\Throwable) {
            return null;
        }
    }

    public function formatField(?string $rendererJson, string $value): ?string
    {
        $payload = $this->decode($rendererJson);
        if ($payload === null || empty($payload['formatter'])) {
            return null;
        }

        try {
            return $this->registry->format($payload['formatter'], $value, $payload['params'] ?? []);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Advisory check: does this row's renderer/formatter self-declare as sensitive
     * (SensitiveConfigFieldInterface)? Callers should OR this with
     * zcObserverLogEventListener::isSensitiveFieldName() on the configuration_key —
     * this only covers rows that opted into the new system, not legacy ones.
     */
    public function isSensitive(?string $rendererJson): bool
    {
        $payload = $this->decode($rendererJson);
        if ($payload === null) {
            return false;
        }

        if (!empty($payload['renderer']) && $this->registry->resolveRenderer($payload['renderer']) instanceof SensitiveConfigFieldInterface) {
            return true;
        }

        return !empty($payload['formatter']) && $this->registry->resolveFormatter($payload['formatter']) instanceof SensitiveConfigFieldInterface;
    }

    protected function decode(?string $rendererJson): ?array
    {
        if ($rendererJson === null || $rendererJson === '') {
            return null;
        }

        $decoded = json_decode($rendererJson, true);
        return is_array($decoded) ? $decoded : null;
    }
}
