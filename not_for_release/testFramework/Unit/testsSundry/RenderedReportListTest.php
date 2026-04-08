<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace {
    if (!function_exists('zen_draw_form')) {
        function zen_draw_form($name, $action, $parameters = '', $method = 'post', $attributes = '')
        {
            $query = $parameters !== '' ? '?' . $parameters : '';
            return '<form name="' . $name . '" action="' . $action . $query . '" method="' . $method . '" ' . $attributes . '>';
        }
    }

    if (!function_exists('zen_draw_hidden_field')) {
        function zen_draw_hidden_field($name, $value)
        {
            return '<input type="hidden" name="' . htmlspecialchars((string) $name, ENT_QUOTES) . '" value="' . htmlspecialchars((string) $value, ENT_QUOTES) . '">';
        }
    }
}

namespace Tests\Unit\testsSundry {

use Tests\Support\zcUnitTestCase;
use Zencart\AdminUi\Pages\ListFooterConfig;
use Zencart\AdminUi\Pages\ReportViewConfig;

class RenderedReportListTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        require_once DIR_FS_CATALOG . 'includes/classes/AdminUi/Pages/ListFooterConfig.php';
        require_once DIR_FS_CATALOG . 'includes/classes/AdminUi/Pages/ReportViewConfig.php';
        if (!defined('IMAGE_SEARCH')) {
            define('IMAGE_SEARCH', 'Search');
        }
        if (!defined('IMAGE_RESET')) {
            define('IMAGE_RESET', 'Reset');
        }
    }

    public function testRenderedReportListSupportsClickableRowsAndFooter(): void
    {
        $formatter = new class {
            public function getTableHeaders(): array
            {
                return [
                    ['headerClass' => 'dataTableHeadingContent text-right', 'title' => 'ID'],
                    ['headerClass' => 'dataTableHeadingContent', 'title' => 'Customer'],
                ];
            }

            public function getTableData(): array
            {
                return [
                    [
                        'customers_id' => ['value' => '12', 'class' => 'text-right', 'original' => 12],
                        'customers_name' => ['value' => 'Alice Example', 'class' => '', 'original' => 'Alice Example'],
                    ],
                ];
            }

            public function rowLink(array $tableRow): ?string
            {
                return 'customers.php?cID=' . $tableRow['customers_id']['original'];
            }
        };

        $pageHeading = 'Customer Report';
        $footerConfig = new ListFooterConfig('Showing 1 to 1', 'Page 1');
        $reportViewConfig = new ReportViewConfig('<div class="summary">Summary</div>');

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/report_list.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Customer Report', $output);
        $this->assertStringContainsString('<div class="summary">Summary</div>', $output);
        $this->assertStringContainsString('Alice Example', $output);
        $this->assertStringContainsString('customers.php?cID=12', $output);
        $this->assertStringContainsString('Showing 1 to 1', $output);
        $this->assertStringContainsString('Page 1', $output);
    }

    public function testRenderedReportListShowsEmptyStateWhenNoRows(): void
    {
        $formatter = new class {
            public function getTableHeaders(): array
            {
                return [];
            }

            public function getTableData(): array
            {
                return [];
            }
        };

        $pageHeading = 'Empty Report';
        $footerConfig = new ListFooterConfig();
        $reportViewConfig = new ReportViewConfig('', '<p>No rows found</p>');

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/report_list.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Empty Report', $output);
        $this->assertStringContainsString('<p>No rows found</p>', $output);
    }

    public function testRenderedReportListOutputsAfterHtml(): void
    {
        $formatter = new class {
            public function getTableHeaders(): array
            {
                return [];
            }

            public function getTableData(): array
            {
                return [];
            }
        };

        $pageHeading = 'Scripted Report';
        $footerConfig = new ListFooterConfig();
        $reportViewConfig = new ReportViewConfig('', '<p>No rows found</p>', '<script>window.reportReady = true;</script>');

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/report_list.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('<script>window.reportReady = true;</script>', $output);
    }
}
}
