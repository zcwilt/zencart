<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\DbRepositories;

use queryFactory;
use splitPageResults;

class BannerManagerRepository extends AbstractQueryFactoryRepository
{
    public function updateOpenNewWindow(int $bannerId, int $flag): void
    {
        $this->db->Execute("UPDATE " . TABLE_BANNERS . "
            SET banners_open_new_windows = " . $flag . "
            WHERE banners_id = " . $bannerId);
    }

    public function bannerImageById(int $bannerId): ?string
    {
        $banner = $this->fetchFirstRow("SELECT banners_image
            FROM " . TABLE_BANNERS . "
            WHERE banners_id = " . $bannerId);

        return $banner === null ? null : (string) $banner['banners_image'];
    }

    public function deleteBannerAndHistory(int $bannerId): void
    {
        $this->db->Execute("DELETE FROM " . TABLE_BANNERS . " WHERE banners_id = " . $bannerId);
        $this->db->Execute("DELETE FROM " . TABLE_BANNERS_HISTORY . " WHERE banners_id = " . $bannerId);
    }

    public function insertBanner(array $sqlDataArray): int
    {
        zen_db_perform(TABLE_BANNERS, $sqlDataArray);
        return (int) zen_db_insert_id();
    }

    public function updateBanner(int $bannerId, array $sqlDataArray): void
    {
        zen_db_perform(TABLE_BANNERS, $sqlDataArray, 'update', "banners_id = " . $bannerId);
    }

    public function updateBannerSchedule(int $bannerId, string $dateScheduled, string $expiresDate, int $expiresImpressions): void
    {
        $sql = "UPDATE " . TABLE_BANNERS . "
            SET date_scheduled = DATE_ADD(:scheduledDate, INTERVAL '00:00:00' HOUR_SECOND),
                expires_date = DATE_ADD(:expiresDate, INTERVAL '23:59:59' HOUR_SECOND),
                expires_impressions = " . ($expiresImpressions === 0 ? "null" : ":expiresImpressions") . "
            WHERE banners_id = :bannersID";
        $sql = $this->db->bindVars($sql, ':expiresImpressions', $expiresImpressions, 'integer');
        $sql = $this->db->bindVars($sql, ':scheduledDate', $dateScheduled, 'date');
        $sql = $this->db->bindVars($sql, ':expiresDate', $expiresDate, 'date');
        $sql = $this->db->bindVars($sql, ':bannersID', $bannerId, 'integer');
        $this->db->Execute($sql);
    }

    public function bannerFormDataById(int $bannerId): ?array
    {
        return $this->fetchFirstRow("SELECT banners_title, banners_url, banners_image, banners_group,
            banners_html_text, status,
            date_format(date_scheduled, '" . zen_datepicker_format_forsql() . "') AS date_scheduled,
            date_format(expires_date, '" . zen_datepicker_format_forsql() . "') AS expires_date,
            expires_impressions, date_status_change, banners_open_new_windows, banners_sort_order
            FROM " . TABLE_BANNERS . "
            WHERE banners_id = " . $bannerId);
    }

    public function bannerGroups(): array
    {
        $groups = [];
        $result = $this->db->Execute("SELECT DISTINCT banners_group
            FROM " . TABLE_BANNERS . "
            ORDER BY banners_group");

        foreach ($result as $group) {
            $groups[] = [
                'id' => $group['banners_group'],
                'text' => $group['banners_group'],
            ];
        }

        return $groups;
    }

    public function bannerListingQuery(): string
    {
        return 'SELECT banners_id, banners_title, banners_image, banners_group, status,
            expires_date, expires_impressions, date_status_change, date_scheduled,
            date_added, banners_open_new_windows, banners_sort_order
            FROM ' . TABLE_BANNERS . '
            ORDER BY banners_group, banners_title';
    }

    public function fetchRows(string $query): array
    {
        return $this->fetchAllRows($query);
    }

    public function paginateQuery(int $currentPage, string $query, int &$queryNumRows): splitPageResults
    {
        return new splitPageResults($currentPage, MAX_DISPLAY_SEARCH_RESULTS, $query, $queryNumRows);
    }

    public function bannerHistoryTotals(int $bannerId): array
    {
        $info = $this->db->Execute("SELECT SUM(banners_shown) AS banners_shown,
            SUM(banners_clicked) AS banners_clicked
            FROM " . TABLE_BANNERS_HISTORY . "
            WHERE banners_id = " . $bannerId);

        return $info->fields;
    }

    public function bannerPositions(string $bannerGroup): array
    {
        $positions = [];
        $bannerPositions = $this->db->Execute(
            'SELECT configuration_title
                FROM ' . TABLE_CONFIGURATION . '
                WHERE configuration_key LIKE "SHOW_BANNERS_GROUP_SET%"
                AND INSTR(configuration_value, "' . $bannerGroup . '")'
        );

        foreach ($bannerPositions as $bannerPosition) {
            $positionTexts = preg_split('/\s?-\s?/', $bannerPosition['configuration_title']);
            $positions[] = $positionTexts[1] ?? '';
        }

        return $positions;
    }
}
