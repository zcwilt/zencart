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

    if (!function_exists('zen_href_link')) {
        function zen_href_link($page, $parameters = '')
        {
            return $page . ($parameters !== '' ? '?' . $parameters : '');
        }
    }

    if (!function_exists('zen_image')) {
        function zen_image($src, $alt = '')
        {
            return '<img src="' . htmlspecialchars((string) $src, ENT_QUOTES) . '" alt="' . htmlspecialchars((string) $alt, ENT_QUOTES) . '">';
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

    if (!function_exists('zen_draw_label')) {
        function zen_draw_label($text, $for, $attributes = '')
        {
            return '<label for="' . htmlspecialchars((string) $for, ENT_QUOTES) . '" ' . $attributes . '>' . $text . '</label>';
        }
    }

    if (!function_exists('zen_draw_pull_down_menu')) {
        function zen_draw_pull_down_menu($name, $values, $default = '', $attributes = '')
        {
            return '<select name="' . htmlspecialchars((string) $name, ENT_QUOTES) . '" ' . $attributes . '></select>';
        }
    }

    if (!function_exists('zen_draw_input_field')) {
        function zen_draw_input_field($name, $value = '', $attributes = '')
        {
            return '<input type="text" name="' . htmlspecialchars((string) $name, ENT_QUOTES) . '" value="' . htmlspecialchars((string) $value, ENT_QUOTES) . '" ' . $attributes . '>';
        }
    }

    if (!function_exists('zen_js_zone_list')) {
        function zen_js_zone_list($country, $form, $field)
        {
            return '// zone list';
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

use stdClass;
use Tests\Support\zcUnitTestCase;

class RenderedGeoZonesResourceTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        foreach ([
            'TABLE_HEADING_COUNTRY_NAME' => 'Country',
            'TABLE_HEADING_COUNTRY_ZONE' => 'Zone',
            'TABLE_HEADING_ACTION' => 'Action',
            'TABLE_HEADING_TAX_ZONES' => 'Tax Zones',
            'TABLE_HEADING_TAX_ZONES_DESCRIPTION' => 'Description',
            'TABLE_HEADING_STATUS' => 'Status',
            'TEXT_LEGEND' => 'Legend',
            'TEXT_LEGEND_TAX_AND_ZONES' => 'Tax and Zones',
            'TEXT_LEGEND_ONLY_ZONES' => 'Zones Only',
            'TEXT_LEGEND_NOT_CONF' => 'Not Configured',
            'TEXT_DISPLAY_NUMBER_OF_GEO_ZONES' => 'Displaying zones',
            'TEXT_INFO_HEADING_NEW_SUB_ZONE' => 'New Sub Zone',
            'TEXT_INFO_COUNTRY' => 'Country',
            'TEXT_INFO_COUNTRY_ZONE' => 'Country Zone',
            'JS_STATE_SELECT' => 'Select',
            'IMAGE_BACK' => 'Back',
            'IMAGE_INSERT' => 'Insert',
            'IMAGE_ICON_INFO' => 'Info',
            'ICON_FOLDER' => 'Folder',
            'FILENAME_GEO_ZONES' => 'geo_zones.php',
            'DIR_WS_ICONS' => 'images/icons/',
            'MAX_DISPLAY_SEARCH_RESULTS' => 20,
            'MAX_DISPLAY_PAGE_LINKS' => 5,
        ] as $constant => $value) {
            if (!defined($constant)) {
                define($constant, $value);
            }
        }
    }

    public function testGeoZonesTemplateRendersGeoZoneListBranch(): void
    {
        $geoZonesController = new class {
            public function isDetailMode(): bool { return false; }
            public function listingRows(): array
            {
                return [[
                    'selected' => true,
                    'rowLink' => 'geo_zones.php?zID=5&action=list',
                    'folderLink' => 'geo_zones.php?zID=5&action=list',
                    'geo_zone_name' => 'North America',
                    'geo_zone_description' => 'NA taxes',
                    'status_icon' => 'status-green',
                    'infoLink' => 'geo_zones.php?zID=5',
                ]];
            }
            public function splitResults(): object
            {
                return new class {
                    public function display_count(...$args): string { return 'count'; }
                    public function display_links(...$args): string { return 'links'; }
                };
            }
            public function queryNumRows(): int { return 1; }
            public function selectedGeoZone(): ?object
            {
                $zone = new stdClass();
                $zone->geo_zone_id = 5;
                return $zone;
            }
            public function selectedSubZone(): ?object { return null; }
            public function getAction(): string { return ''; }
            public function getSubAction(): string { return ''; }
            public function currentZonePage(): int { return 1; }
            public function currentSubZonePage(): int { return 1; }
            public function currentZoneId(): int { return 5; }
            public function currentZoneName(): string { return 'North America'; }
            public function showZoneScript(): bool { return false; }
            public function detailPaginationParameters(): string { return 'zpage=1&zID=5&action=list'; }
            public function topLevelPaginationParameters(): string { return ''; }
            public function backToZoneListUrl(): string { return 'geo_zones.php?zpage=1&zID=5'; }
            public function newSubZoneUrl(): string { return 'geo_zones.php?zpage=1&zID=5&action=list&saction=new'; }
            public function newGeoZoneUrl(): string { return 'geo_zones.php?zpage=1&zID=5&action=new_zone'; }
            public function getBoxHeader(): array { return [['text' => '<h4>North America</h4>']]; }
            public function getBoxContent(): array { return [['text' => 'info']]; }
        };
        $pageHeading = 'Zone Definitions - Taxes, Payment and Shipping';

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/geo_zones_resource.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Zone Definitions - Taxes, Payment and Shipping', $output);
        $this->assertStringContainsString('Legend', $output);
        $this->assertStringContainsString('North America', $output);
        $this->assertStringContainsString('action=new_zone', $output);
    }

    public function testGeoZonesTemplateRendersSubZoneDetailBranch(): void
    {
        $geoZonesController = new class {
            public function isDetailMode(): bool { return true; }
            public function listingRows(): array
            {
                return [[
                    'selected' => true,
                    'rowLink' => 'geo_zones.php?zID=7&action=list&sID=10&saction=edit',
                    'countries_name' => 'United States',
                    'zone_name' => 'Alabama',
                    'infoLink' => 'geo_zones.php?zID=7&action=list&sID=10',
                ]];
            }
            public function splitResults(): object
            {
                return new class {
                    public function display_count(...$args): string { return 'count'; }
                    public function display_links(...$args): string { return 'links'; }
                };
            }
            public function queryNumRows(): int { return 1; }
            public function selectedGeoZone(): ?object { return null; }
            public function selectedSubZone(): ?object
            {
                $zone = new stdClass();
                $zone->association_id = 10;
                return $zone;
            }
            public function getAction(): string { return 'list'; }
            public function getSubAction(): string { return ''; }
            public function currentZonePage(): int { return 1; }
            public function currentSubZonePage(): int { return 1; }
            public function currentZoneId(): int { return 7; }
            public function currentZoneName(): string { return 'US Tax Zone'; }
            public function showZoneScript(): bool { return true; }
            public function detailPaginationParameters(): string { return 'zpage=1&zID=7&action=list'; }
            public function topLevelPaginationParameters(): string { return ''; }
            public function backToZoneListUrl(): string { return 'geo_zones.php?zpage=1&zID=7'; }
            public function newSubZoneUrl(): string { return 'geo_zones.php?zpage=1&zID=7&action=list&spage=1&sID=10&saction=new'; }
            public function newGeoZoneUrl(): string { return 'geo_zones.php?zpage=1&zID=7&action=new_zone'; }
            public function getBoxHeader(): array { return [['text' => '<h4>United States</h4>']]; }
            public function getBoxContent(): array { return [['text' => 'info']]; }
        };
        $pageHeading = 'Zone Definitions - Taxes, Payment and Shipping';

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/geo_zones_resource.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('US Tax Zone', $output);
        $this->assertStringContainsString('United States', $output);
        $this->assertStringContainsString('Alabama', $output);
        $this->assertStringContainsString('Back', $output);
        $this->assertStringContainsString('saction=new', $output);
    }
}
}
