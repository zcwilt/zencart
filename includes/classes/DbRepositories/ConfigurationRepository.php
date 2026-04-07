<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\DbRepositories;

use Zencart\Config\DbLoaders\ConfigurationLoader;

/**
 * @deprecated Use \Zencart\Config\DbLoaders\ConfigurationLoader for config-loading responsibilities.
 *
 * @since ZC v2.2.0
 */
class ConfigurationRepository extends ConfigurationLoader
{
    /**
     * @since ZC v2.2.0
     */
    public function getByKey(string $configurationKey): ?array
    {
        $configurationKey = $this->db->prepare_input($configurationKey);
        return $this->fetchFirstRow(
            "SELECT configuration_id, configuration_key, configuration_value FROM " . TABLE_CONFIGURATION .
            " WHERE configuration_key = '" . $configurationKey . "' LIMIT 1"
        );
    }

    /**
     * @since ZC v2.2.0
     */
    public function updateValueByKey(string $configurationKey, string $configurationValue): int
    {
        $configurationKey = $this->db->prepare_input($configurationKey);
        $configurationValue = $this->db->prepare_input($configurationValue);

        $this->db->Execute(
            "UPDATE " . TABLE_CONFIGURATION .
            " SET configuration_value = '" . $configurationValue . "'" .
            " WHERE configuration_key = '" . $configurationKey . "'"
        );

        return $this->db->affectedRows();
    }
}
