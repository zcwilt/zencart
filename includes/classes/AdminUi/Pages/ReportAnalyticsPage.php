<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Pages;

use Zencart\AdminUi\AdminPageData;
use Zencart\Traits\NotifierManager;

class ReportAnalyticsPage
{
    use NotifierManager;

    public function __construct(
        protected string $heading,
        protected array $navigationLinks,
        protected array $chartConfigs,
        protected array $tableHeaders,
        protected array $tableRows,
        protected array $summaryRows,
        protected array $filterRows,
        protected ?string $previousLink = null,
        protected ?string $nextLink = null
    ) {
    }

    public function build(): AdminPageData
    {
        $heading = $this->heading;
        $navigationLinks = $this->navigationLinks;
        $chartConfigs = $this->chartConfigs;
        $tableHeaders = $this->tableHeaders;
        $tableRows = $this->tableRows;
        $summaryRows = $this->summaryRows;
        $filterRows = $this->filterRows;
        $previousLink = $this->previousLink;
        $nextLink = $this->nextLink;

        $this->notify(
            'NOTIFY_ADMIN_REPORT_ANALYTICS_PAGE_BUILD_START',
            [],
            $heading,
            $navigationLinks,
            $chartConfigs,
            $tableHeaders,
            $tableRows,
            $summaryRows,
            $filterRows,
            $previousLink,
            $nextLink
        );

        $page = new AdminPageData(
            DIR_FS_ADMIN . 'includes/templates/report_analytics.php',
            [
                'pageHeading' => $heading,
                'navigationLinks' => $navigationLinks,
                'chartConfigs' => $chartConfigs,
                'tableHeaders' => $tableHeaders,
                'tableRows' => $tableRows,
                'summaryRows' => $summaryRows,
                'filterRows' => $filterRows,
                'previousLink' => $previousLink,
                'nextLink' => $nextLink,
            ]
        );

        $this->notify('NOTIFY_ADMIN_REPORT_ANALYTICS_PAGE_BUILD_END', [], $page);

        return $page;
    }
}
