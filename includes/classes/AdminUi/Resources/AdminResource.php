<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources;

use Zencart\AdminUi\AdminPageData;
use Zencart\Request\Request;
use Zencart\Traits\NotifierManager;

abstract class AdminResource
{
    use NotifierManager;

    public function __construct(
        protected Request $request,
        protected $messageStack
    ) {
    }

    protected function notifyBuildPageStart(array $context = []): array
    {
        $context['resourceClass'] = static::class;
        $this->notify('NOTIFY_ADMIN_RESOURCE_BUILD_PAGE_START', [], $context);
        return $context;
    }

    protected function notifyBuildPageEnd(AdminPageData $page, array $context = []): AdminPageData
    {
        $context['resourceClass'] = static::class;
        $this->notify('NOTIFY_ADMIN_RESOURCE_BUILD_PAGE_END', $context, $page);
        return $page;
    }

    abstract public function buildPage(): AdminPageData;
}
