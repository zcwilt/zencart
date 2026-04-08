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
        function zen_href_link($page, $parameters = '', $connection = 'NONSSL')
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

class RenderedModulesResourceTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        foreach ([
            'TEXT_ENABLED' => 'Enabled',
            'TEXT_AVAILABLE' => 'Available',
            'TABLE_HEADING_MODULES' => 'Modules',
            'TABLE_HEADING_SORT_ORDER' => 'Sort Order',
            'TABLE_HEADING_ORDERS_STATUS' => 'Order Status',
            'TABLE_HEADING_ACTION' => 'Action',
            'TEXT_MODULE_DIRECTORY' => 'Module Directory:',
            'IMAGE_ICON_INFO' => 'Info',
        ] as $constant => $value) {
            if (!defined($constant)) {
                define($constant, $value);
            }
        }
    }

    public function testModulesTemplateRendersGroupedListingAndInfobox(): void
    {
        $modulesController = new class {
            public function availableNotifications(): array { return []; }
            public function isPaymentSet(): bool { return true; }
            public function groupedRows(): array
            {
                return [
                    'enabled' => [[
                        'selected' => true,
                        'rowLink' => 'modules.php?set=payment&module=cod',
                        'title' => 'Cash on Delivery',
                        'code' => 'cod',
                        'sortOrder' => '10',
                        'statusIcon' => '<i class="status-green"></i>',
                        'orderStatus' => 'Processing',
                        'infoLink' => 'modules.php?set=payment&module=cod',
                    ]],
                    'available' => [[
                        'selected' => false,
                        'rowLink' => 'modules.php?set=payment&module=paypal',
                        'title' => 'PayPal',
                        'code' => 'paypal',
                        'sortOrder' => '',
                        'statusIcon' => '<i class="status-red"></i>',
                        'orderStatus' => 'Default',
                        'infoLink' => 'modules.php?set=payment&module=paypal',
                    ]],
                ];
            }
            public function getBoxHeader(): array { return [['text' => '<h4>Cash on Delivery</h4>']]; }
            public function getBoxContent(): array { return [['text' => 'module info']]; }
            public function moduleDirectory(): string { return '/var/www/includes/modules/payment'; }
            public function helpBody(): string { return ''; }
            public function helpTitle(): string { return ''; }
        };
        $pageHeading = 'Payment Modules';

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/modules_resource.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('<h1>Payment Modules</h1>', $output);
        $this->assertStringContainsString('Enabled Modules', $output);
        $this->assertStringContainsString('Available Modules', $output);
        $this->assertStringContainsString('Cash on Delivery', $output);
        $this->assertStringContainsString('PayPal', $output);
        $this->assertStringContainsString('Processing', $output);
        $this->assertStringContainsString('/var/www/includes/modules/payment', $output);
        $this->assertStringContainsString('<div class="box">1:1</div>', $output);
    }

    public function testModulesTemplateRendersHelpModalWhenHelpBodyPresent(): void
    {
        $modulesController = new class {
            public function availableNotifications(): array { return []; }
            public function isPaymentSet(): bool { return false; }
            public function groupedRows(): array { return ['enabled' => [], 'available' => []]; }
            public function getBoxHeader(): array { return []; }
            public function getBoxContent(): array { return []; }
            public function moduleDirectory(): string { return '/tmp/modules/shipping'; }
            public function helpBody(): string { return '<p>Help body</p>'; }
            public function helpTitle(): string { return 'Module Help'; }
        };
        $pageHeading = 'Shipping Modules';

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/modules_resource.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Shipping Modules', $output);
        $this->assertStringContainsString('id="helpModal"', $output);
        $this->assertStringContainsString('Module Help', $output);
        $this->assertStringContainsString('<p>Help body</p>', $output);
    }
}
}
