<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Reports;

use Zencart\AdminUi\AdminPageData;
use Zencart\AdminUi\Pages\ReportListPageBuilder;

class StatsProductsLowstockReport extends AdminReport
{
    public function buildPage(): AdminPageData
    {
        $context = $this->notifyBuildPageStart();

        global $db;

        $productsQueryRaw = "SELECT p.products_id, pd.products_name, p.products_quantity
                               FROM " . TABLE_PRODUCTS . " p,
                                    " . TABLE_PRODUCTS_DESCRIPTION . " pd
                              WHERE p.products_id = pd.products_id
                                AND pd.language_id = " . (int) $_SESSION['languages_id'] . "
                           ORDER BY p.products_quantity, pd.products_name";

        $productsQueryNumRows = 0;
        $productsSplit = new \splitPageResults($this->request->integer('page', 1), MAX_DISPLAY_SEARCH_RESULTS_REPORTS, $productsQueryRaw, $productsQueryNumRows);
        $products = $db->Execute($productsQueryRaw);

        $rows = [];
        foreach ($products as $productRecord) {
            $productData = (new \Product((int) $productRecord['products_id']))->withDefaultLanguage();
            $product = $productData->getData();

            if (!$productData->allowsAddToCart()) {
                continue;
            }

            $productLink = zen_href_link(
                FILENAME_PRODUCT,
                '&product_type=' . $product['products_type'] . '&cPath=' . zen_get_product_path($product['products_id']) . '&pID=' . $product['products_id'] . '&action=new_product'
            );

            $rows[] = [
                '_rowLink' => [
                    'value' => '',
                    'class' => '',
                    'original' => $productLink,
                ],
                'products_id' => [
                    'value' => $product['products_id'],
                    'class' => 'text-right',
                    'original' => (int) $product['products_id'],
                ],
                'products_name' => [
                    'value' => '<a href="' . $productLink . '">' . $product['products_name'] . '</a>',
                    'class' => '',
                    'original' => $product['products_name'],
                ],
                'products_quantity' => [
                    'value' => $product['products_quantity'],
                    'class' => 'text-center',
                    'original' => (int) $product['products_quantity'],
                ],
            ];
        }

        $formatter = new ReportTableFormatter(
            [
                ['headerClass' => 'dataTableHeadingContent right', 'title' => TABLE_HEADING_NUMBER],
                ['headerClass' => 'dataTableHeadingContent', 'title' => TABLE_HEADING_PRODUCTS_NAME],
                ['headerClass' => 'dataTableHeadingContent text-center', 'title' => TABLE_HEADING_VIEWED],
            ],
            $rows,
            static fn(array $row): string => (string) $row['_rowLink']['original']
        );

        $page = (new ReportListPageBuilder(
            $this->request,
            HEADING_TITLE,
            $formatter
        ))
            ->withPagination(
                $productsQueryRaw,
                TEXT_DISPLAY_NUMBER_OF_PRODUCTS,
                MAX_DISPLAY_SEARCH_RESULTS_REPORTS
            )
            ->build();

        return $this->notifyBuildPageEnd($page, array_merge($context, [
            'productsSplit' => $productsSplit,
        ]));
    }
}
