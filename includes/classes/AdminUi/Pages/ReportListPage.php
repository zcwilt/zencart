<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Pages;

use Zencart\AdminUi\AdminPageData;
use Zencart\Traits\NotifierManager;

class ReportListPage
{
    use NotifierManager;

    public function __construct(
        protected string $heading,
        protected $formatter,
        protected ?ListFooterConfig $footerConfig = null,
        protected ?ReportViewConfig $viewConfig = null
    ) {
    }

    public function build(): AdminPageData
    {
        $heading = $this->heading;
        $formatter = $this->formatter;
        $footerConfig = $this->footerConfig ?? new ListFooterConfig();
        $viewConfig = $this->viewConfig ?? new ReportViewConfig();

        $this->notify(
            'NOTIFY_ADMIN_REPORT_LIST_PAGE_BUILD_START',
            [],
            $heading,
            $formatter,
            $footerConfig,
            $viewConfig
        );

        $page = new AdminPageData(
            DIR_FS_ADMIN . 'includes/templates/report_list.php',
            [
                'pageHeading' => $heading,
                'formatter' => $formatter,
                'footerConfig' => $footerConfig,
                'reportViewConfig' => $viewConfig,
            ]
        );

        $this->notify('NOTIFY_ADMIN_REPORT_LIST_PAGE_BUILD_END', [], $page);

        return $page;
    }
}
