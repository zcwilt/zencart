<?php
/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Features\TestAdminFeatures;

use Symfony\Component\Panther\Client;
use Tests\Support\zcFeatureTestCaseAdmin;

class AdminTest extends zcFeatureTestCaseAdmin
{

    protected array $quickTestMap = [
        'category_product_listing' => 'Admin Category Product Listing',
        'product_types' => 'Admin Product Types',
        'options_name_manager' => 'Admin Options Name Manager',
        'manufacturers' => 'Admin Manufacturers',
    ];

    public function testSimpleAdmin()
    {
        $this->browser->request('GET', HTTP_SERVER . '/admin');
        $response = $this->browser->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->browser->request('GET', HTTP_SERVER . '/admin');
        $response = $this->browser->getResponse();
        $this->assertStringContainsString('Admin Login', (string)$response->getContent() );
        $this->browser->submitForm('Submit', [
            'admin_name' => 'Admin',
            'admin_pass' => 'password',
        ]);
        $response = $this->browser->getResponse();
        $this->assertStringContainsString('Initial Setup Wizard', (string)$response->getContent() );
    }

    public function testInitialLogin()
    {
        $this->browser->request('GET', HTTP_SERVER . '/admin');
        $response = $this->browser->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->browser->request('GET', HTTP_SERVER . '/admin');
        $response = $this->browser->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->browser->submitForm('Submit', [
            'admin_name' => 'Admin',
            'admin_pass' => 'password',
        ]);
        $this->browser->submitForm('Update', [
            'store_name' => 'Zencart Store',
        ]);
        $response = $this->browser->getResponse();
        $this->assertStringContainsString('Initial Setup Wizard', (string)$response->getContent() );
        $this->browser->submitForm('Update', [
            'store_name' => 'Zencart Store',
            'store_owner' => 'Store Owner',
        ]);
        $response = $this->browser->getResponse();
        $this->assertStringContainsString('Admin Home', (string)$response->getContent() );
        $this->quickLinksTest();
    }

    public function QuickLinksTest()
    {
        foreach ($this->quickTestMap as $page => $contentTest) {
            $pageURI = $this->buildAdminLink($page);
            $this->browser->request('GET', $pageURI);
            $response = $this->browser->getResponse();
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertStringContainsString($contentTest, (string)$response->getContent() );
        }
    }

}
