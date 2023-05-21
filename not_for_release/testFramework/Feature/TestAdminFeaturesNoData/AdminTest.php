<?php
/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Features\TestAdminFeatures;

use Tests\Support\zcFeatureTestCaseAdmin;

class AdminTest extends zcFeatureTestCaseAdmin
{
    public function testSimpleAdmin()
    {
        $this->browser->request('GET', HTTP_SERVER . '/admin');
        $response = $this->browser->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->browser->request('GET', HTTP_SERVER . '/admin');
        $response = $this->browser->getResponse();
        $this->assertStringContainsString('Admin Login', (string)$response->getContent() );
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
        $response = $this->browser->getResponse();
        $this->assertStringContainsString('Password Expired', (string)$response->getContent() );
    }
}
