<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *
 * Instantiates the shared ConfigFieldRegistry (see includes/classes/ConfigField/)
 * and populates it with core's renderer/formatter adapters. Runs after
 * init_general_funcs.php (breakpoint 40, which loads the zen_cfg_* / zen_get_*
 * functions the adapters delegate to) and alongside configurationValidation
 * (breakpoint 175), so it's available to every admin page that renders
 * configuration fields.
 *
 * @since ZC v3.0.0
 */
$autoLoadConfig[176][] = [
    'autoType' => 'classInstantiate',
    'className' => 'Zencart\ConfigField\ConfigFieldRegistry',
    'objectName' => 'zcConfigFieldRegistry',
];
$autoLoadConfig[176][] = [
    'autoType' => 'objectMethod',
    'objectName' => 'zcConfigFieldRegistry',
    'methodName' => 'bootstrapCore',
];
