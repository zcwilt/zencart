<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Reports;

use statsSalesReportGraph;
use Zencart\AdminUi\AdminPageData;
use Zencart\AdminUi\Pages\ReportAnalyticsPage;

class StatsSalesReportGraphsReport extends AdminReport
{
    public function buildPage(): AdminPageData
    {
        $context = $this->notifyBuildPageStart();

        require_once DIR_FS_ADMIN . 'includes/classes/stats_sales_report_graph.php';
        $currencies = new \currencies();

        $salesReportView = $this->resolveReportView();
        $startDate = $this->request->string('startDate', '');
        $endDate = $this->request->string('endDate', '');
        $salesReportFilter = $this->request->string('filter', '');

        $report = new statsSalesReportGraph($salesReportView, $startDate, $endDate, $salesReportFilter);
        if ($salesReportFilter === '') {
            $salesReportFilter = $report->filter;
        }

        [$summary1, $summary2, $reportDesc] = $this->summaryLabels($salesReportView);
        $tableHeaders = $this->tableHeaders($salesReportView);
        $tableRows = $this->tableRows($report, $salesReportView, $currencies);
        $chartConfigs = $this->chartConfigs($report, $salesReportView, $reportDesc, $currencies);
        $summaryRows = $this->summaryRows($report, $tableRows, $summary1, $summary2, $currencies);
        $filterRows = $this->filterRows($report, $salesReportFilter);
        $navigationLinks = $this->navigationLinks($salesReportFilter);

        $page = (new ReportAnalyticsPage(
            HEADING_TITLE,
            $navigationLinks,
            $chartConfigs,
            $tableHeaders,
            $tableRows,
            $summaryRows,
            $filterRows,
            $report->previous !== '' ? zen_href_link(FILENAME_STATS_SALES_REPORT_GRAPHS, $report->previous) : null,
            $report->next !== '' ? zen_href_link(FILENAME_STATS_SALES_REPORT_GRAPHS, $report->next) : null
        ))->build();

        return $this->notifyBuildPageEnd($page, array_merge($context, [
            'salesReportView' => $salesReportView,
            'salesReportFilter' => $salesReportFilter,
        ]));
    }

    protected function resolveReportView(): int
    {
        $salesReportView = $this->request->integer('report', statsSalesReportGraph::MONTHLY_VIEW);
        if ($salesReportView < statsSalesReportGraph::HOURLY_VIEW || $salesReportView > statsSalesReportGraph::YEARLY_VIEW) {
            return statsSalesReportGraph::MONTHLY_VIEW;
        }

        return $salesReportView;
    }

    protected function summaryLabels(int $salesReportView): array
    {
        return match ($salesReportView) {
            statsSalesReportGraph::HOURLY_VIEW => [CHART_TEXT_AVERAGE . ' ' . REPORT_TEXT_HOURLY, TODAY_TO_DATE, REPORT_TEXT_HOURLY],
            statsSalesReportGraph::DAILY_VIEW => [CHART_TEXT_AVERAGE . ' ' . REPORT_TEXT_DAILY, WEEK_TO_DATE, REPORT_TEXT_DAILY],
            statsSalesReportGraph::WEEKLY_VIEW => [CHART_TEXT_AVERAGE . ' ' . REPORT_TEXT_WEEKLY, WEEK_TO_DATE, REPORT_TEXT_WEEKLY],
            statsSalesReportGraph::YEARLY_VIEW => [CHART_TEXT_AVERAGE . ' ' . REPORT_TEXT_YEARLY, YEARLY_TOTAL, REPORT_TEXT_YEARLY],
            default => [CHART_TEXT_AVERAGE . ' ' . REPORT_TEXT_MONTHLY, MONTH_TO_DATE, REPORT_TEXT_MONTHLY],
        };
    }

    protected function navigationLinks(string $salesReportFilter): array
    {
        $filterLink = $salesReportFilter !== '' ? '&filter=' . $salesReportFilter : '';

        return [
            ['label' => REPORT_TEXT_HOURLY, 'href' => zen_href_link(FILENAME_STATS_SALES_REPORT_GRAPHS, 'report=1' . $filterLink)],
            ['label' => REPORT_TEXT_DAILY, 'href' => zen_href_link(FILENAME_STATS_SALES_REPORT_GRAPHS, 'report=2' . $filterLink)],
            ['label' => REPORT_TEXT_WEEKLY, 'href' => zen_href_link(FILENAME_STATS_SALES_REPORT_GRAPHS, 'report=3' . $filterLink)],
            ['label' => REPORT_TEXT_MONTHLY, 'href' => zen_href_link(FILENAME_STATS_SALES_REPORT_GRAPHS, 'report=4' . $filterLink)],
            ['label' => REPORT_TEXT_YEARLY, 'href' => zen_href_link(FILENAME_STATS_SALES_REPORT_GRAPHS, 'report=5' . $filterLink)],
        ];
    }

