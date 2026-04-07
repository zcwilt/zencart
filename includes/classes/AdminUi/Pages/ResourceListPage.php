<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Pages;

use Zencart\AdminUi\AdminPageData;
use Zencart\Traits\NotifierManager;

class ResourceListPage
{
    use NotifierManager;

    public function __construct(
        protected string $heading,
        protected $formatter,
        protected $tableController,
        protected ?ListViewConfig $listViewConfig = null,
        protected ?ListFooterConfig $footerConfig = null
    ) {
    }

    public function build(): AdminPageData
    {
        $heading = $this->heading;
        $formatter = $this->formatter;
        $tableController = $this->tableController;
        $listViewConfig = $this->listViewConfig ?? new ListViewConfig();
        $footerConfig = $this->footerConfig ?? new ListFooterConfig();

        $this->notify(
            'NOTIFY_ADMIN_LIST_PAGE_BUILD_START',
            [],
            $heading,
            $formatter,
            $tableController,
            $listViewConfig,
            $footerConfig
        );

        $page = new AdminPageData(
            DIR_FS_ADMIN . 'includes/templates/resource_list.php',
            [
                'pageHeading' => $heading,
                'formatter' => $formatter,
                'tableController' => $tableController,
                'listViewConfig' => $listViewConfig,
                'footerConfig' => $footerConfig,
            ]
        );

        $this->notify('NOTIFY_ADMIN_LIST_PAGE_BUILD_END', [], $page);

        return $page;
    }
}
