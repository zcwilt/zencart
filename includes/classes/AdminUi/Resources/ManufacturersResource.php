<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources;

use Zencart\AdminUi\AdminPageData;
use Zencart\AdminUi\Resources\Manufacturers\ManufacturersController;
use Zencart\AdminUi\Resources\Manufacturers\ManufacturersDataFormatter;
use Zencart\AdminUi\Resources\Manufacturers\ManufacturersDataSource;
use Zencart\ViewBuilders\DerivedItemsManager;
use Zencart\ViewBuilders\TableViewDefinition;

class ManufacturersResource extends AdminResource
{
    public function buildPage(): AdminPageData
    {
        $context = $this->notifyBuildPageStart();

        $table = new TableViewDefinition([
            'colKey' => 'manufacturers_id',
            'colKeyName' => 'mID',
            'paginated' => false,
            'selectedRowAction' => 'edit',
            'searchPlaceholder' => 'Search ' . TABLE_HEADING_MANUFACTURERS,
            'columns' => [
                'manufacturers_id' => ['title' => TABLE_HEADING_ID, 'class' => 'text-center'],
                'manufacturers_name' => [
                    'title' => TABLE_HEADING_MANUFACTURERS,
                    'searchable' => true,
                ],
                'featured' => [
                    'title' => TABLE_HEADING_MANUFACTURER_FEATURED,
                    'class' => '',
                    'derivedItem' => [
                        'type' => 'closure',
                        'method' => static function ($row): string {
                            return !empty($row['featured'] ?? $row->featured ?? null) ? '<strong>' . TEXT_YES . '</strong>' : TEXT_NO;
                        },
                    ],
                ],
            ],
        ]);

        $dataSource = new ManufacturersDataSource($table);
        $query = $dataSource->processRequest($this->request);
        $queryResults = $dataSource->processQuery($query);
        $formatter = new ManufacturersDataFormatter(
            $this->request,
            $table,
            $queryResults,
            new DerivedItemsManager(),
            $dataSource->getLinkPage()
        );

        $controller = new ManufacturersController($this->request, $this->messageStack, $table, $formatter);
        $controller->processRequest();

        $page = new AdminPageData(
            DIR_FS_ADMIN . 'includes/templates/manufacturers_resource.php',
            [
                'formatter' => $formatter,
                'tableController' => $controller,
                'manufacturersSplit' => $dataSource->getSplitResults(),
                'manufacturersQueryNumRows' => $dataSource->getQueryNumRows(),
                'currentPage' => $dataSource->getCurrentPage(),
                'pageHeading' => HEADING_TITLE,
            ]
        );

        return $this->notifyBuildPageEnd($page, $context);
    }
}
