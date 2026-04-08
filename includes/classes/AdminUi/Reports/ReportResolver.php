<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Reports;

use Zencart\Traits\NotifierManager;
use Zencart\Traits\Singleton;

class ReportResolver
{
    use NotifierManager;
    use Singleton;

    protected array $registry = [];

    public function register(string $reportName, string $reportClass): self
    {
        $this->registry[$reportName] = $reportClass;
        return $this;
    }

    public function unregister(string $reportName): self
    {
        unset($this->registry[$reportName]);
        return $this;
    }

    public function resolve(string $reportName, string $defaultClass): string
    {
        $resolvedClass = $this->registry[$reportName] ?? $defaultClass;
        $this->notify('NOTIFY_ADMIN_REPORT_RESOLVE_START', ['reportName' => $reportName, 'defaultClass' => $defaultClass], $resolvedClass);
        $this->notify('NOTIFY_ADMIN_REPORT_RESOLVE_END', ['reportName' => $reportName, 'defaultClass' => $defaultClass], $resolvedClass);
        return $resolvedClass;
    }
}