    protected function tableHeaders(int $salesReportView): array
    {
        $reportTextTitle = match ($salesReportView) {
            statsSalesReportGraph::YEARLY_VIEW => REPORT_TEXT_YEARLY_TITLE,
            statsSalesReportGraph::MONTHLY_VIEW => REPORT_TEXT_MONTHLY_TITLE,
            statsSalesReportGraph::WEEKLY_VIEW => REPORT_TEXT_WEEKLY_TITLE,
            statsSalesReportGraph::DAILY_VIEW => REPORT_TEXT_DAILY_TITLE,
            default => REPORT_TEXT_HOURLY_TITLE,
        };

        return [
            ['title' => $reportTextTitle, 'class' => 'dataTableHeadingContent'],
            ['title' => REPORT_TEXT_ORDERS, 'class' => 'dataTableHeadingContent text-center'],
            ['title' => REPORT_TEXT_CONVERSION_PER_ORDER, 'class' => 'dataTableHeadingContent text-right'],
            ['title' => REPORT_TEXT_CONVERSION, 'class' => 'dataTableHeadingContent text-right'],
            ['title' => REPORT_TEXT_VARIANCE, 'class' => 'dataTableHeadingContent text-right'],
        ];
    }

    protected function tableRows(statsSalesReportGraph $report, int $salesReportView, \currencies $currencies): array
    {
        $rows = [];
        $lastValue = 0.0;

        for ($i = 0; $i < $report->size; $i++) {
            $percent = $lastValue != 0.0 ? (100 * $report->info[$i]['sum'] / $lastValue - 100) : 0.0;
            $lastValue = (float) $report->info[$i]['sum'];

            $label = $this->periodLabel($salesReportView, $report->info[$i]['startDates'], $report->info[$i]['endDates'], $i);
            if (($report->info[$i]['link'] ?? '') !== '') {
                $label = '<a href="' . zen_href_link(FILENAME_STATS_SALES_REPORT_GRAPHS, $report->info[$i]['link']) . '">' . $label . '</a>';
            }

            $rows[] = [
                'label' => $label,
                'count' => (string) $report->info[$i]['count'],
                'avg' => $currencies->format((float) $report->info[$i]['avg']),
                'sum' => $currencies->format((float) $report->info[$i]['sum']),
                'variance' => $percent == 0.0 ? '---' : number_format($percent, 0) . '%',
            ];
        }

        return $rows;
    }

    protected function chartConfigs(statsSalesReportGraph $report, int $salesReportView, string $reportDesc, \currencies $currencies): array
    {
        $chartHeader = '';
        $totalData = [];
        $averageData = [];

        for ($i = 0; $i < $report->size; $i++) {
            $label = $this->chartLabel($salesReportView, $report->info[$i]['startDates'], $report->info[$i]['endDates'], $chartHeader, $i);
            $totalData[] = [$label, round((float) $report->info[$i]['sum'], $currencies->get_decimal_places(DEFAULT_CURRENCY))];

            if ($salesReportView < statsSalesReportGraph::YEARLY_VIEW) {
                $averageData[] = [$label, round((float) $report->info[$i]['avg'], $currencies->get_decimal_places(DEFAULT_CURRENCY))];
            }
        }

        $configs = [[
            'containerId' => 'chart_div',
            'seriesLabel' => CHART_TOTAL_SALES,
            'title' => $reportDesc . $chartHeader,
            'color' => '#0000FF',
            'data' => $totalData,
        ]];

        if ($salesReportView < statsSalesReportGraph::YEARLY_VIEW) {
            $configs[] = [
                'containerId' => 'chart_div2',
                'seriesLabel' => CHART_AVERAGE_SALE_AMOUNT,
                'title' => $reportDesc . $chartHeader,
                'color' => '#FF0000',
                'data' => $averageData,
            ];
        }

        return $configs;
    }

