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
            return '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars((string) $value, ENT_QUOTES) . '">';
        }
    }

    if (!function_exists('zen_draw_input_field')) {
        function zen_draw_input_field($name, $value = '', $attributes = '')
        {
            return '<input type="text" name="' . $name . '" value="' . htmlspecialchars((string) $value, ENT_QUOTES) . '" ' . $attributes . '>';
        }
    }

    if (!function_exists('zen_href_link')) {
        function zen_href_link($page, $parameters = '')
        {
            return $page . ($parameters !== '' ? '?' . $parameters : '');
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
use Zencart\AdminUi\Pages\ListFooterConfig;
use Zencart\AdminUi\Pages\ListViewConfig;

class RenderedResourceListTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        require_once DIR_FS_CATALOG . 'includes/classes/AdminUi/Pages/ListViewConfig.php';
        require_once DIR_FS_CATALOG . 'includes/classes/AdminUi/Pages/ListFooterConfig.php';
        if (!defined('TABLE_HEADING_ACTION')) {
            define('TABLE_HEADING_ACTION', 'Action');
        }
        if (!defined('IMAGE_SEARCH')) {
            define('IMAGE_SEARCH', 'Search');
        }
        if (!defined('IMAGE_RESET')) {
            define('IMAGE_RESET', 'Reset');
        }
    }

    public function testRenderedResourceListSupportsGroupedRows(): void
    {
        $formatter = new class {
            public function getTableData(): array
            {
                return [
                    [
                        'name' => ['value' => 'Enabled Plugin', 'class' => '', 'original' => 'Enabled Plugin'],
                        'status' => ['value' => 'enabled-icon', 'class' => 'status-enabled', 'original' => 1],
                    ],
                    [
                        'name' => ['value' => 'Disabled Plugin', 'class' => '', 'original' => 'Disabled Plugin'],
                        'status' => ['value' => 'disabled-icon', 'class' => 'status-disabled', 'original' => 0],
                    ],
                ];
            }

            public function getTableHeaders(): array
            {
                return [
                    ['headerClass' => 'dataTableHeadingContent', 'title' => 'Name'],
                    ['headerClass' => 'dataTableHeadingContent', 'title' => 'Status'],
                ];
            }

            public function hasRowActions(): bool
            {
                return false;
            }

            public function getRowActions($tableRow): array
            {
                return [];
            }

            public function isRowSelected(array $tableRow): bool
            {
                return $tableRow['name']['value'] === 'Enabled Plugin';
            }

            public function getSelectedRowLink(array $tableRow): string
            {
                return 'selected-link';
            }

            public function getNotSelectedRowLink(array $tableRow): string
            {
                return 'not-selected-link';
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
        };

        $pageHeading = 'Plugin Manager';
        $listViewConfig = new ListViewConfig(
            'status',
            [1, 0],
            [1 => 'Enabled', 0 => 'Disabled'],
            [1 => 'w-10']
        );
        $footerConfig = new ListFooterConfig(
            'Showing 1 to 2',
            'Page 1',
            'new-resource.php',
            'Add New'
        );
        $PHP_SELF = 'plugin_manager.php';

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/resource_list.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Plugin Manager', $output);
        $this->assertStringContainsString('Enabled', $output);
        $this->assertStringContainsString('Disabled', $output);
        $this->assertStringContainsString('Enabled Plugin', $output);
        $this->assertStringContainsString('Disabled Plugin', $output);
        $this->assertStringContainsString('selected-link', $output);
        $this->assertStringContainsString('not-selected-link', $output);
        $this->assertStringContainsString('class="dataTableRowSelected js-resource-row-link"', $output);
        $this->assertStringContainsString('class="dataTableRow js-resource-row-link"', $output);
        $this->assertStringContainsString('data-row-link="selected-link"', $output);
        $this->assertStringContainsString('data-row-link="not-selected-link"', $output);
        $this->assertStringContainsString('Showing 1 to 2', $output);
        $this->assertStringContainsString('Page 1', $output);
        $this->assertStringContainsString('new-resource.php', $output);
        $this->assertStringContainsString('Add New', $output);
        $this->assertStringNotContainsString('Undefined', $output);
    }

    public function testRenderedResourceListSupportsTaxClassStyleFooterAndEditLinks(): void
    {
        $formatter = new class {
            public function getTableData(): array
            {
                return [
                    [
                        'tax_class_id' => ['value' => '3', 'class' => 'dataTableContent', 'original' => 3],
                        'tax_class_title' => ['value' => 'Taxable Goods', 'class' => 'dataTableContent', 'original' => 'Taxable Goods'],
                    ],
                    [
                        'tax_class_id' => ['value' => '7', 'class' => 'dataTableContent', 'original' => 7],
                        'tax_class_title' => ['value' => 'Shipping', 'class' => 'dataTableContent', 'original' => 'Shipping'],
                    ],
                ];
            }

            public function getTableHeaders(): array
            {
                return [
                    ['headerClass' => 'dataTableHeadingContent', 'title' => 'ID'],
                    ['headerClass' => 'dataTableHeadingContent', 'title' => 'Tax Class'],
                ];
            }

            public function hasRowActions(): bool
            {
                return false;
            }

            public function getRowActions($tableRow): array
            {
                return [];
            }

            public function isRowSelected(array $tableRow): bool
            {
                return $tableRow['tax_class_id']['value'] === '3';
            }

            public function getSelectedRowLink(array $tableRow): string
            {
                return 'tax_classes.php?page=2&tID=' . $tableRow['tax_class_id']['value'] . '&action=edit';
            }

            public function getNotSelectedRowLink(array $tableRow): string
            {
                return 'tax_classes.php?page=2&tID=' . $tableRow['tax_class_id']['value'];
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
        };

        $pageHeading = 'Tax Classes';
        $listViewConfig = new ListViewConfig();
        $footerConfig = new ListFooterConfig(
            'Displaying 1 to 2 of 2',
            '<a href="tax_classes.php?page=2">2</a>',
            'tax_classes.php?page=2&action=new',
            'New Tax Class'
        );
        $PHP_SELF = 'tax_classes.php';

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/resource_list.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Tax Classes', $output);
        $this->assertStringContainsString('tax_classes.php?page=2&tID=3&action=edit', $output);
        $this->assertStringContainsString('tax_classes.php?page=2&tID=7', $output);
        $this->assertStringContainsString('Displaying 1 to 2 of 2', $output);
        $this->assertStringContainsString('tax_classes.php?page=2&action=new', $output);
        $this->assertStringContainsString('New Tax Class', $output);
        $this->assertStringNotContainsString('Undefined', $output);
    }

    public function testRenderedResourceListShowsSearchFormWhenEnabled(): void
    {
        $formatter = new class {
            public function hasSearch(): bool
            {
                return true;
            }

            public function searchAction(): string
            {
                return 'plugin_manager';
            }

            public function searchHiddenParameters(): array
            {
                return ['plugin_status' => '1', 'sort' => 'name'];
            }

            public function searchParameter(): string
            {
                return 'search';
            }

            public function searchValue(): string
            {
                return 'ship';
            }

            public function searchPlaceholder(): string
            {
                return 'Search Name';
            }

            public function searchResetHref(): string
            {
                return 'plugin_manager?plugin_status=1&sort=name';
            }

            public function getTableData(): array
            {
                return [];
            }

            public function getTableHeaders(): array
            {
                return [
                    ['headerClass' => 'dataTableHeadingContent', 'title' => 'Name'],
                ];
            }

            public function isRowSelected(array $tableRow): bool
            {
                return false;
            }

            public function getSelectedRowLink(array $tableRow): string
            {
                return '';
            }

            public function getNotSelectedRowLink(array $tableRow): string
            {
                return '';
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
        };

        $pageHeading = 'Plugin Manager';
        $listViewConfig = new ListViewConfig();
        $footerConfig = new ListFooterConfig();
        $PHP_SELF = 'plugin_manager.php';

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/resource_list.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('name="table-search"', $output);
        $this->assertStringContainsString('js-resource-search-form', $output);
        $this->assertStringContainsString('data-lookahead-min="3"', $output);
        $this->assertStringContainsString('data-lookahead-delay="350"', $output);
        $this->assertStringContainsString('name="plugin_status" value="1"', $output);
        $this->assertStringContainsString('name="sort" value="name"', $output);
        $this->assertStringContainsString('name="search" value="ship"', $output);
        $this->assertStringContainsString('class="form-control js-resource-search-input"', $output);
        $this->assertStringContainsString('plugin_manager?plugin_status=1&sort=name', $output);
        $this->assertStringContainsString("var row = event.target.closest('tr.js-resource-row-link');", $output);
        $this->assertStringContainsString("form.js-resource-search-form[data-lookahead=\"true\"]", $output);
        $this->assertStringContainsString('if (value.length < minLength)', $output);
        $this->assertStringContainsString('form.submit();', $output);
        $this->assertStringContainsString('input.focus();', $output);
        $this->assertStringContainsString('input.setSelectionRange(input.value.length, input.value.length);', $output);
    }

    public function testRenderedResourceListPartialsSupportNotifierCustomization(): void
    {
        global $zco_notifier;

        $formatter = new class {
            public function hasSearch(): bool
            {
                return true;
            }

            public function searchAction(): string
            {
                return 'plugin_manager';
            }

            public function searchHiddenParameters(): array
            {
                return ['plugin_status' => '1'];
            }

            public function searchParameter(): string
            {
                return 'search';
            }

            public function searchValue(): string
            {
                return 'ship';
            }

            public function searchPlaceholder(): string
            {
                return 'Search Name';
            }

            public function searchResetHref(): string
            {
                return 'plugin_manager?plugin_status=1';
            }

            public function getTableData(): array
            {
                return [];
            }

            public function getTableHeaders(): array
            {
                return [
                    ['headerClass' => 'dataTableHeadingContent', 'title' => 'Name'],
                ];
            }

            public function isRowSelected(array $tableRow): bool
            {
                return false;
            }

            public function getSelectedRowLink(array $tableRow): string
            {
                return '';
            }

            public function getNotSelectedRowLink(array $tableRow): string
            {
                return '';
            }
        };

        $tableController = new class {
            public function getBoxHeader(): array
            {
                return [['text' => '<h4>Original Header</h4>']];
            }

            public function getBoxContent(): array
            {
                return [['text' => 'Original Content']];
            }
        };

        $zco_notifier = new class {
            public function notify($eventId, $param1 = [], &$param2 = null, &$param3 = null, &$param4 = null, &$param5 = null, &$param6 = null, &$param7 = null, &$param8 = null, &$param9 = null): void
            {
                if ($eventId === 'NOTIFY_ADMIN_RESOURCE_TOOLBAR_START') {
                    $param2 = '<div>toolbar-before</div>';
                    $param3 = '<div>toolbar-after</div>';
                    $param6['observer'] = '1';
                    $param7 = 'Find';
                    $param8 = 'Clear';
                }

                if ($eventId === 'NOTIFY_ADMIN_RESOURCE_INFOBOX_START') {
                    $param2 = '<div>infobox-before</div>';
                    $param3 = '<div>infobox-after</div>';
                }

                if ($eventId === 'NOTIFY_ADMIN_RESOURCE_FOOTER_START') {
                    $param2 = '<div>footer-before</div>';
                    $param3 = '<div>footer-after</div>';
                    $param6 = '<a href="observer-action.php" class="btn btn-primary" role="button">Observer Action</a>';
                }
            }
        };

        $pageHeading = 'Plugin Manager';
        $listViewConfig = new ListViewConfig();
        $footerConfig = new ListFooterConfig(
            'Showing 1 to 2',
            'Page 1'
        );
        $PHP_SELF = 'plugin_manager.php';

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/resource_list.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('toolbar-before', $output);
        $this->assertStringContainsString('toolbar-after', $output);
        $this->assertStringContainsString('name="observer" value="1"', $output);
        $this->assertStringContainsString('>Find</button>', $output);
        $this->assertStringContainsString('>Clear</a>', $output);
        $this->assertStringContainsString('infobox-before', $output);
        $this->assertStringContainsString('infobox-after', $output);
        $this->assertStringContainsString('footer-before', $output);
        $this->assertStringContainsString('footer-after', $output);
        $this->assertStringContainsString('observer-action.php', $output);
        $this->assertStringContainsString('Observer Action', $output);
    }
}
}
