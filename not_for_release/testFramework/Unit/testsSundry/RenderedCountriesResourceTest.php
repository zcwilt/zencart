<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace {
    if (!function_exists('zen_output_string_protected')) {
        function zen_output_string_protected($string)
        {
            return htmlspecialchars((string) $string, ENT_QUOTES);
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

    if (!function_exists('zen_icon')) {
        function zen_icon(string $icon, ?string $tooltip = null, string $size = '', bool $fixedWidth = false, bool $hidden = false): string
        {
            return '<i class="' . $icon . '"></i>';
        }
    }

    if (!function_exists('zen_href_link')) {
        function zen_href_link($page, $parameters = '')
        {
            return $page . ($parameters !== '' ? '?' . $parameters : '');
        }
    }

    if (!function_exists('zen_get_all_get_params')) {
        function zen_get_all_get_params($excludeArray = [])
        {
            $pairs = [];
            foreach ($_GET as $key => $value) {
                if (in_array($key, $excludeArray, true)) {
                    continue;
                }
                $pairs[] = $key . '=' . $value;
            }
            return implode('&', $pairs);
        }
    }
}

namespace Tests\Unit\testsSundry {

use Tests\Support\zcUnitTestCase;

class RenderedCountriesResourceTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        if (!defined('TABLE_HEADING_COUNTRY_NAME')) {
            define('TABLE_HEADING_COUNTRY_NAME', 'Country Name');
        }
        if (!defined('TABLE_HEADING_COUNTRY_CODES')) {
            define('TABLE_HEADING_COUNTRY_CODES', 'Country Codes');
        }
        if (!defined('TABLE_HEADING_COUNTRY_STATUS')) {
            define('TABLE_HEADING_COUNTRY_STATUS', 'Country Status');
        }
        if (!defined('TABLE_HEADING_ACTION')) {
            define('TABLE_HEADING_ACTION', 'Action');
        }
        if (!defined('TEXT_INFO_COUNTRY_CODE_2')) {
            define('TEXT_INFO_COUNTRY_CODE_2', 'ISO-2');
        }
        if (!defined('TEXT_INFO_COUNTRY_CODE_3')) {
            define('TEXT_INFO_COUNTRY_CODE_3', 'ISO-3');
        }
        if (!defined('TEXT_DISPLAY_NUMBER_OF_COUNTRIES')) {
            define('TEXT_DISPLAY_NUMBER_OF_COUNTRIES', 'Displaying countries');
        }
        if (!defined('MAX_DISPLAY_SEARCH_RESULTS')) {
            define('MAX_DISPLAY_SEARCH_RESULTS', 20);
        }
        if (!defined('MAX_DISPLAY_PAGE_LINKS')) {
            define('MAX_DISPLAY_PAGE_LINKS', 5);
        }
        if (!defined('IMAGE_NEW_COUNTRY')) {
            define('IMAGE_NEW_COUNTRY', 'New Country');
        }
        if (!defined('TEXT_RESOURCE_LIST_SEARCH_BUTTON')) {
            define('TEXT_RESOURCE_LIST_SEARCH_BUTTON', 'Search');
        }
        if (!defined('TEXT_RESOURCE_LIST_RESET_BUTTON')) {
            define('TEXT_RESOURCE_LIST_RESET_BUTTON', 'Reset');
        }
        if (!defined('TEXT_COUNTRIES_FILTER_STATUS_ALL')) {
            define('TEXT_COUNTRIES_FILTER_STATUS_ALL', 'All Statuses');
        }
        if (!defined('TEXT_COUNTRIES_FILTER_STATUS_ACTIVE')) {
            define('TEXT_COUNTRIES_FILTER_STATUS_ACTIVE', 'Active Countries');
        }
        if (!defined('TEXT_COUNTRIES_FILTER_STATUS_INACTIVE')) {
            define('TEXT_COUNTRIES_FILTER_STATUS_INACTIVE', 'Inactive Countries');
        }
        if (!defined('FILENAME_COUNTRIES')) {
            define('FILENAME_COUNTRIES', 'countries.php');
        }
        if (!defined('IMAGE_ICON_INFO')) {
            define('IMAGE_ICON_INFO', 'Info');
        }
        if (!defined('IMAGE_ICON_STATUS_OFF')) {
            define('IMAGE_ICON_STATUS_OFF', 'Off');
        }
        if (!defined('IMAGE_ICON_STATUS_ON')) {
            define('IMAGE_ICON_STATUS_ON', 'On');
        }
    }

    public function testRenderedCountriesTemplateIncludesSortableHeaderAndPersistsSortParameters(): void
    {
        $_GET = ['cmd' => 'countries', 'page' => '2', 'cID' => '17'];

        $formatter = new class {
            public function hasSearch(): bool
            {
                return true;
            }

            public function searchAction(): string
            {
                return 'countries.php';
            }

            public function hasFilters(): bool
            {
                return true;
            }

            public function filters(): array
            {
                return [[
                    'key' => 'status',
                    'parameter' => 'status_filter',
                    'label' => 'Country Status',
                    'type' => 'select',
                    'options' => ['' => 'All Statuses', '1' => 'Active Countries', '0' => 'Inactive Countries'],
                    'value' => '1',
                ]];
            }

            public function toolbarHiddenParameters(): array
            {
                return ['sort' => 'countries_name', 'direction' => 'asc'];
            }

            public function searchParameter(): string
            {
                return 'search';
            }

            public function searchValue(): string
            {
                return 'Belg';
            }

            public function searchPlaceholder(): string
            {
                return 'Search Country Name';
            }

            public function searchResetHref(): string
            {
                return 'countries.php?sort=countries_name&direction=asc';
            }

            public function isAlphabeticMode(): bool
            {
                return false;
            }

            public function getTableHeaders(): array
            {
                return [
                    ['headerClass' => 'hidden', 'title' => '', 'href' => null],
                    [
                        'headerClass' => 'dataTableHeadingContent col-sm-6',
                        'title' => 'Country Name',
                        'href' => 'countries.php?sort=countries_name&direction=desc',
                        'sortIndicator' => ' ↑',
                    ],
                    ['headerClass' => 'dataTableHeadingContent text-center', 'title' => 'ISO-2', 'href' => null],
                    ['headerClass' => 'dataTableHeadingContent text-center', 'title' => 'ISO-3', 'href' => null],
                    ['headerClass' => 'dataTableHeadingContent text-center', 'title' => 'Country Status', 'href' => null],
                ];
            }

            public function getPersistentLinkParameters(array $exclude = []): string
            {
                if ($exclude === ['page', 'cID', 'action']) {
                    return 'sort=countries_name&direction=asc';
                }
                return 'page=2&sort=countries_name&direction=asc';
            }

            public function getTableData(): array
            {
                return [[
                    'countries_id' => ['value' => '5', 'class' => 'hidden', 'original' => 5],
                    'countries_name' => ['value' => 'Belgium', 'class' => 'col-sm-6', 'original' => 'Belgium'],
                    'countries_iso_code_2' => ['value' => 'BE', 'class' => 'text-center', 'original' => 'BE'],
                    'countries_iso_code_3' => ['value' => 'BEL', 'class' => 'text-center', 'original' => 'BEL'],
                    'status' => ['value' => '1', 'class' => 'text-center dataTableButtonCell', 'original' => 1],
                ], [
                    'countries_id' => ['value' => '17', 'class' => 'hidden', 'original' => 17],
                    'countries_name' => ['value' => 'Bahrain', 'class' => 'col-sm-6', 'original' => 'Bahrain'],
                    'countries_iso_code_2' => ['value' => 'BH', 'class' => 'text-center', 'original' => 'BH'],
                    'countries_iso_code_3' => ['value' => 'BHR', 'class' => 'text-center', 'original' => 'BHR'],
                    'status' => ['value' => '1', 'class' => 'text-center dataTableButtonCell', 'original' => 1],
                ]];
            }

            public function isRowSelected(array $tableRow): bool
            {
                return (int) $tableRow['countries_id']['original'] === 5;
            }

            public function getSelectedRowLink(array $tableRow): string
            {
                return 'countries.php?page=2&sort=countries_name&direction=asc&cID=' . $tableRow['countries_id']['original'] . '&action=edit';
            }

            public function getNotSelectedRowLink(array $tableRow): string
            {
                return 'countries.php?page=2&sort=countries_name&direction=asc&cID=' . $tableRow['countries_id']['original'];
            }
        };

        $tableController = new class {
            public function statusFormParameters(): string
            {
                return 'sort=countries_name&direction=asc&action=setstatus';
            }

            public function shouldShowNewCountryAction(): bool
            {
                return true;
            }

            public function newCountryUrl(): string
            {
                return 'countries.php?page=2&sort=countries_name&direction=asc&action=new';
            }

            public function getBoxHeader(): array
            {
                return [];
            }

            public function getBoxContent(): array
            {
                return [];
            }
        };

        $countriesSplit = new class {
            public function display_count($queryNumRows, $resultsPerPage, $currentPage, $countText): string
            {
                return 'count:' . $queryNumRows . ':' . $resultsPerPage . ':' . $currentPage . ':' . $countText;
            }

            public function display_links($queryNumRows, $resultsPerPage, $maxPageLinks, $currentPage, $parameters = '', $pageName = 'page'): string
            {
                return 'links:' . $parameters . ':' . $pageName;
            }
        };

        $countriesQueryNumRows = 1;
        $currentPage = '2';
        $pageParameter = 'page=2&';
        $pageHeading = 'Countries';
        $pageTopLinkHtml = '<a href="iso-codes">ISO</a>';

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/countries_resource.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('name="countries-search"', $output);
        $this->assertStringContainsString('js-resource-search-form', $output);
        $this->assertStringContainsString('data-lookahead-min="3"', $output);
        $this->assertStringContainsString('data-lookahead-delay="350"', $output);
        $this->assertStringContainsString('name="status_filter"', $output);
        $this->assertStringContainsString('All Statuses', $output);
        $this->assertStringContainsString('Active Countries', $output);
        $this->assertStringContainsString('Inactive Countries', $output);
        $this->assertStringContainsString('<option value="1" selected>', $output);
        $this->assertStringContainsString('name="search" value="Belg"', $output);
        $this->assertStringContainsString('class="form-control js-resource-search-input"', $output);
        $this->assertStringContainsString('countries.php?sort=countries_name&direction=asc', $output);
        $this->assertStringContainsString('countries.php?sort=countries_name&direction=desc', $output);
        $this->assertStringContainsString('Country Name ↑', $output);
        $this->assertStringContainsString('links:sort=countries_name&direction=asc:page', $output);
        $this->assertStringNotContainsString('links:page=2', $output);
        $this->assertStringContainsString('page=2&sort=countries_name&direction=asc&action=new', $output);
        $this->assertStringContainsString('tr.country-row-link {', $output);
        $this->assertStringContainsString('cursor: pointer;', $output);
        $this->assertStringContainsString('id="defaultSelected" class="dataTableRowSelected country-row-link js-resource-row-link"', $output);
        $this->assertStringContainsString('href="countries.php?page=2&amp;sort=countries_name&amp;direction=asc&amp;cID=17" class="country-row-link-text country-row-link-block"', $output);
        $this->assertStringContainsString('data-row-link="countries.php?page=2&sort=countries_name&direction=asc&cID=17"', $output);
        $this->assertStringContainsString("document.addEventListener('click'", $output);
        $this->assertStringContainsString("event.target.closest('a, button, form, input, select, textarea, label')", $output);
        $this->assertStringContainsString("var row = event.target.closest('tr.js-resource-row-link');", $output);
        $this->assertStringContainsString("form.js-resource-search-form[data-lookahead=\"true\"]", $output);
        $this->assertStringContainsString('if (value.length < minLength)', $output);
        $this->assertStringContainsString('form.submit();', $output);
        $this->assertStringContainsString("document.querySelectorAll('select.js-resource-filter-select')", $output);
        $this->assertStringContainsString("select.form.submit();", $output);
        $this->assertStringContainsString('input.focus();', $output);
        $this->assertStringContainsString('input.setSelectionRange(input.value.length, input.value.length);', $output);
        $this->assertStringContainsString('name="setstatus_5"', $output);
        $this->assertStringContainsString('action=setstatus', $output);
        $this->assertStringNotContainsString('cID=17&amp;action=setstatus', $output);
        $this->assertStringContainsString('onmousedown="event.stopPropagation();"', $output);
        $this->assertStringContainsString('onclick="event.stopPropagation(); event.preventDefault(); this.form.submit(); return false;"', $output);
        $this->assertStringContainsString("this.closest('form').submit()", $output);
        $this->assertStringNotContainsString('Undefined', $output);
    }

    public function testRenderedCountriesTemplateDisplaysAlphabeticPaginatorInBrowseMode(): void
    {
        $_GET = ['cmd' => 'countries', 'page' => 'B'];

        $formatter = new class {
            public function hasSearch(): bool
            {
                return true;
            }

            public function hasFilters(): bool
            {
                return false;
            }

            public function toolbarHiddenParameters(): array
            {
                return [];
            }

            public function searchAction(): string
            {
                return 'countries.php';
            }

            public function searchParameter(): string
            {
                return 'search';
            }

            public function searchValue(): string
            {
                return '';
            }

            public function searchPlaceholder(): string
            {
                return 'Search Country Name';
            }

            public function searchResetHref(): string
            {
                return 'countries.php';
            }

            public function isAlphabeticMode(): bool
            {
                return true;
            }

            public function getTableHeaders(): array
            {
                return [
                    ['headerClass' => 'hidden', 'title' => '', 'href' => null],
                    ['headerClass' => 'dataTableHeadingContent col-sm-6', 'title' => 'Country Name', 'href' => null],
                    ['headerClass' => 'dataTableHeadingContent text-center', 'title' => 'ISO-2', 'href' => null],
                    ['headerClass' => 'dataTableHeadingContent text-center', 'title' => 'ISO-3', 'href' => null],
                    ['headerClass' => 'dataTableHeadingContent text-center', 'title' => 'Country Status', 'href' => null],
                ];
            }

            public function getPersistentLinkParameters(array $exclude = []): string
            {
                return '';
            }

            public function getTableData(): array
            {
                return [[
                    'countries_id' => ['value' => '17', 'class' => 'hidden', 'original' => 17],
                    'countries_name' => ['value' => 'Bahrain', 'class' => 'col-sm-6', 'original' => 'Bahrain'],
                    'countries_iso_code_2' => ['value' => 'BH', 'class' => 'text-center', 'original' => 'BH'],
                    'countries_iso_code_3' => ['value' => 'BHR', 'class' => 'text-center', 'original' => 'BHR'],
                    'status' => ['value' => '1', 'class' => 'text-center dataTableButtonCell', 'original' => 1],
                ]];
            }

            public function isRowSelected(array $tableRow): bool
            {
                return true;
            }

            public function getSelectedRowLink(array $tableRow): string
            {
                return 'countries.php?page=B&cID=17&action=edit';
            }

            public function getNotSelectedRowLink(array $tableRow): string
            {
                return 'countries.php?page=B&cID=17';
            }
        };

        $tableController = new class {
            public function statusFormParameters(): string
            {
                return 'action=setstatus';
            }

            public function shouldShowNewCountryAction(): bool
            {
                return true;
            }

            public function newCountryUrl(): string
            {
                return 'countries.php?action=new';
            }

            public function getBoxHeader(): array
            {
                return [];
            }

            public function getBoxContent(): array
            {
                return [];
            }
        };

        $countriesSplit = new class {
            public function display_count($queryNumRows, $resultsPerPage, $currentPage, $countText): string
            {
                return 'count:' . $currentPage;
            }

            public function display_links($queryNumRows, $resultsPerPage, $maxPageLinks, $currentPage, $parameters = '', $pageName = 'page'): string
            {
                return 'alphabet-links:' . $currentPage . ':' . $parameters . ':' . $pageName;
            }
        };

        $countriesQueryNumRows = 4;
        $currentPage = 'B';
        $pageParameter = 'page=B&';
        $pageHeading = 'Countries';
        $pageTopLinkHtml = '<a href="iso-codes">ISO</a>';

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/countries_resource.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('count:B', $output);
        $this->assertStringContainsString('<td class="text-right">alphabet-links:B::page</td>', $output);
        $this->assertStringContainsString('countries.php?action=new', $output);
    }
}
}
