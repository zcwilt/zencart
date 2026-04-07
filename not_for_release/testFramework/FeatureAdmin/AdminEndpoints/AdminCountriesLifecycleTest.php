<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\FeatureAdmin\AdminEndpoints;

use Tests\Support\Database\TestDb;
use Tests\Support\zcInProcessFeatureTestCaseAdmin;

/**
 * @group parallel-candidate
 */
class AdminCountriesLifecycleTest extends zcInProcessFeatureTestCaseAdmin
{
    protected $runTestInSeparateProcess = true;
    protected $preserveGlobalState = false;

    public function testAdminCanCreateEditAndDeleteCountry(): void
    {
        $this->completeInitialAdminSetup();

        $newPage = $this->getAdmin('/admin/index.php?cmd=countries&page=L&action=new')
            ->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee('Countries')
            ->assertSee('New Country');

        $createResponse = $this->submitAdminForm($newPage, 'countries', [
            'countries_name' => 'Lifecycleland',
            'countries_iso_code_2' => 'LL',
            'countries_iso_code_3' => 'LCL',
            'address_format_id' => '1',
            'status' => 'on',
        ]);

        $createPage = $createResponse->isRedirect() ? $this->followAdminRedirect($createResponse) : $createResponse;

        $createPage->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee('Lifecycleland')
            ->assertSee('LCL');

        $countryId = (int) TestDb::selectValue(
            'SELECT countries_id FROM countries WHERE countries_iso_code_3 = :code ORDER BY countries_id DESC LIMIT 1',
            [':code' => 'LCL']
        );

        $this->assertGreaterThan(0, $countryId);

        $createdCountry = TestDb::selectOne(
            'SELECT countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, status
               FROM countries
              WHERE countries_id = :country_id
              LIMIT 1',
            [':country_id' => $countryId]
        );

        $this->assertNotNull($createdCountry);
        $this->assertSame('Lifecycleland', $createdCountry['countries_name']);
        $this->assertSame('LL', $createdCountry['countries_iso_code_2']);
        $this->assertSame('LCL', $createdCountry['countries_iso_code_3']);
        $this->assertSame('1', (string) $createdCountry['address_format_id']);
        $this->assertSame('1', (string) $createdCountry['status']);

        $editPage = $this->getAdmin('/admin/index.php?cmd=countries&page=L&cID=' . $countryId . '&action=edit')
            ->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee('Edit Country')
            ->assertSee('Lifecycleland');

        $editResponse = $this->submitAdminForm($editPage, 'countries', [
            'countries_name' => 'Lifecycle Republic',
            'countries_iso_code_2' => 'LR',
            'countries_iso_code_3' => 'LCR',
            'address_format_id' => '2',
        ]);

        $editPage = $editResponse->isRedirect() ? $this->followAdminRedirect($editResponse) : $editResponse;

        $editPage->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee('Lifecycle Republic')
            ->assertSee('LCR');

        $updatedCountry = TestDb::selectOne(
            'SELECT countries_name, countries_iso_code_2, countries_iso_code_3, address_format_id, status
               FROM countries
              WHERE countries_id = :country_id
              LIMIT 1',
            [':country_id' => $countryId]
        );

        $this->assertNotNull($updatedCountry);
        $this->assertSame('Lifecycle Republic', $updatedCountry['countries_name']);
        $this->assertSame('LR', $updatedCountry['countries_iso_code_2']);
        $this->assertSame('LCR', $updatedCountry['countries_iso_code_3']);
        $this->assertSame('2', (string) $updatedCountry['address_format_id']);
        $this->assertSame('0', (string) $updatedCountry['status']);

        $deletePage = $this->getAdmin('/admin/index.php?cmd=countries&page=L&cID=' . $countryId . '&action=delete')
            ->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee('Delete Country')
            ->assertSee('Lifecycle Republic');

        $deleteResponse = $this->submitAdminForm($deletePage, 'countries', [
            'cID' => (string) $countryId,
        ]);

        $deletePage = $deleteResponse->isRedirect() ? $this->followAdminRedirect($deleteResponse) : $deleteResponse;

        $deletePage->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee('Countries');

        $deletedCountry = TestDb::selectValue(
            'SELECT countries_id FROM countries WHERE countries_id = :country_id LIMIT 1',
            [':country_id' => $countryId]
        );

        $this->assertNull($deletedCountry);
    }

