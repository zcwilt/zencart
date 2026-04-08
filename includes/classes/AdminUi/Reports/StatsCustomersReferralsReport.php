<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Reports;

use Zencart\AdminUi\AdminPageData;

class StatsCustomersReferralsReport extends AdminReport
{
    public function buildPage(): AdminPageData
    {
        $context = $this->notifyBuildPageStart();

        global $db;

        $startDate = $this->request->string('start_date', date('m-d-Y'));
        $endDate = $this->request->string('end_date', date('m-d-Y'));
        $referralCode = $this->request->string('referral_code', '0');

        $customersReferralQuery = "SELECT customers_referral, count(*) AS count
                                     FROM " . TABLE_CUSTOMERS . "
                                    WHERE customers_referral != ''
                                 GROUP BY customers_referral";
        $customersReferral = $db->Execute($customersReferralQuery);

        $referralOptions = [
            [
                'id' => '0',
                'text' => TEXT_REFERRAL_UNKNOWN,
            ],
        ];

        foreach ($customersReferral as $customerReferral) {
            $referralOptions[] = [
                'id' => $customerReferral['customers_referral'],
                'text' => $customerReferral['customers_referral'] . ' (' . $customerReferral['count'] . ')',
            ];
        }

        [$sqlStartDate, $sqlEndDate] = $this->normalizeDateRange($startDate, $endDate);

        if ($referralCode === '0') {
            $customersOrdersQuery = "SELECT c.customers_id, c.customers_referral, o.orders_id, o.date_purchased, o.order_total, o.coupon_code
                                       FROM " . TABLE_CUSTOMERS . " c,
                                            " . TABLE_ORDERS . " o
                                      WHERE c.customers_id = o.customers_id
                                        AND c.customers_referral = ''
                                        AND (o.date_purchased >= :sd: AND o.date_purchased <= :ed:)
                                   ORDER BY o.date_purchased, o.orders_id";
        } else {
            $customersOrdersQuery = "SELECT c.customers_id, c.customers_referral, o.orders_id, o.date_purchased, o.order_total, o.coupon_code
                                       FROM " . TABLE_CUSTOMERS . " c,
                                            " . TABLE_ORDERS . " o
                                      WHERE c.customers_id = o.customers_id
                                        AND c.customers_referral = :refcode:
                                        AND (o.date_purchased >= :sd: AND o.date_purchased <= :ed:)
                                   ORDER BY o.date_purchased, o.orders_id";
        }

        $customersOrdersQuery = $db->bindVars($customersOrdersQuery, ':ed:', $sqlEndDate, 'date');
        $customersOrdersQuery = $db->bindVars($customersOrdersQuery, ':sd:', $sqlStartDate, 'date');
        $customersOrdersQuery = $db->bindVars($customersOrdersQuery, ':refcode:', $referralCode, 'string');

        $customersOrders = $db->Execute($customersOrdersQuery);

        if (!class_exists('order')) {
            include DIR_FS_CATALOG . DIR_WS_CLASSES . 'order.php';
        }

        $orderEntries = [];
        foreach ($customersOrders as $customersOrder) {
            $order = new \order($customersOrder['orders_id']);
            $totals = [];
            foreach ($order->totals as $total) {
                $totals[] = [
                    'title' => $total['title'],
                    'text' => $total['text'],
                    'class' => str_replace('_', '-', $total['class']),
                ];
            }

            $orderEntries[] = [
                'datePurchasedLong' => zen_date_long($customersOrder['date_purchased']),
                'ordersId' => (int) $customersOrder['orders_id'],
                'couponCode' => (string) ($customersOrder['coupon_code'] ?? ''),
                'detailsLink' => zen_href_link(
                    FILENAME_ORDERS,
                    $this->buildQueryStringExcluding(['oID', 'action']) . 'oID=' . $customersOrder['orders_id'] . '&action=edit',
                    'NONSSL'
                ),
                'totals' => $totals,
            ];
        }

        $page = new AdminPageData(
            DIR_FS_ADMIN . 'includes/templates/stats_customers_referrals_report.php',
            [
                'pageHeading' => HEADING_TITLE,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'referralCode' => $referralCode,
                'referralOptions' => $referralOptions,
                'orderEntries' => $orderEntries,
            ]
        );

        return $this->notifyBuildPageEnd($page, $context);
    }

    protected function normalizeDateRange(string $startDate, string $endDate): array
    {
        $startParts = explode('-', $startDate);
        $endParts = explode('-', $endDate);

        $startMonth = $startParts[0] ?? date('m');
        $startDay = $startParts[1] ?? date('d');
        $startYear = $startParts[2] ?? date('Y');

        $endMonth = $endParts[0] ?? date('m');
        $endDay = $endParts[1] ?? date('d');
        $endYear = $endParts[2] ?? date('Y');

        return [
            $startYear . '-' . $startMonth . '-' . $startDay . ' 00:00:00',
            $endYear . '-' . $endMonth . '-' . $endDay . ' 23:59:59',
        ];
    }

    protected function buildQueryStringExcluding(array $excludeKeys): string
    {
        $params = [];
        foreach ($this->request->query() as $key => $value) {
            if (in_array($key, $excludeKeys, true)) {
                continue;
            }
            $params[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
        }

        return $params === [] ? '' : implode('&', $params) . '&';
    }
}
