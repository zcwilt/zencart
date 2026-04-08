<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Reports;

use Zencart\AdminUi\AdminPageData;
use Zencart\AdminUi\Pages\ReportListPageBuilder;
use Zencart\AdminUi\Pages\ReportViewConfig;

class StatsProductsPurchasedReport extends AdminReport
{
    public function buildPage(): AdminPageData
    {
        $context = $this->notifyBuildPageStart();

        global $db;

        $productsFilter = $this->normalizeProductsFilter($this->request->string('products_filter', ''));
        $productsFilterNameModel = trim($this->request->string('products_filter_name_model', ''));

        if ($productsFilter !== '' || $productsFilterNameModel !== '') {
            $page = $this->buildDetailPage($db, $productsFilter, $productsFilterNameModel);
            return $this->notifyBuildPageEnd($page, $context);
        }

        $page = $this->buildSummaryPage($db);
        return $this->notifyBuildPageEnd($page, $context);
    }

    protected function buildSummaryPage($db): AdminPageData
    {
        $productsQueryRaw = "SELECT SUM(products_quantity) AS products_ordered, products_name, products_id
                               FROM " . TABLE_ORDERS_PRODUCTS . "
                           GROUP BY products_id, products_name
                           ORDER BY products_ordered DESC, products_name";

        $productsQueryNumRows = 0;
        $productsSplit = new \splitPageResults($this->request->integer('page', 1), MAX_DISPLAY_SEARCH_RESULTS_REPORTS, $productsQueryRaw, $productsQueryNumRows);
        $products = $db->Execute($productsQueryRaw);

        $rows = [];
        foreach ($products as $product) {
            $productLink = $this->productEditLink((int) $product['products_id']);
            $drillDownLink = zen_href_link(FILENAME_STATS_PRODUCTS_PURCHASED, 'products_filter=' . $product['products_id']);

            $rows[] = [
                '_rowLink' => [
                    'value' => '',
                    'class' => '',
                    'original' => $productLink,
                ],
                'products_id' => [
                    'value' => '<a href="' . $drillDownLink . '">' . $product['products_id'] . '</a>',
                    'class' => 'text-right',
                    'original' => (int) $product['products_id'],
                ],
                'products_name' => [
                    'value' => '<a href="' . $productLink . '">' . $product['products_name'] . '</a>',
                    'class' => '',
                    'original' => $product['products_name'],
                ],
                'products_ordered' => [
                    'value' => $product['products_ordered'],
                    'class' => 'text-center',
                    'original' => (int) $product['products_ordered'],
                ],
            ];
        }

        $formatter = new ReportTableFormatter(
            [
                ['headerClass' => 'dataTableHeadingContent right', 'title' => TABLE_HEADING_NUMBER],
                ['headerClass' => 'dataTableHeadingContent', 'title' => TABLE_HEADING_PRODUCTS],
                ['headerClass' => 'dataTableHeadingContent text-center', 'title' => TABLE_HEADING_PURCHASED . '&nbsp;'],
            ],
            $rows,
            static fn(array $row): string => (string) $row['_rowLink']['original']
        );

        return (new ReportListPageBuilder(
            $this->request,
            HEADING_TITLE,
            $formatter
        ))
            ->withPagination(
                $productsQueryRaw,
                TEXT_DISPLAY_NUMBER_OF_PRODUCTS,
                MAX_DISPLAY_SEARCH_RESULTS_REPORTS
            )
            ->withViewConfig(new ReportViewConfig(
                $this->buildSearchFormsHtml('', '')
            ))
            ->build();
    }

