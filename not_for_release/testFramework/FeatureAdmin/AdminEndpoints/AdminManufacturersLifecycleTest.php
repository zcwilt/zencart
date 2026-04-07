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
class AdminManufacturersLifecycleTest extends zcInProcessFeatureTestCaseAdmin
{
    protected $runTestInSeparateProcess = true;
    protected $preserveGlobalState = false;

    public function testAdminCanCreateEditAndDeleteManufacturer(): void
    {
        $this->completeInitialAdminSetup();

        $languageId = (int) TestDb::selectValue(
            'SELECT languages_id FROM languages ORDER BY sort_order ASC, languages_id ASC LIMIT 1'
        );
        $this->assertGreaterThan(0, $languageId);

        $newPage = $this->getAdmin('/admin/index.php?cmd=manufacturers&page=1&action=new')
            ->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee('Manufacturers')
            ->assertSee('New Manufacturer');

        $createResponse = $this->submitAdminForm($newPage, 'manufacturers', [
            'manufacturers_name' => 'Lifecycle Manufacturer',
            'featured' => '1',
            'img_dir' => 'manufacturers/',
            'manufacturers_image_manual' => 'none',
            'manufacturers_url' => [
                $languageId => 'https://example.com/lifecycle-manufacturer',
            ],
        ]);

        $createPage = $createResponse->isRedirect() ? $this->followAdminRedirect($createResponse) : $createResponse;

        $createPage->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee('Manufacturers')
            ->assertSee('Lifecycle Manufacturer');

        $searchPage = $this->getAdmin('/admin/index.php?cmd=manufacturers&search=Lifecycle')
            ->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee('Manufacturers')
            ->assertSee('name="search" value="Lifecycle"', false)
            ->assertSee('Lifecycle Manufacturer');

        $manufacturerId = (int) TestDb::selectValue(
            'SELECT manufacturers_id FROM manufacturers WHERE manufacturers_name = :name ORDER BY manufacturers_id DESC LIMIT 1',
            [':name' => 'Lifecycle Manufacturer']
        );

        $this->assertGreaterThan(0, $manufacturerId);

        $createdManufacturer = TestDb::selectOne(
            'SELECT manufacturers_name, featured, manufacturers_image
               FROM manufacturers
              WHERE manufacturers_id = :manufacturer_id
              LIMIT 1',
            [':manufacturer_id' => $manufacturerId]
        );

        $this->assertNotNull($createdManufacturer);
        $this->assertSame('Lifecycle Manufacturer', $createdManufacturer['manufacturers_name']);
        $this->assertSame('1', (string) $createdManufacturer['featured']);
        $this->assertSame('', (string) $createdManufacturer['manufacturers_image']);

        $createdManufacturerInfo = TestDb::selectOne(
            'SELECT manufacturers_url
               FROM manufacturers_info
              WHERE manufacturers_id = :manufacturer_id
                AND languages_id = :language_id
              LIMIT 1',
            [':manufacturer_id' => $manufacturerId, ':language_id' => $languageId]
        );

        $this->assertNotNull($createdManufacturerInfo);
        $this->assertSame('https://example.com/lifecycle-manufacturer', $createdManufacturerInfo['manufacturers_url']);

        $editPage = $this->getAdmin('/admin/index.php?cmd=manufacturers&page=1&mID=' . $manufacturerId . '&action=edit')
            ->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee('Edit Manufacturer')
            ->assertSee('Lifecycle Manufacturer');

        $editResponse = $this->submitAdminForm($editPage, 'manufacturers', [
            'manufacturers_name' => 'Lifecycle Manufacturer Updated',
            'featured' => '',
            'img_dir' => 'manufacturers/',
            'manufacturers_image_manual' => 'none',
            'manufacturers_url' => [
                $languageId => 'https://example.com/lifecycle-manufacturer-updated',
            ],
        ]);

        $editPage = $editResponse->isRedirect() ? $this->followAdminRedirect($editResponse) : $editResponse;

        $editPage->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee('Manufacturers')
            ->assertSee('Lifecycle Manufacturer Updated');

        $updatedManufacturer = TestDb::selectOne(
            'SELECT manufacturers_name, featured, manufacturers_image
               FROM manufacturers
              WHERE manufacturers_id = :manufacturer_id
              LIMIT 1',
            [':manufacturer_id' => $manufacturerId]
        );

        $this->assertNotNull($updatedManufacturer);
        $this->assertSame('Lifecycle Manufacturer Updated', $updatedManufacturer['manufacturers_name']);
        $this->assertSame('0', (string) $updatedManufacturer['featured']);
        $this->assertSame('', (string) $updatedManufacturer['manufacturers_image']);

        $updatedManufacturerInfo = TestDb::selectOne(
            'SELECT manufacturers_url
               FROM manufacturers_info
              WHERE manufacturers_id = :manufacturer_id
                AND languages_id = :language_id
              LIMIT 1',
            [':manufacturer_id' => $manufacturerId, ':language_id' => $languageId]
        );

        $this->assertNotNull($updatedManufacturerInfo);
        $this->assertSame('https://example.com/lifecycle-manufacturer-updated', $updatedManufacturerInfo['manufacturers_url']);

        $deletePage = $this->getAdmin('/admin/index.php?cmd=manufacturers&page=1&mID=' . $manufacturerId . '&action=delete')
            ->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee('Delete Manufacturer')
            ->assertSee('Lifecycle Manufacturer Updated');

        $deleteResponse = $this->submitAdminForm($deletePage, 'manufacturers', [
            'mID' => (string) $manufacturerId,
        ]);

        $deletePage = $deleteResponse->isRedirect() ? $this->followAdminRedirect($deleteResponse) : $deleteResponse;

        $deletePage->assertOk()
            ->assertHeader('X-ZC-InProcess-Runner', 'admin')
            ->assertSee('Manufacturers');

        $deletedManufacturer = TestDb::selectValue(
            'SELECT manufacturers_id FROM manufacturers WHERE manufacturers_id = :manufacturer_id LIMIT 1',
            [':manufacturer_id' => $manufacturerId]
        );
        $deletedManufacturerInfo = TestDb::selectValue(
            'SELECT manufacturers_id FROM manufacturers_info WHERE manufacturers_id = :manufacturer_id LIMIT 1',
            [':manufacturer_id' => $manufacturerId]
        );

        $this->assertNull($deletedManufacturer);
        $this->assertNull($deletedManufacturerInfo);
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
