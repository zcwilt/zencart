<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace {
    if (!function_exists('zen_href_link')) {
        function zen_href_link($page, $parameters = '')
        {
            return $page . ($parameters !== '' ? '?' . $parameters : '');
        }
    }

    if (!function_exists('zen_icon')) {
        function zen_icon(string $icon, ?string $tooltip = null, string $size = '', bool $fixedWidth = false, bool $hidden = false): string
        {
            return '<i class="' . $icon . '"></i>';
        }
    }

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
            return '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars((string) $value, ENT_QUOTES) . '">';
        }
    }

    if (!function_exists('zen_draw_input_field')) {
        function zen_draw_input_field($name, $value = '', $attributes = '')
        {
            return '<input type="text" name="' . $name . '" value="' . htmlspecialchars((string) $value, ENT_QUOTES) . '" ' . $attributes . '>';
        }
    }

    if (!class_exists('box')) {
        class box
        {
            public function infoBox(array $header, array $content): string
            {
                return '<div class="box">' . count($header) . ':' . count($content) . '</div>';
            }
        }
    }
}

namespace Tests\Unit\testsSundry {

use Tests\Support\zcUnitTestCase;

class RenderedManufacturersResourceTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (!defined('TABLE_HEADING_ID')) {
            define('TABLE_HEADING_ID', 'ID');
        }
        if (!defined('TABLE_HEADING_MANUFACTURERS')) {
            define('TABLE_HEADING_MANUFACTURERS', 'Manufacturers');
        }
        if (!defined('TABLE_HEADING_MANUFACTURER_FEATURED')) {
            define('TABLE_HEADING_MANUFACTURER_FEATURED', 'Featured?');
        }
        if (!defined('TABLE_HEADING_ACTION')) {
            define('TABLE_HEADING_ACTION', 'Action');
        }
        if (!defined('TEXT_DISPLAY_NUMBER_OF_MANUFACTURERS')) {
            define('TEXT_DISPLAY_NUMBER_OF_MANUFACTURERS', 'Displaying manufacturers');
        }
        if (!defined('MAX_DISPLAY_SEARCH_RESULTS')) {
            define('MAX_DISPLAY_SEARCH_RESULTS', 20);
        }
        if (!defined('MAX_DISPLAY_PAGE_LINKS')) {
            define('MAX_DISPLAY_PAGE_LINKS', 5);
        }
        if (!defined('FILENAME_MANUFACTURERS')) {
            define('FILENAME_MANUFACTURERS', 'manufacturers.php');
        }
        if (!defined('ICON_EDIT')) {
            define('ICON_EDIT', 'Edit');
        }
        if (!defined('ICON_DELETE')) {
            define('ICON_DELETE', 'Delete');
        }
        if (!defined('IMAGE_INSERT')) {
            define('IMAGE_INSERT', 'Insert');
        }
        if (!defined('TEXT_RESOURCE_LIST_SEARCH_BUTTON')) {
            define('TEXT_RESOURCE_LIST_SEARCH_BUTTON', 'Search');
        }
        if (!defined('TEXT_RESOURCE_LIST_RESET_BUTTON')) {
            define('TEXT_RESOURCE_LIST_RESET_BUTTON', 'Reset');
        }
    }

    public function testRenderedManufacturersTemplateIncludesNotifierColumnsAndActions(): void
    {
        global $zco_notifier;

        $_GET = ['cmd' => 'manufacturers', 'page' => '2', 'mID' => '9'];

        $zco_notifier = new class {
            public function notify($event, $param1 = [], &$param2 = null): void
            {
                if ($event === 'NOTIFY_ADMIN_MANUFACTURERS_EXTRA_COLUMN_HEADING') {
                    $param2 = [['align' => 'center', 'text' => 'Observer Heading']];
                }

                if ($event === 'NOTIFY_ADMIN_MANUFACTURERS_EXTRA_COLUMN_DATA') {
                    $manufacturerId = (int) (($param1['manufacturers_id'] ?? $param1->manufacturers_id ?? 0));
                    $param2 = [['align' => 'center', 'text' => 'Observer Data ' . $manufacturerId]];
                }
            }
        };

        $formatter = new class {
            public function getTableData(): array
            {
                return [
                    [
                        'manufacturers_id' => ['value' => '4'],
                        'manufacturers_name' => ['value' => 'Acme Labs'],
                        'featured' => ['value' => 'No'],
                    ],
                    [
                        'manufacturers_id' => ['value' => '9'],
                        'manufacturers_name' => ['value' => 'Zen Widget Co'],
                        'featured' => ['value' => '<strong>Yes</strong>'],
                    ],
                ];
            }

            public function getResultSet()
            {
                return new class {
                    public function getCollection(): array
                    {
                        return [
                            ['manufacturers_id' => 4, 'manufacturers_name' => 'Acme Labs', 'featured' => 0],
                            ['manufacturers_id' => 9, 'manufacturers_name' => 'Zen Widget Co', 'featured' => 1],
                        ];
                    }
                };
            }

            public function editRowLink(array $tableRow): string
            {
                return 'manufacturers.php?page=2&mID=' . $tableRow['manufacturers_id']['value'] . '&action=edit';
            }

            public function hasSearch(): bool
            {
                return true;
            }

            public function searchAction(): string
            {
                return 'manufacturers.php';
            }

            public function searchHiddenParameters(): array
            {
                return ['page' => '2'];
            }

            public function searchParameter(): string
            {
                return 'search';
            }

            public function searchValue(): string
            {
                return 'Zen';
            }

            public function searchPlaceholder(): string
            {
                return 'Search Manufacturers';
            }

            public function searchResetHref(): string
            {
                return 'manufacturers.php?page=2';
            }

            public function getNotSelectedRowLink(array $tableRow): string
            {
                return 'manufacturers.php?page=2&mID=' . $tableRow['manufacturers_id']['value'];
            }

            public function isRowSelected(array $tableRow): bool
            {
                return $tableRow['manufacturers_id']['value'] === '9';
            }

            public function currentRowFromRequest()
            {
                return ['manufacturers_id' => 9];
            }
        };

        $tableController = new class {
            public function getBoxHeader(): array
            {
                return [];
            }

            public function getBoxContent(): array
            {
                return [];
            }

            public function getAction(): string
            {
                return '';
            }
        };

        $manufacturersSplit = new class {
            public function display_count($queryNumRows, $resultsPerPage, $currentPage, $countText): string
            {
                return 'count:' . $queryNumRows . ':' . $currentPage . ':' . $countText;
            }

            public function display_links($queryNumRows, $resultsPerPage, $maxPageLinks, $currentPage): string
            {
                return 'links:' . $queryNumRows . ':' . $currentPage;
            }
        };

        $manufacturersQueryNumRows = 2;
        $currentPage = '2';
        $pageHeading = 'Manufacturers';

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/manufacturers_resource.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('<h1>Manufacturers</h1>', $output);
        $this->assertStringContainsString('name="manufacturers-search"', $output);
        $this->assertStringContainsString('name="search" value="Zen"', $output);
        $this->assertStringContainsString('class="form-control js-resource-search-input"', $output);
        $this->assertStringContainsString('manufacturers.php?page=2', $output);
        $this->assertStringContainsString("form.js-resource-search-form[data-lookahead=\"true\"]", $output);
        $this->assertStringContainsString('Observer Heading', $output);
        $this->assertStringContainsString('Observer Data 4', $output);
        $this->assertStringContainsString("document.location.href='manufacturers.php?page=2&mID=4&action=edit'", $output);
        $this->assertStringContainsString('href="manufacturers?page=2&mID=4&action=delete"', $output);
        $this->assertStringContainsString('href="manufacturers.php?page=2&mID=4"', $output);
        $this->assertStringContainsString('count:2:2:Displaying manufacturers', $output);
        $this->assertStringContainsString('links:2:2', $output);
        $this->assertStringContainsString('href="manufacturers?page=2&mID=9&action=new"', $output);
        $this->assertStringContainsString('Zen Widget Co', $output);
    }
}
}
