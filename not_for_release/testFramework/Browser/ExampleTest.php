<?php


class ExampleTest extends \Tests\Support\zcFeatureTestCase
{

    public $testContext = 'admin';
    /**
     * @test
     */
    public function an_example_test()
    {
        $response = $this->client->get(HTTP_SERVER . DIR_WS_ADMIN);
        $body = (string)$response->getBody();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Admin Login', $body);
        $post = ['admin_name' => 'Admin', 'admin_pass' => 'password'];
        var_dump($_SESSION);
       // $response = $this->client->post(HTTP_SERVER . DIR_WS_ADMIN . '/login', ['form_params' => $post]);
    }
}