    protected function buildDetailPage($db, string $productsFilter, string $productsFilterNameModel): AdminPageData
    {
        if ($productsFilter !== '') {
            $detailQuery = "SELECT o.customers_id, op.orders_id, op.products_id, op.products_quantity, op.products_name, op.products_model,
                                   o.customers_name, o.customers_company, o.customers_email_address, o.date_purchased
                              FROM " . TABLE_ORDERS . " o,
                                   " . TABLE_ORDERS_PRODUCTS . " op
                             WHERE op.products_id IN (" . $productsFilter . ")
                               AND op.orders_id = o.orders_id
                          ORDER BY op.products_id, o.date_purchased DESC";
        } else {
            $safeFilterNameModel = zen_db_input(zen_db_prepare_input($productsFilterNameModel));
            $detailQuery = "SELECT o.customers_id, op.orders_id, op.products_id, op.products_quantity, op.products_name, op.products_model,
                                   o.customers_name, o.customers_company, o.customers_email_address, o.date_purchased
                              FROM " . TABLE_ORDERS . " o,
                                   " . TABLE_ORDERS_PRODUCTS . " op
                             WHERE ((op.products_model LIKE '%" . $safeFilterNameModel . "%')
                                OR (op.products_name LIKE '%" . $safeFilterNameModel . "%'))
                               AND op.orders_id = o.orders_id
                          ORDER BY op.products_id, o.date_purchased DESC";
        }

        $detailQueryNumRows = 0;
        $detailSplit = new \splitPageResults($this->request->integer('page', 1), MAX_DISPLAY_SEARCH_RESULTS_REPORTS, $detailQuery, $detailQueryNumRows);
        $detailRows = $db->Execute($detailQuery);

        $rows = [];
        foreach ($detailRows as $ordersProducts) {
            $productLink = $this->productEditLink((int) $ordersProducts['products_id']);
            $customersLink = zen_href_link(
                FILENAME_CUSTOMERS,
                $this->buildQueryStringExcluding(['cID', 'action', 'page', 'products_filter']) . 'cID=' . $ordersProducts['customers_id'] . '&action=edit',
                'NONSSL'
            );
            $ordersLink = zen_href_link(
                FILENAME_ORDERS,
                $this->buildQueryStringExcluding(['oID', 'action', 'page', 'products_filter']) . 'oID=' . $ordersProducts['orders_id'] . '&action=edit',
                'NONSSL'
            );

            $customerInfo = $ordersProducts['customers_name']
                . ($ordersProducts['customers_company'] !== '' ? '<br>' . zen_output_string_protected($ordersProducts['customers_company']) : '')
                . '<br>' . $ordersProducts['customers_email_address'];

            $rows[] = [
                'customers_id' => [
                    'value' => '<a href="' . $customersLink . '">' . $ordersProducts['customers_id'] . '</a>',
                    'class' => '',
                    'original' => (int) $ordersProducts['customers_id'],
                ],
                'orders_id' => [
                    'value' => '<a href="' . $ordersLink . '">' . $ordersProducts['orders_id'] . '</a>',
                    'class' => '',
                    'original' => (int) $ordersProducts['orders_id'],
                ],
                'date_purchased' => [
                    'value' => zen_date_short($ordersProducts['date_purchased']),
                    'class' => '',
                    'original' => $ordersProducts['date_purchased'],
                ],
                'customers_info' => [
                    'value' => $customerInfo,
                    'class' => '',
                    'original' => strip_tags(str_replace('<br>', ' ', $customerInfo)),
                ],
                'products_quantity' => [
                    'value' => $ordersProducts['products_quantity'],
                    'class' => 'text-center',
                    'original' => (int) $ordersProducts['products_quantity'],
                ],
                'products_name' => [
                    'value' => '<a href="' . $productLink . '">' . $ordersProducts['products_name'] . '</a>',
                    'class' => 'text-center',
                    'original' => $ordersProducts['products_name'],
                ],
                'products_model' => [
                    'value' => $ordersProducts['products_model'],
                    'class' => 'text-center',
                    'original' => $ordersProducts['products_model'],
                ],
            ];
        }

        $formatter = new ReportTableFormatter(
            [
                ['headerClass' => 'dataTableHeadingContent right', 'title' => TABLE_HEADING_CUSTOMERS_ID],
                ['headerClass' => 'dataTableHeadingContent', 'title' => TABLE_HEADING_ORDERS_ID],
                ['headerClass' => 'dataTableHeadingContent', 'title' => TABLE_HEADING_ORDERS_DATE_PURCHASED],
                ['headerClass' => 'dataTableHeadingContent', 'title' => TABLE_HEADING_CUSTOMERS_INFO],
                ['headerClass' => 'dataTableHeadingContent text-center', 'title' => TABLE_HEADING_PRODUCTS_QUANTITY],
                ['headerClass' => 'dataTableHeadingContent text-center', 'title' => TABLE_HEADING_PRODUCTS_NAME],
                ['headerClass' => 'dataTableHeadingContent text-center', 'title' => TABLE_HEADING_PRODUCTS_MODEL],
            ],
            $rows
        );

        return (new ReportListPageBuilder(
            $this->request,
            HEADING_TITLE,
            $formatter
        ))
            ->withPagination(
                $detailQuery,
                TEXT_DISPLAY_NUMBER_OF_PRODUCTS,
                MAX_DISPLAY_SEARCH_RESULTS_REPORTS,
                MAX_DISPLAY_PAGE_LINKS,
                'page',
                $this->buildDetailLinkParameters()
            )
            ->withViewConfig(new ReportViewConfig(
                $this->buildSearchFormsHtml($productsFilter, $productsFilterNameModel),
                $rows === [] ? '<div class="row"><div class="col-xs-12"><p class="text-center">' . NONE . '</p></div></div>' : ''
            ))
            ->build();
    }

