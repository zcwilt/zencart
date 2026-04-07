<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources;

use Zencart\Traits\NotifierManager;
use Zencart\Traits\Singleton;

class ResourceResolver
{
    use NotifierManager;
    use Singleton;

    protected array $registry = [];

    public function register(string $resourceName, string $resourceClass): self
    {
        $this->registry[$resourceName] = $resourceClass;
        return $this;
    }

    public function unregister(string $resourceName): self
    {
        unset($this->registry[$resourceName]);
        return $this;
    }

    public function resolve(string $resourceName, string $defaultClass): string
    {
        $resolvedClass = $this->registry[$resourceName] ?? $defaultClass;
        $this->notify('NOTIFY_ADMIN_RESOURCE_RESOLVE_START', ['resourceName' => $resourceName, 'defaultClass' => $defaultClass], $resolvedClass);
        $this->notify('NOTIFY_ADMIN_RESOURCE_RESOLVE_END', ['resourceName' => $resourceName, 'defaultClass' => $defaultClass], $resolvedClass);
        return $resolvedClass;
    }
}
