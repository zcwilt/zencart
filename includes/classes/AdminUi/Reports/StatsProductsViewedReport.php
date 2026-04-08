<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Reports;

use Zencart\AdminUi\AdminPageData;
use Zencart\AdminUi\Pages\ReportListPageBuilder;
use Zencart\AdminUi\Pages\ReportViewConfig;

class StatsProductsViewedReport extends AdminReport
{
    public function buildPage(): AdminPageData
    {
        $context = $this->notifyBuildPageStart();

        global $db;

        $startDate = zen_db_input($this->request->input('start_date', date('Y') . '-01-01'));
        $endDate = zen_db_input($this->request->input('end_date', date('Y-m-d')));

        $sql = "SELECT p.products_id, pd.products_name, sum(v.views) as total_views, l.name as language, p.products_type
                FROM " . TABLE_PRODUCTS . " p
                LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON p.products_id = pd.products_id
                LEFT JOIN " . TABLE_LANGUAGES . " l ON l.languages_id = pd.language_id
                INNER JOIN " . TABLE_COUNT_PRODUCT_VIEWS . " v ON p.products_id = v.product_id AND v.language_id = l.languages_id
                LEFT JOIN " . TABLE_PRODUCT_TYPES . " pt ON p.products_type = pt.type_id
                WHERE date_viewed BETWEEN CAST(:startdate AS DATE) AND CAST(:enddate AS DATE)
                GROUP BY p.products_id, pd.products_name, language, p.products_type
                ORDER BY total_views DESC";
        $sql = $db->bindVars($sql, ':startdate', $startDate, 'string');
        $sql = $db->bindVars($sql, ':enddate', $endDate, 'string');

        $productsQueryNumRows = 0;
        $productsSplit = new \splitPageResults($this->request->integer('page', 1), MAX_DISPLAY_SEARCH_RESULTS_REPORTS, $sql, $productsQueryNumRows);
        $products = $db->Execute($sql);

        $rows = [];
        foreach ($products as $product) {
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
                    'value' => '<a href="' . $productLink . '">' . $product['products_name'] . '</a> (' . $product['language'] . ')',
                    'class' => '',
                    'original' => $product['products_name'],
                ],
                'total_views' => [
                    'value' => $product['total_views'],
                    'class' => 'text-center',
                    'original' => (int) $product['total_views'],
                ],
            ];
        }

        $formatter = new ReportTableFormatter(
            [
                ['headerClass' => 'dataTableHeadingContent right', 'title' => TABLE_HEADING_PRODUCTS_ID],
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
                $sql,
                TEXT_DISPLAY_NUMBER_OF_PRODUCTS,
                MAX_DISPLAY_SEARCH_RESULTS_REPORTS,
                MAX_DISPLAY_PAGE_LINKS,
                'page',
                'start_date=' . $startDate . '&end_date=' . $endDate
            )
            ->withViewConfig(new ReportViewConfig(
                $this->buildDateRangeFormHtml($startDate, $endDate),
                '',
                $this->buildDatePickerScriptHtml()
            ))
            ->build();

        return $this->notifyBuildPageEnd($page, array_merge($context, [
            'productsSplit' => $productsSplit,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]));
    }

    protected function buildDateRangeFormHtml(string $startDate, string $endDate): string
    {
        return '<div class="row">'
            . zen_draw_form('date_range', FILENAME_STATS_PRODUCTS_VIEWED, '', 'post', 'class="form-horizontal"')
            . '<div class="form-group">'
            . zen_draw_label(TEXT_REPORT_START_DATE, 'start_date', 'class="col-sm-3 control-label"')
            . '<div class="col-sm-4 col-md-3"><div class="date input-group" id="datepicker_start_date">'
            . '<span class="input-group-addon datepicker_icon">' . zen_icon('calendar-days', size: 'lg') . '</span>'
            . zen_draw_input_field('start_date', $startDate, 'class="form-control" id="start_date"')
            . '</div><span class="help-block errorText">(' . zen_datepicker_format_full() . ')</span></div></div>'
            . '<div class="form-group">'
            . zen_draw_label(TEXT_REPORT_END_DATE, 'end_date', 'class="col-sm-3 control-label"')
            . '<div class="col-sm-4 col-md-3"><div class="date input-group" id="datepicker_end_date">'
            . '<span class="input-group-addon datepicker_icon">' . zen_icon('calendar-days', size: 'lg') . '</span>'
            . zen_draw_input_field('end_date', $endDate, 'class="form-control" id="end_date"')
            . '</div><span class="help-block errorText">(' . zen_datepicker_format_full() . ')</span></div></div>'
            . '<div class="col-sm-7 col-md-6 text-right"><button type="submit" class="btn btn-primary">' . IMAGE_SUBMIT . '</button></div>'
            . '</form></div><br>';
    }

    protected function buildDatePickerScriptHtml(): string
    {
        return <<<HTML
<script>
    $(function () {
        $('input[name="start_date"]').datepicker({
            maxDate: 0
        });
        $('input[name="end_date"]').datepicker({
            maxDate: 0
        });
    })
</script>
HTML;
    }
}