    protected function buildSearchFormsHtml(string $productsFilter, string $productsFilterNameModel): string
    {
        $filterNotice = '';
        if ($productsFilter !== '') {
            $filterNotice = '<br>' . TEXT_INFO_SEARCH_DETAIL_FILTER . $productsFilter
                . '<br><a href="' . zen_href_link(FILENAME_STATS_PRODUCTS_PURCHASED, '', 'NONSSL') . '" class="btn btn-default btn-xs">' . IMAGE_RESET . '</a>';
        }

        $filterNameNotice = '';
        if ($productsFilterNameModel !== '') {
            $filterNameNotice = '<br>' . TEXT_INFO_SEARCH_DETAIL_FILTER . zen_db_prepare_input($productsFilterNameModel)
                . '<br><a href="' . zen_href_link(FILENAME_STATS_PRODUCTS_PURCHASED, '', 'NONSSL') . '" class="btn btn-default btn-xs">' . IMAGE_RESET . '</a>';
        }

        return '<div class="row"><div class="col-sm-offset-6 col-sm-6">'
            . zen_draw_form('search', FILENAME_STATS_PRODUCTS_PURCHASED, '', 'get', 'class="form-horizontal"', true)
            . zen_hide_session_id()
            . zen_draw_label(HEADING_TITLE_SEARCH_DETAIL_REPORTS, 'products_filter', 'class="control-label col-sm-9"')
            . '<div class="col-sm-3">' . zen_draw_input_field('products_filter', '', 'class="form-control"') . '</div>'
            . $filterNotice
            . '</form><br>'
            . zen_draw_form('search', FILENAME_STATS_PRODUCTS_PURCHASED, '', 'get', 'class="form-horizontal"', true)
            . zen_hide_session_id()
            . zen_draw_label(HEADING_TITLE_SEARCH_DETAIL_REPORTS_NAME_MODEL, 'products_filter_name_model', 'class="control-label col-sm-9"')
            . '<div class="col-sm-3">' . zen_draw_input_field('products_filter_name_model', '', 'class="form-control"') . '</div>'
            . $filterNameNotice
            . '</form></div></div>';
    }

    protected function normalizeProductsFilter(string $productsFilter): string
    {
        $productsFilter = str_replace(' ', ',', $productsFilter);
        $productsFilter = str_replace(',,', ',', $productsFilter);
        $productsFilter = preg_replace('/[^0-9,]/', '', $productsFilter) ?? '';
        return zen_db_input(zen_db_prepare_input($productsFilter));
    }

    protected function productEditLink(int $productsId): string
    {
        $cPath = zen_get_product_path($productsId);
        $productType = zen_get_products_type($productsId);

        return zen_href_link(
            FILENAME_PRODUCT,
            '&product_type=' . $productType . '&cPath=' . $cPath . '&pID=' . $productsId . '&action=new_product'
        );
    }

    protected function buildDetailLinkParameters(): string
    {
        $params = [];
        $productsFilter = $this->request->string('products_filter', '');
        $productsFilterNameModel = $this->request->string('products_filter_name_model', '');

        if ($productsFilter !== '') {
            $params[] = 'products_filter=' . urlencode($productsFilter);
        }

        if ($productsFilterNameModel !== '') {
            $params[] = 'products_filter_name_model=' . urlencode($productsFilterNameModel);
        }

        return implode('&', $params);
    }

    protected function buildQueryStringExcluding(array $excludeKeys): string
    {
        $params = [];
        foreach ($this->request->query() as $key => $value) {
            if (in_array($key, $excludeKeys, true)) {
                continue;
            }
            $params[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
        }

        return $params === [] ? '' : implode('&', $params) . '&';
    }
}