    protected function summaryRows(statsSalesReportGraph $report, array $tableRows, string $summary1, string $summary2, \currencies $currencies): array
    {
        $sum = 0.0;
        $avg = 0.0;
        foreach ($report->info as $info) {
            $sum += (float) $info['sum'];
            $avg += (float) $info['avg'];
        }

        $rows = [];
        if ($report->size != 0) {
            $rows[] = [
                'label' => '<strong>' . $summary1 . ' </strong>',
                'value' => $currencies->format($sum / $report->size),
            ];
        }
        $rows[] = [
            'label' => '<strong>' . $summary2 . ' </strong>',
            'value' => $currencies->format($sum),
        ];

        return $rows;
    }

    protected function filterRows(statsSalesReportGraph $report, string $salesReportFilter): array
    {
        if ($salesReportFilter === '') {
            $salesReportFilter = str_repeat('0', $report->status_available_size);
        }

        $rows = [];
        for ($i = 0; $i < $report->status_available_size; $i++) {
            $enabled = substr($salesReportFilter, $i, 1) !== '1';
            if ($enabled) {
                $toggle = substr($salesReportFilter, 0, $i) . '1' . substr($salesReportFilter, $i + 1, $report->status_available_size - ($i + 1));
                $toggleLink = zen_href_link(FILENAME_STATS_SALES_REPORT_GRAPHS, $report->filter_link . '&filter=' . $toggle);
                $valueHtml = zen_icon('status-green', IMAGE_ICON_STATUS_GREEN) . '&nbsp;<a href="' . $toggleLink . '">' . zen_icon('status-red-light', IMAGE_ICON_STATUS_RED_LIGHT) . '</a>';
            } else {
                $toggle = substr($salesReportFilter, 0, $i) . '0' . substr($salesReportFilter, $i + 1);
                $toggleLink = zen_href_link(FILENAME_STATS_SALES_REPORT_GRAPHS, $report->filter_link . '&filter=' . $toggle);
                $valueHtml = '<a href="' . $toggleLink . '">' . zen_icon('status-green-light', IMAGE_ICON_STATUS_GREEN) . '</a>&nbsp;' . zen_icon('status-red', IMAGE_ICON_STATUS_RED_LIGHT);
            }

            $rows[] = [
                'label' => $report->status_available[$i]['text'],
                'valueHtml' => $valueHtml,
            ];
        }

        return $rows;
    }

    protected function periodLabel(int $salesReportView, int $start, int $end, int $index): string
    {
        global $zcDate;

        return match ($salesReportView) {
            statsSalesReportGraph::HOURLY_VIEW => $zcDate->output('%H', $start) . ' - ' . $zcDate->output('%H', $end) . ($index === 0 ? ' ' . $zcDate->output(DATE_FORMAT_SHORT, $start) : ''),
            statsSalesReportGraph::DAILY_VIEW => $zcDate->output(DATE_FORMAT_SHORT, $start),
            statsSalesReportGraph::WEEKLY_VIEW => $zcDate->output(DATE_FORMAT_SHORT, $start) . ' - ' . $zcDate->output(DATE_FORMAT_SHORT, mktime(0, 0, 0, (int) date('m', $end), date('d', $end) - 1, (int) date('Y', $end))),
            statsSalesReportGraph::MONTHLY_VIEW => $zcDate->output(DATE_FORMAT_SHORT_NO_DAY, $start),
            default => $zcDate->output('%Y', $start),
        };
    }

    protected function chartLabel(int $salesReportView, int $start, int $end, string &$chartHeader, int $index): string
    {
        global $zcDate;

        return match ($salesReportView) {
            statsSalesReportGraph::YEARLY_VIEW => $zcDate->output('%Y', $start),
            statsSalesReportGraph::MONTHLY_VIEW => $this->captureChartHeader($chartHeader, ' ' . $zcDate->output('%Y', $start), $index, $zcDate->output('%b', $start)),
            statsSalesReportGraph::WEEKLY_VIEW => $zcDate->output(DATE_FORMAT_SHORT_NO_YEAR, $start) . '\n' . $zcDate->output(DATE_FORMAT_SHORT_NO_YEAR, $end - 1),
            statsSalesReportGraph::DAILY_VIEW => $zcDate->output(DATE_FORMAT_SHORT_NO_YEAR, $start),
            default => $this->captureChartHeader($chartHeader, ' ' . $zcDate->output(DATE_FORMAT_SHORT, $start), $index, $zcDate->output('%k', $start)),
        };
    }

    protected function captureChartHeader(string &$chartHeader, string $value, int $index, string $label): string
    {
        if ($chartHeader === '' && $index === 0) {
            $chartHeader = $value;
        }

        return $label;
    }
}
