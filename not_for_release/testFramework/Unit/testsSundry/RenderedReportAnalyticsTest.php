<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace {
    if (!function_exists('zen_icon')) {
        function zen_icon(string $icon, ?string $tooltip = null, string $size = '', bool $fixedWidth = false, bool $hidden = false): string
        {
            return '<i class="' . $icon . '"></i>';
        }
    }
}

namespace Tests\Unit\testsSundry {

use Tests\Support\zcUnitTestCase;

class RenderedReportAnalyticsTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        foreach ([
            'TEXT_PREVIOUS_LINK' => 'Previous',
            'TEXT_NEXT_LINK' => 'Next',
            'FILTER_STATUS' => 'Filter by Order Status',
            'FILTER_VALUE' => 'Filter On/Off',
        ] as $constant => $value) {
            if (!defined($constant)) {
                define($constant, $value);
            }
        }
    }

    public function testAnalyticsTemplateRendersChartsTablesAndFilters(): void
    {
        $pageHeading = 'Sales Report';
        $navigationLinks = [
            ['label' => 'Daily', 'href' => 'stats_sales_report_graphs.php?report=2'],
            ['label' => 'Monthly', 'href' => 'stats_sales_report_graphs.php?report=4'],
        ];
        $chartConfigs = [[
            'containerId' => 'chart_div',
            'seriesLabel' => 'Total Sales',
            'title' => 'Monthly 2026',
            'color' => '#0000FF',
            'data' => [['Jan', 100.25], ['Feb', 200.75]],
        ]];
        $tableHeaders = [
            ['title' => 'Month', 'class' => 'dataTableHeadingContent'],
            ['title' => 'Orders', 'class' => 'dataTableHeadingContent text-center'],
            ['title' => 'Avg', 'class' => 'dataTableHeadingContent text-right'],
            ['title' => 'Sum', 'class' => 'dataTableHeadingContent text-right'],
            ['title' => 'Variance', 'class' => 'dataTableHeadingContent text-right'],
        ];
        $tableRows = [[
            'label' => '<a href="stats_sales_report_graphs.php?report=3">Jan</a>',
            'count' => '5',
            'avg' => '$20.00',
            'sum' => '$100.00',
            'variance' => '10%',
        ]];
        $summaryRows = [['label' => '<strong>Monthly Totals</strong>', 'value' => '$100.00']];
        $filterRows = [['label' => 'Pending', 'valueHtml' => '<i class="status-green"></i>']];
        $previousLink = 'stats_sales_report_graphs.php?report=4&startDate=1';
        $nextLink = 'stats_sales_report_graphs.php?report=4&startDate=2';

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/report_analytics.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Sales Report', $output);
        $this->assertStringContainsString('stats_sales_report_graphs.php?report=2', $output);
        $this->assertStringContainsString('chart_div', $output);
        $this->assertStringContainsString('Monthly 2026', $output);
        $this->assertStringContainsString('$100.00', $output);
        $this->assertStringContainsString('Pending', $output);
        $this->assertStringContainsString('stats_sales_report_graphs.php?report=4&startDate=1', $output);
        $this->assertStringContainsString('google.charts.load', $output);
    }
}
}
