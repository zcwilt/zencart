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

    if (!function_exists('zen_draw_radio_field')) {
        function zen_draw_radio_field($name, $value, $checked = false)
        {
            return '<input type="radio" name="' . htmlspecialchars((string) $name, ENT_QUOTES) . '" value="' . htmlspecialchars((string) $value, ENT_QUOTES) . '"' . ($checked ? ' checked' : '') . '>';
        }
    }

    if (!function_exists('zen_draw_label')) {
        function zen_draw_label($text, $for, $attributes = '')
        {
            return '<label for="' . htmlspecialchars((string) $for, ENT_QUOTES) . '" ' . $attributes . '>' . $text . '</label>';
        }
    }

    if (!function_exists('zen_draw_input_field')) {
        function zen_draw_input_field($name, $value = '', $attributes = '')
        {
            return '<input type="text" name="' . htmlspecialchars((string) $name, ENT_QUOTES) . '" value="' . htmlspecialchars((string) $value, ENT_QUOTES) . '" ' . $attributes . '>';
        }
    }

    if (!function_exists('zen_draw_pull_down_menu')) {
        function zen_draw_pull_down_menu($name, $values, $default = '', $attributes = '')
        {
            return '<select name="' . htmlspecialchars((string) $name, ENT_QUOTES) . '" ' . $attributes . '></select>';
        }
    }

    if (!function_exists('zen_draw_file_field')) {
        function zen_draw_file_field($name, $value = '', $attributes = '')
        {
            return '<input type="file" name="' . htmlspecialchars((string) $name, ENT_QUOTES) . '" ' . $attributes . '>';
        }
    }

    if (!function_exists('zen_draw_textarea_field')) {
        function zen_draw_textarea_field($name, $wrap, $cols, $rows, $text = '', $attributes = '')
        {
            return '<textarea name="' . htmlspecialchars((string) $name, ENT_QUOTES) . '" ' . $attributes . '>' . htmlspecialchars((string) $text, ENT_QUOTES) . '</textarea>';
        }
    }

    if (!function_exists('zen_set_field_length')) {
        function zen_set_field_length($table, $field)
        {
            return '';
        }
    }

    if (!function_exists('zen_datepicker_format_full')) {
        function zen_datepicker_format_full()
        {
            return 'yyyy-mm-dd';
        }
    }

    if (!function_exists('zen_draw_checkbox_field')) {
        function zen_draw_checkbox_field($name, $value = '', $checked = false)
        {
            return '<input type="checkbox" name="' . htmlspecialchars((string) $name, ENT_QUOTES) . '" value="' . htmlspecialchars((string) $value, ENT_QUOTES) . '"' . ($checked ? ' checked' : '') . '>';
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

class RenderedBannerManagerResourceTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        foreach ([
            'CHARSET' => 'utf-8',
            'FILENAME_BANNER_MANAGER' => 'banner_manager.php',
            'FILENAME_BANNER_STATISTICS' => 'banner_statistics.php',
            'TABLE_BANNERS' => 'banners',
            'TABLE_HEADING_BANNERS' => 'Banners',
            'TABLE_HEADING_GROUPS' => 'Groups',
            'TABLE_HEADING_POSITIONS' => 'Positions',
            'TABLE_HEADING_STATISTICS' => 'Statistics',
            'TABLE_HEADING_STATUS' => 'Status',
            'TABLE_HEADING_BANNER_OPEN_NEW_WINDOWS' => 'New Window',
            'TABLE_HEADING_BANNER_SORT_ORDER' => 'Sort Order',
            'TABLE_HEADING_ACTION' => 'Action',
            'TEXT_LEGEND' => 'Legend',
            'TEXT_LEGEND_BANNER_OPEN_NEW_WINDOWS' => 'Open New Windows',
            'IMAGE_ICON_STATUS_ON' => 'On',
            'IMAGE_ICON_STATUS_OFF' => 'Off',
            'IMAGE_ICON_BANNER_OPEN_NEW_WINDOWS_ON' => 'Open',
            'IMAGE_ICON_BANNER_OPEN_NEW_WINDOWS_OFF' => 'Closed',
            'TEXT_DISPLAY_NUMBER_OF_BANNERS' => 'Displaying banners',
            'MAX_DISPLAY_SEARCH_RESULTS' => 20,
            'MAX_DISPLAY_PAGE_LINKS' => 5,
            'IMAGE_NEW_BANNER' => 'New Banner',
            'TEXT_BANNERS_STATUS' => 'Banner Status',
            'TEXT_BANNERS_ACTIVE' => 'Active',
            'TEXT_BANNERS_NOT_ACTIVE' => 'Inactive',
            'TEXT_INFO_BANNER_STATUS' => 'Status help',
            'TEXT_BANNERS_OPEN_NEW_WINDOWS' => 'Open in New Window',
            'TEXT_YES' => 'Yes',
            'TEXT_NO' => 'No',
            'TEXT_INFO_BANNER_OPEN_NEW_WINDOWS' => 'Window help',
            'TEXT_BANNERS_TITLE' => 'Title',
            'TEXT_BANNERS_URL' => 'URL',
            'TEXT_BANNERS_GROUP' => 'Group',
            'TEXT_BANNERS_NEW_GROUP' => 'New Group',
            'TEXT_BANNERS_IMAGE_LOCAL' => 'Local Image',
            'TEXT_BANNERS_CURRENT_IMAGE' => 'Current Image',
            'TEXT_BANNERS_IMAGE' => 'Upload Image',
            'TEXT_BANNERS_IMAGE_TARGET' => 'Image Target',
            'TEXT_BANNER_IMAGE_TARGET_INFO' => 'Target Info',
            'TEXT_BANNERS_HTML_TEXT' => 'HTML Text',
            'TEXT_BANNERS_HTML_TEXT_INFO' => 'HTML help',
            'TEXT_BANNERS_ALL_SORT_ORDER' => 'Sort',
            'TEXT_BANNERS_ALL_SORT_ORDER_INFO' => 'Sort help',
            'TEXT_BANNERS_SCHEDULED_AT' => 'Schedule',
            'TEXT_BANNERS_EXPIRES_ON' => 'Expires',
            'ERROR_INVALID_SCHEDULED_DATE' => 'Invalid scheduled date',
            'ERROR_INVALID_EXPIRES_DATE' => 'Invalid expires date',
            'TEXT_BANNERS_OR_AT' => 'or at',
            'TEXT_BANNERS_IMPRESSIONS' => 'Impressions',
            'IMAGE_INSERT' => 'Insert',
            'IMAGE_UPDATE' => 'Update',
            'IMAGE_CANCEL' => 'Cancel',
            'TEXT_BANNERS_BANNER_NOTE' => 'Banner note',
            'TEXT_BANNERS_INSERT_NOTE' => 'Insert note',
            'TEXT_BANNERS_EXPIRY_NOTE' => 'Expiry note',
            'TEXT_BANNERS_SCHEDULE_NOTE' => 'Schedule note',
            'DIR_WS_INCLUDES' => DIR_FS_ADMIN . 'includes/',
        ] as $constant => $value) {
            if (!defined($constant)) {
                define($constant, $value);
            }
        }
    }

    public function testBannerManagerTemplateRendersListingBranch(): void
    {
        $bannerManagerController = new class {
            public function getAction(): string { return ''; }
            public function selectedBanner(): ?object { return null; }
            public function formValues(): ?object { return null; }
            public function shouldShowLegend(): bool { return true; }
            public function isFormMode(): bool { return false; }
            public function listingRows(): array
            {
                return [[
                    'selected' => true,
                    'rowLink' => 'banner_manager.php?page=2&bID=4&action=new',
                    'popupLink' => 'popup_image.php?banner=4',
                    'title' => 'Spring Sale',
                    'group' => 'homepage',
                    'positions' => ['Side Box', 'Footer'],
                    'statistics' => '12 / 2',
                    'status' => 1,
                    'statusLink' => 'banner_manager.php?page=2&bID=4&action=setflag&flag=0',
                    'statusTitle' => 'On',
                    'openNewWindow' => 1,
                    'openWindowLink' => 'banner_manager.php?page=2&bID=4&action=setbanners_open_new_windows&flagbanners_open_new_windows=0',
                    'sortOrder' => 3,
                    'statisticsLink' => 'banner_statistics.php?page=2&bID=4',
                    'infoLink' => 'banner_manager.php?page=2&bID=4',
                ]];
            }
            public function getBoxHeader(): array { return [['text' => '<h4>Spring Sale</h4>']]; }
            public function getBoxContent(): array { return [['text' => 'info']]; }
            public function splitResults(): object
            {
                return new class {
                    public function display_count(...$args): string { return 'count'; }
                    public function display_links(...$args): string { return 'links'; }
                };
            }
            public function queryNumRows(): int { return 1; }
            public function currentPage(): int { return 2; }
            public function listingPaginationParameters(): string { return 'bID=4'; }
            public function newBannerUrl(): string { return 'banner_manager.php?action=new'; }
        };
        $pageHeading = 'Banner Manager';

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/banner_manager_resource.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('<h1>Banner Manager</h1>', $output);
        $this->assertStringContainsString('Legend', $output);
        $this->assertStringContainsString('Spring Sale', $output);
        $this->assertStringContainsString('banner_manager.php?page=2&bID=4&action=setflag&flag=0', $output);
        $this->assertStringContainsString('banner_statistics.php?page=2&bID=4', $output);
        $this->assertStringContainsString('banner_manager.php?action=new', $output);
        $this->assertStringContainsString('count', $output);
        $this->assertStringContainsString('links', $output);
    }

    public function testBannerManagerTemplateRendersFormBranch(): void
    {
        $bannerManagerController = new class {
            public function getAction(): string { return 'new'; }
            public function selectedBanner(): ?object { return null; }
            public function shouldShowLegend(): bool { return false; }
            public function isFormMode(): bool { return true; }
            public function formAction(): string { return 'upd'; }
            public function currentBannerId(): int { return 9; }
            public function formPostParameters(): string { return 'page=3&action=upd'; }
            public function cancelFormUrl(): string { return 'banner_manager.php?page=3&bID=9'; }
            public function groupOptions(): array { return [['id' => 'homepage', 'text' => 'homepage']]; }
            public function abbreviatedImagesDirectory(): string { return '/images/'; }
            public function formValues(): ?object
            {
                $values = new stdClass();
                $values->status = 1;
                $values->banners_open_new_windows = 0;
                $values->banners_title = 'Hero Banner';
                $values->banners_url = 'https://example.com';
                $values->banners_group = 'homepage';
                $values->banners_image = 'banners/hero.jpg';
                $values->banners_html_text = '<b>Hero</b>';
                $values->banners_sort_order = 7;
                $values->date_scheduled = '2026-04-07';
                $values->expires_date = '2026-05-07';
                $values->expires_impressions = 2500;
                return $values;
            }
        };
        $pageHeading = 'Banner Manager';

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/banner_manager_resource.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('action="banner_manager?page=3&action=upd"', $output);
        $this->assertStringContainsString('name="banners_id" value="9"', $output);
        $this->assertStringContainsString('name="banners_title" value="Hero Banner"', $output);
        $this->assertStringContainsString('name="banners_url" value="https://example.com"', $output);
        $this->assertStringContainsString('name="expires_impressions" value="2500"', $output);
        $this->assertStringContainsString('banner_manager.php?page=3&bID=9', $output);
        $this->assertStringContainsString('Upload Image', $output);
        $this->assertStringContainsString('Banner note', $output);
    }
}
}
