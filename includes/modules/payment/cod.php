<?php
/**
 * COD Payment Module
 *
 * @copyright Copyright 2003-2023 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Scott C Wilson 2022 Oct 16 Modified in v1.5.8a $
 */

use Zencart\ModuleSupport\PaymentModuleAbstract;
use Zencart\ModuleSupport\PaymentModuleContract;
use Zencart\ModuleSupport\PaymentModuleConcerns;

class cod extends PaymentModuleAbstract implements PaymentModuleContract
{
    use PaymentModuleConcerns;

    public string $version = '1.0.0';
    public string $code = 'cod';
    protected function addCustomConfigurationKeys(): array
    {
        $configKeys = [];
        return $configKeys;
    }
}
