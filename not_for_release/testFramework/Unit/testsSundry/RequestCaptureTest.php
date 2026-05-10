<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsSundry;

use Tests\Support\zcUnitTestCase;
use Zencart\Request\Request;

class RequestCaptureTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $_GET = [
            'page' => '7',
            'flag' => 'true',
            'order_id' => '99',
        ];
        $_POST = [
            'order_id' => '12<script>',
            'query_email_address' => 'customer@example.com',
            'notify' => '0',
            'items' => ['a', 'b'],
        ];
        $_COOKIE = [
            'zenid' => 'cookie-token',
        ];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_REQUEST = array_merge($_GET, $_POST);
    }

    public function testCaptureSeparatesRequestSources(): void
    {
        $request = Request::capture();

        $this->assertSame('12<script>', $request->input('order_id'));
        $this->assertSame('99', $request->query('order_id'));
        $this->assertSame('12<script>', $request->post('order_id'));
        $this->assertSame('cookie-token', $request->cookie('zenid'));
        $this->assertSame('POST', $request->server('REQUEST_METHOD'));
    }

    public function testTypedAccessorsCoerceExpectedValues(): void
    {
        $request = Request::capture();

        $this->assertSame(12, $request->postInteger('order_id'));
        $this->assertSame(99, $request->queryInteger('order_id'));
        $this->assertSame(12, $request->integer('order_id'));
        $this->assertTrue($request->boolean('flag'));
        $this->assertFalse($request->boolean('notify', true));
        $this->assertSame('customer@example.com', $request->postString('query_email_address'));
        $this->assertSame(['a', 'b'], $request->inputArray('items'));
    }

    public function testTypedAccessorsReturnDefaultsForUnsupportedShapes(): void
    {
        $_POST['order_id'] = ['12'];
        $_REQUEST = array_merge($_GET, $_POST);

        $request = Request::capture();

        $this->assertSame(0, $request->postInteger('order_id'));
        $this->assertSame('fallback', $request->string('order_id', 'fallback'));
        $this->assertSame([], $request->inputArray('missing'));
        $this->assertTrue($request->boolean('missing', true));
    }
}
