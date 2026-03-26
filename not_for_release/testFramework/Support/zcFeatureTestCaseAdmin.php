<?php
/**
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Support;

/**
 *
 */
abstract class zcFeatureTestCaseAdmin extends zcFeatureTestCase
{
    protected $context = 'admin';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::setUpFeatureTestContext('admin');
    }
}
