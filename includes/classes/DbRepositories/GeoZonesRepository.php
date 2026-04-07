<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\DbRepositories;

use queryFactory;
use splitPageResults;

class GeoZonesRepository extends AbstractQueryFactoryRepository
{
    public function insertSubZone(int $zoneCountryId, int $zoneId, int $geoZoneId): int
    {
        $this->db->Execute("INSERT INTO " . TABLE_ZONES_TO_GEO_ZONES . "(zone_country_id, zone_id, geo_zone_id, date_added)
            VALUES ('" . $zoneCountryId . "',
                    '" . $zoneId . "',
                    '" . $geoZoneId . "',
                    now())");

        return (int) $this->db->insert_ID();
    }

    public function updateSubZone(int $associationId, int $geoZoneId, int $zoneCountryId, int|string $zoneId): void
    {
        $this->db->Execute("UPDATE " . TABLE_ZONES_TO_GEO_ZONES . "
            SET geo_zone_id = " . $geoZoneId . ",
                zone_country_id = " . $zoneCountryId . ",
                zone_id = " . (!empty($zoneId) ? (int) $zoneId : 'null') . ",
                last_modified = now()
            WHERE association_id = " . $associationId);
    }

    public function deleteSubZone(int $associationId): void
    {
        $this->db->Execute("DELETE FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE association_id = " . $associationId);
    }

    public function insertGeoZone(string $name, string $description): int
    {
        $this->db->Execute("INSERT INTO " . TABLE_GEO_ZONES . " (geo_zone_name, geo_zone_description, date_added)
            VALUES ('" . zen_db_input($name) . "',
                    '" . zen_db_input($description) . "',
                    now())");

        return (int) $this->db->insert_ID();
    }

    public function updateGeoZone(int $geoZoneId, string $name, string $description): void
    {
        $this->db->Execute("UPDATE " . TABLE_GEO_ZONES . "
            SET geo_zone_name = '" . zen_db_input($name) . "',
                geo_zone_description = '" . zen_db_input($description) . "',
                last_modified = now()
            WHERE geo_zone_id = " . $geoZoneId);
    }

    public function geoZoneHasTaxRates(int $geoZoneId): bool
    {
        $checkTaxRates = $this->db->Execute("SELECT tax_zone_id
            FROM " . TABLE_TAX_RATES . "
            WHERE tax_zone_id = " . $geoZoneId);

        return $checkTaxRates->RecordCount() > 0;
    }

    public function deleteGeoZone(int $geoZoneId): void
    {
        $this->db->Execute("DELETE FROM " . TABLE_GEO_ZONES . " WHERE geo_zone_id = " . $geoZoneId);
        $this->db->Execute("DELETE FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = " . $geoZoneId);
    }

    public function subZoneListingQuery(int $geoZoneId): string
    {
        return "SELECT a.association_id, a.zone_country_id, c.countries_name, a.zone_id, a.geo_zone_id, a.last_modified, a.date_added, z.zone_name
            FROM (" . TABLE_ZONES_TO_GEO_ZONES . " a
              LEFT JOIN " . TABLE_COUNTRIES . " c ON a.zone_country_id = c.countries_id
              LEFT JOIN " . TABLE_ZONES . " z ON a.zone_id = z.zone_id)
            WHERE a.geo_zone_id = " . $geoZoneId . "
            ORDER BY c.countries_name, association_id";
    }

    public function geoZoneListingQuery(): string
    {
        return "SELECT geo_zone_id, geo_zone_name, geo_zone_description, last_modified, date_added
            FROM " . TABLE_GEO_ZONES . "
            ORDER BY geo_zone_name";
    }

    public function fetchRows(string $query): array
    {
        return $this->fetchAllRows($query);
    }

    public function paginateQuery(int $currentPage, string $query, int &$queryNumRows): splitPageResults
    {
        return new splitPageResults($currentPage, MAX_DISPLAY_SEARCH_RESULTS, $query, $queryNumRows);
    }

    public function geoZoneCounts(int $geoZoneId): array
    {
        $numZonesResult = $this->db->Execute("SELECT COUNT(*) AS num_zones
            FROM " . TABLE_ZONES_TO_GEO_ZONES . "
            WHERE geo_zone_id = " . $geoZoneId . "
            GROUP BY geo_zone_id");
        $numTaxRatesResult = $this->db->Execute("SELECT COUNT(*) AS num_tax_rates
            FROM " . TABLE_TAX_RATES . "
            WHERE tax_zone_id = " . $geoZoneId . "
            GROUP BY tax_zone_id");

        return [
            'num_zones' => (!$numZonesResult->EOF && isset($numZonesResult->fields['num_zones'])) ? (int) $numZonesResult->fields['num_zones'] : 0,
            'num_tax_rates' => (!$numTaxRatesResult->EOF && isset($numTaxRatesResult->fields['num_tax_rates'])) ? (int) $numTaxRatesResult->fields['num_tax_rates'] : 0,
        ];
    }
}
