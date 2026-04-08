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

    if (!function_exists('zen_hide_session_id')) {
        function zen_hide_session_id()
        {
            return '<input type="hidden" name="zenid" value="test">';
        }
    }

    if (!function_exists('zen_draw_hidden_field')) {
        function zen_draw_hidden_field($name, $value)
        {
            return '<input type="hidden" name="' . htmlspecialchars((string) $name, ENT_QUOTES) . '" value="' . htmlspecialchars((string) $value, ENT_QUOTES) . '">';
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

    if (!function_exists('zen_draw_separator')) {
        function zen_draw_separator($image, $width, $height)
        {
            return '<img src="' . $image . '" width="' . $width . '" height="' . $height . '">';
        }
    }
}

namespace Tests\Unit\testsSundry {

use Tests\Support\zcUnitTestCase;

class RenderedStatsCustomersReferralsReportTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        foreach ([
            'FILENAME_STATS_CUSTOMERS_REFERRALS' => 'stats_customers_referrals.php',
            'TEXT_INFO_SELECT_REFERRAL' => 'Select Referral',
            'TEXT_INFO_START_DATE' => 'Start Date',
            'TEXT_INFO_END_DATE' => 'End Date',
            'IMAGE_DISPLAY' => 'Display',
            'TEXT_ORDER_NUMBER' => 'Order #',
            'TEXT_COUPON_ID' => 'Coupon:',
            'IMAGE_DETAILS' => 'Details',
        ] as $constant => $value) {
            if (!defined($constant)) {
                define($constant, $value);
            }
        }
    }

    public function testTemplateRendersFiltersAndOrderTotals(): void
    {
        $pageHeading = 'Customer Referrals';
        $startDate = '04-01-2026';
        $endDate = '04-08-2026';
        $referralCode = 'newsletter';
        $referralOptions = [
            ['id' => '0', 'text' => 'Unknown'],
            ['id' => 'newsletter', 'text' => 'newsletter (5)'],
        ];
        $orderEntries = [[
            'datePurchasedLong' => 'April 8, 2026',
            'ordersId' => 17,
            'couponCode' => 'SAVE10',
            'detailsLink' => 'orders.php?oID=17&action=edit',
            'totals' => [
                ['title' => 'Sub-Total:', 'text' => '$20.00', 'class' => 'ot-subtotal'],
                ['title' => 'Total:', 'text' => '$21.50', 'class' => 'ot-total'],
            ],
        ]];

        ob_start();
        require DIR_FS_ADMIN . 'includes/templates/stats_customers_referrals_report.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Customer Referrals', $output);
        $this->assertStringContainsString('name="start_date" value="04-01-2026"', $output);
        $this->assertStringContainsString('name="end_date" value="04-08-2026"', $output);
        $this->assertStringContainsString('Order # 17', $output);
        $this->assertStringContainsString('SAVE10', $output);
        $this->assertStringContainsString('orders.php?oID=17&action=edit', $output);
        $this->assertStringContainsString('$21.50', $output);
    }
}
}
