<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources;

use Zencart\AdminUi\AdminPageData;
use Zencart\AdminUi\Resources\Countries\CountriesController;
use Zencart\AdminUi\Resources\Countries\CountriesDataFormatter;
use Zencart\AdminUi\Resources\Countries\CountriesDataSource;
use Zencart\DbRepositories\CountriesRepository;
use Zencart\ViewBuilders\DerivedItemsManager;
use Zencart\ViewBuilders\TableViewDefinition;

class CountriesResource extends AdminResource
{
    public function buildPage(): AdminPageData
    {
        $context = $this->notifyBuildPageStart();

        $table = new TableViewDefinition([
            'colKey' => 'countries_id',
            'colKeyName' => 'cID',
            'paginated' => false,
            'selectedRowAction' => 'edit',
            'searchPlaceholder' => 'Search ' . TABLE_HEADING_COUNTRY_NAME,
            'defaultSort' => [
                'column' => 'countries_name',
                'direction' => 'asc',
            ],
            'filters' => [
                'status' => [
                    'parameter' => 'status_filter',
                    'type' => 'select',
                    'label' => TABLE_HEADING_COUNTRY_STATUS,
                    'options' => [
                        '' => TEXT_COUNTRIES_FILTER_STATUS_ALL,
                        '1' => TEXT_COUNTRIES_FILTER_STATUS_ACTIVE,
                        '0' => TEXT_COUNTRIES_FILTER_STATUS_INACTIVE,
                    ],
                ],
            ],
            'columns' => [
                'countries_id' => ['title' => '', 'class' => 'hidden'],
                'countries_name' => [
                    'title' => TABLE_HEADING_COUNTRY_NAME,
                    'class' => 'col-sm-6',
                    'sortable' => true,
                    'searchable' => true,
                    'sortKey' => 'countries_name',
                    'defaultDirection' => 'asc',
                ],
                'countries_iso_code_2' => ['title' => TEXT_INFO_COUNTRY_CODE_2, 'class' => 'text-center'],
                'countries_iso_code_3' => ['title' => TEXT_INFO_COUNTRY_CODE_3, 'class' => 'text-center'],
                'status' => ['title' => TABLE_HEADING_COUNTRY_STATUS, 'class' => 'text-center dataTableButtonCell'],
            ],
        ]);

        $dataSource = new CountriesDataSource($table);
        $query = $dataSource->processRequest($this->request);
        $queryResults = $dataSource->processQuery($query);
        $formatter = new CountriesDataFormatter(
            $this->request,
            $table,
            $queryResults,
            new DerivedItemsManager(),
            $dataSource->getCurrentPage(),
            $dataSource->isAlphabeticMode()
        );

        global $db;

        $controller = new CountriesController(
            $this->request,
            $this->messageStack,
            $table,
            $formatter,
            new CountriesRepository($db)
        );
        $controller->processRequest();

        $page = new AdminPageData(
            DIR_FS_ADMIN . 'includes/templates/countries_resource.php',
            [
                'formatter' => $formatter,
                'tableController' => $controller,
                'countriesSplit' => $dataSource->getSplitResults(),
                'countriesQueryNumRows' => $dataSource->getQueryNumRows(),
                'currentPage' => $dataSource->getCurrentPage(),
                'pageParameter' => $dataSource->getPageParameter(),
                'pageHeading' => HEADING_TITLE,
                'pageTopLinkHtml' => ISO_COUNTRY_CODES_LINK,
            ]
        );

        return $this->notifyBuildPageEnd($page, $context);
    }
}
