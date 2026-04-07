<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsSundry;

use Tests\Support\zcUnitTestCase;

class Psr4AutoloadAdminPrefixTest extends zcUnitTestCase
{
    public function testPsr4AutoloadRegistersAdminNamespacePrefix(): void
    {
        if (!defined('DIR_FS_CATALOG')) {
            define('DIR_FS_CATALOG', getcwd() . '/');
        }
        if (!defined('DIR_WS_CLASSES')) {
            define('DIR_WS_CLASSES', 'includes/classes/');
        }
        if (!defined('DIR_FS_ADMIN')) {
            define('DIR_FS_ADMIN', getcwd() . '/admin/');
        }

        $psr4Autoloader = new class {
            public array $prefixes = [];
            public array $classFiles = [];

            public function addPrefix(string $prefix, string $path): void
            {
                $this->prefixes[$prefix] = $path;
            }

            public function setClassFile(string $class, string $path): void
            {
                $this->classFiles[$class] = $path;
            }
        };

        require DIR_FS_CATALOG . 'includes/psr4Autoload.php';

        $this->assertArrayHasKey('Zencart\AdminUi', $psr4Autoloader->prefixes);
        $this->assertSame(
            DIR_FS_CATALOG . DIR_WS_CLASSES . 'AdminUi',
            $psr4Autoloader->prefixes['Zencart\AdminUi']
        );
    }
}