    public function testAdminCanSelectAnotherVisibleCountryRowAndToggleItsStatus(): void
    {
        $this->completeInitialAdminSetup();

        $letterGroup = TestDb::selectOne(
            "SELECT UPPER(SUBSTRING(countries_name, 1, 1)) AS letter, COUNT(*) AS total
               FROM countries
              GROUP BY UPPER(SUBSTRING(countries_name, 1, 1))
             HAVING COUNT(*) >= 2
              ORDER BY letter ASC
              LIMIT 1"
        );
        $this->assertNotNull($letterGroup);

        $statement = TestDb::pdo()->prepare(
            'SELECT countries_id, countries_name, status
               FROM countries
              WHERE UPPER(SUBSTRING(countries_name, 1, 1)) = :letter
              ORDER BY countries_name ASC, countries_id ASC
              LIMIT 2'
        );
        $statement->bindValue(':letter', $letterGroup['letter']);
        $statement->execute();
        $countriesOnLetterPage = $statement->fetchAll();

        $this->assertCount(2, $countriesOnLetterPage);
        $selectedCountry = $countriesOnLetterPage[0];
        $toggleCountry = $countriesOnLetterPage[1];

        $selectedPage = $this->getAdmin('/admin/index.php?cmd=countries&page=' . urlencode((string) $letterGroup['letter']) . '&cID=' . $selectedCountry['countries_id'])
            ->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee((string) $selectedCountry['countries_name'])
            ->assertSee((string) $toggleCountry['countries_name'])
            ->assertSee('cID=' . $selectedCountry['countries_id'] . '&amp;action=edit');

        $this->assertStringContainsString('name="setstatus_' . $selectedCountry['countries_id'] . '"', $selectedPage->content);
        $this->assertStringContainsString('name="setstatus_' . $toggleCountry['countries_id'] . '"', $selectedPage->content);

        $otherRowPage = $this->getAdmin('/admin/index.php?cmd=countries&page=' . urlencode((string) $letterGroup['letter']) . '&cID=' . $toggleCountry['countries_id'])
            ->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee('Countries')
            ->assertSee((string) $toggleCountry['countries_name'])
            ->assertSee('cID=' . $toggleCountry['countries_id'] . '&amp;action=edit');

        $originalStatus = (string) $toggleCountry['status'];

        $this->assertContains($originalStatus, ['0', '1']);

        $toggleResponse = $this->submitAdminForm($selectedPage, 'setstatus_' . $toggleCountry['countries_id'], [
            'current_country' => (string) $toggleCountry['countries_id'],
            'current_status' => $originalStatus,
        ]);

        $toggleResponse->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee((string) $toggleCountry['countries_name'])
            ->assertSee('cID=' . $toggleCountry['countries_id'] . '&amp;action=edit');

        $toggledStatus = (string) TestDb::selectValue(
            'SELECT status FROM countries WHERE countries_id = :country_id LIMIT 1',
            [':country_id' => $toggleCountry['countries_id']]
        );

        $this->assertSame($originalStatus === '1' ? '0' : '1', $toggledStatus);
    }

    public function testAdminCanBrowseCountriesByAlphabeticPage(): void
    {
        $this->completeInitialAdminSetup();

        $bPage = $this->getAdmin('/admin/index.php?cmd=countries&page=B')
            ->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee('Countries')
            ->assertSee('Bahrain');

        $this->assertStringContainsString('page=B', $bPage->content);
        $this->assertStringContainsString('action=new', $bPage->content);
    }

    public function testAdminCanFilterCountriesByStatus(): void
    {
        $this->completeInitialAdminSetup();

        $activeCountry = TestDb::selectOne(
            'SELECT countries_id, countries_name
               FROM countries
              WHERE status = 1
              ORDER BY countries_name ASC
              LIMIT 1'
        );
        $inactiveCountryId = TestDb::insert('countries', [
            'countries_name' => 'Filter Inactive Republic',
            'countries_iso_code_2' => 'FI',
            'countries_iso_code_3' => 'FIR',
            'address_format_id' => 1,
            'status' => 0,
        ]);
        $inactiveCountry = TestDb::selectOne(
            'SELECT countries_id, countries_name
               FROM countries
              WHERE countries_id = :country_id
              LIMIT 1',
            [':country_id' => $inactiveCountryId]
        );

        $this->assertNotNull($activeCountry);
        $this->assertNotNull($inactiveCountry);

        $filteredPage = $this->getAdmin('/admin/index.php?cmd=countries&status_filter=1')
            ->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee('Countries')
            ->assertSee((string) $activeCountry['countries_name']);

        $this->assertStringNotContainsString((string) $inactiveCountry['countries_name'], $filteredPage->content);
        $this->assertStringContainsString('name="status_filter"', $filteredPage->content);
        $this->assertStringContainsString('Active Countries', $filteredPage->content);
        $this->assertStringContainsString('<option value="1" selected>', $filteredPage->content);
    }

    protected function completeInitialAdminSetup(): void
    {
        $this->visitAdminHome()
            ->assertOk()
            ->assertSee('Admin Login');

        $this->submitAdminLogin([
            'admin_name' => 'Admin',
            'admin_pass' => 'password',
        ])->assertOk()
            ->assertSee('Initial Setup Wizard');

        $this->submitAdminSetupWizard([
            'store_name' => 'Zencart Store',
        ])->assertOk()
            ->assertSee('Initial Setup Wizard');

        $this->submitAdminSetupWizard([
            'store_name' => 'Zencart Store',
            'store_owner' => 'Store Owner',
        ])->assertOk()
            ->assertSee('Admin Home');
    }
}
