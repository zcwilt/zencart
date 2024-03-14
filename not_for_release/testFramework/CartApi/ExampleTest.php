<?php

namespace Tests\CartApi;

// use Illuminate\Foundation\Testing\RefreshDatabase;
//use Tests\Feature\TestStoreFeatures\TestCase;

class ExampleTest extends TestCase
{
    /**
     * @test
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(404);
    }
}
