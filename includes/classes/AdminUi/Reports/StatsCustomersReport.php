<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Reports;

use Zencart\AdminUi\AdminPageData;
use Zencart\AdminUi\Pages\ReportListPageBuilder;

class StatsCustomersReport extends AdminReport
{
    public function buildPage(): AdminPageData
    {
        $context = $this->notifyBuildPageStart();

        global $db;

        $currencies = new \currencies();
        $customersQueryRaw = "SELECT c.customers_id, c.customers_firstname, c.customers_lastname,
                                     SUM(op.products_quantity * op.final_price) + SUM(op.onetime_charges) AS ordersum
                                FROM " . TABLE_CUSTOMERS . " c,
                                     " . TABLE_ORDERS_PRODUCTS . " op,
                                     " . TABLE_ORDERS . " o
                               WHERE c.customers_id = o.customers_id
                                 AND o.orders_id = op.orders_id
                               GROUP BY c.customers_id, c.customers_firstname, c.customers_lastname
                               ORDER BY ordersum DESC";

        $customersQueryNumRows = 0;
        $customersSplit = new \splitPageResults($this->request->integer('page', 1), MAX_DISPLAY_SEARCH_RESULTS_REPORTS, $customersQueryRaw, $customersQueryNumRows);
        $customers = $db->Execute($customersQueryRaw);
        $countQuery = $db->Execute(
            "SELECT customers_id
               FROM " . TABLE_ORDERS . "
              GROUP BY customers_id"
        );
        $totalCustomers = $countQuery->RecordCount();

        $rows = [];
        foreach ($customers as $customer) {
            $rows[] = [
                'customers_id' => [
                    'value' => $customer['customers_id'] . '&nbsp;&nbsp;',
                    'class' => 'text-right',
                    'original' => (int) $customer['customers_id'],
                ],
                'customers_name' => [
                    'value' => '<a href="' . zen_href_link(FILENAME_CUSTOMERS, 'cID=' . $customer['customers_id'], 'NONSSL') . '">' . $customer['customers_firstname'] . ' ' . $customer['customers_lastname'] . '</a>',
                    'class' => '',
                    'original' => trim($customer['customers_firstname'] . ' ' . $customer['customers_lastname']),
                ],
                'ordersum' => [
                    'value' => $currencies->format((float) $customer['ordersum']),
                    'class' => 'text-right',
                    'original' => (float) $customer['ordersum'],
                ],
            ];
        }

        $formatter = new ReportTableFormatter(
            [
                ['headerClass' => 'dataTableHeadingContent right', 'title' => TABLE_HEADING_NUMBER],
                ['headerClass' => 'dataTableHeadingContent', 'title' => TABLE_HEADING_CUSTOMERS],
                ['headerClass' => 'dataTableHeadingContent text-right', 'title' => TABLE_HEADING_TOTAL_PURCHASED . '&nbsp;'],
            ],
            $rows,
            static fn(array $row): string => zen_href_link(FILENAME_CUSTOMERS, 'cID=' . $row['customers_id']['original'], 'NONSSL')
        );

        $page = (new ReportListPageBuilder(
            $this->request,
            HEADING_TITLE,
            $formatter
        ))
            ->withPagination(
                $customersQueryRaw,
                TEXT_DISPLAY_NUMBER_OF_CUSTOMERS,
                MAX_DISPLAY_SEARCH_RESULTS_REPORTS
            )
            ->withTotalOverride($totalCustomers)
            ->build();

        return $this->notifyBuildPageEnd($page, array_merge($context, [
            'customersSplit' => $customersSplit,
            'totalCustomers' => $totalCustomers,
        ]));
    }
}
