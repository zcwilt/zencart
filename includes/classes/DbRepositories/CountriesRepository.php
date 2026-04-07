<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\DbRepositories;

use queryFactory;

class CountriesRepository extends AbstractQueryFactoryRepository
{
    public function createCountry(
        string $countriesName,
        string $countriesIsoCode2,
        string $countriesIsoCode3,
        int $status,
        int $addressFormatId
    ): void {
        $this->db->Execute("INSERT INTO " . TABLE_COUNTRIES . " (countries_name, countries_iso_code_2, countries_iso_code_3, status, address_format_id)
                      VALUES ('" . zen_db_input($countriesName) . "',
                              '" . zen_db_input($countriesIsoCode2) . "',
                              '" . zen_db_input($countriesIsoCode3) . "',
                              " . $status . ",
                              " . $addressFormatId . ")");
    }

    public function updateCountry(
        int $countriesId,
        string $countriesName,
        string $countriesIsoCode2,
        string $countriesIsoCode3,
        int $status,
        int $addressFormatId
    ): void {
        $this->db->Execute("UPDATE " . TABLE_COUNTRIES . "
                        SET countries_name = '" . zen_db_input($countriesName) . "',
                            countries_iso_code_2 = '" . zen_db_input($countriesIsoCode2) . "',
                            countries_iso_code_3 = '" . zen_db_input($countriesIsoCode3) . "',
                            address_format_id = " . $addressFormatId . ",
                            status = " . $status . "
                      WHERE countries_id = " . $countriesId);
    }

    public function isCountryInUse(int $countriesId): bool
    {
        $result = $this->fetchFirstRow(
            "SELECT entry_country_id
               FROM " . TABLE_ADDRESS_BOOK . "
              WHERE entry_country_id = " . $countriesId . "
              LIMIT 1"
        );

        return $result !== null;
    }

    public function deleteCountry(int $countriesId): void
    {
        $this->db->Execute("DELETE FROM " . TABLE_COUNTRIES . " WHERE countries_id = " . $countriesId);
    }

    public function updateStatus(int $countriesId, int $status): void
    {
        $this->db->Execute("UPDATE " . TABLE_COUNTRIES . " SET status = " . $status . " WHERE countries_id = " . $countriesId);
    }
}
