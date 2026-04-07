<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsSundry;

use Tests\Support\zcUnitTestCase;
use Zencart\AdminUi\Resources\ResourceResolver;
use Zencart\Traits\ObserverManager;

class ResourceResolverTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        require_once DIR_FS_CATALOG . 'includes/classes/EventDto.php';
        require_once DIR_FS_CATALOG . 'includes/classes/traits/Singleton.php';
        require_once DIR_FS_CATALOG . 'includes/classes/traits/NotifierManager.php';
        require_once DIR_FS_CATALOG . 'includes/classes/traits/ObserverManager.php';
        require_once DIR_FS_CATALOG . 'includes/classes/AdminUi/Resources/ResourceResolver.php';
    }

    public function testResolverUsesRegisteredResourceOverride(): void
    {
        $resolver = ResourceResolver::getInstance();
        $resolver->register('tax_classes', FakeResolvedResource::class);

        $this->assertSame(FakeResolvedResource::class, $resolver->resolve('tax_classes', DefaultResolvedResource::class));

        $resolver->unregister('tax_classes');
    }

    public function testResolverCanBeOverriddenByNotifier(): void
    {
        $resolver = ResourceResolver::getInstance();
        $observer = new class {
            use ObserverManager;

            public function __construct()
            {
                $this->attach($this, ['NOTIFY_ADMIN_RESOURCE_RESOLVE_START']);
            }

            public function updateNotifyAdminResourceResolveStart(&$class, $eventId, $params, &$resolvedClass)
            {
                if ($params['resourceName'] === 'plugin_manager') {
                    $resolvedClass = FakeResolvedResource::class;
                }
            }
        };

        $this->assertSame(FakeResolvedResource::class, $resolver->resolve('plugin_manager', DefaultResolvedResource::class));
    }
}

class DefaultResolvedResource
{
}

class FakeResolvedResource
{
}
