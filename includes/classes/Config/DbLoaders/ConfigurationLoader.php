<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\Config\DbLoaders;

use Zencart\DbRepositories\AbstractQueryFactoryRepository;

/**
 * Database-backed configuration constant loader for TABLE_CONFIGURATION.
 */
class ConfigurationLoader extends AbstractQueryFactoryRepository
{
    protected array $configAsIntArray = ['SECURITY_CODE_LENGTH'];
    protected array $keepAsStringArray = ['PRODUCTS_MANUFACTURERS_STATUS'];

    public function loadConfigSettings(): void
    {
        $configs = $this->db->Execute(
            'SELECT configuration_key, configuration_value, configuration_group_id FROM ' . TABLE_CONFIGURATION
        );

        foreach ($configs as $config) {
            $key = strtoupper((string)$config['configuration_key']);
            $value = $config['configuration_value'];
            $groupId = (int)$config['configuration_group_id'];

            $convertToInt = false;
            if (in_array($key, $this->configAsIntArray, true)) {
                $convertToInt = true;
            } elseif (in_array($groupId, [2, 3], true) && !in_array($key, $this->keepAsStringArray, true)) {
                $convertToInt = true;
            }

            if ($convertToInt) {
                $value = (int)$value;
            }

            if (!defined($key)) {
                define($key, $value);
            }
        }
    }
}
