<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsSundry;

use Tests\Support\zcUnitTestCase;
use Zencart\Request\Request;

class RequestInfrastructureTest extends zcUnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        require_once DIR_FS_CATALOG . 'includes/classes/traits/Singleton.php';
        require_once DIR_FS_CATALOG . 'includes/classes/Request.php';
    }

    public function testCapturePreservesLegacyMergedInputBehavior(): void
    {
        $_GET = ['page' => '2', 'cmd' => 'countries'];
        $_POST = ['page' => '3', 'search' => 'Belg'];
        $_COOKIE = ['theme' => 'classic'];
        $_SERVER = ['REQUEST_METHOD' => 'POST'];
        $_REQUEST = ['page' => '3', 'cmd' => 'countries', 'search' => 'Belg', 'theme' => 'classic'];

        $request = Request::capture();

        $this->assertSame('3', $request->input('page'));
        $this->assertTrue($request->has('search'));
        $this->assertSame('2', $request->query('page'));
        $this->assertSame('3', $request->post('page'));
        $this->assertSame('classic', $request->cookie('theme'));
        $this->assertSame('POST', $request->server('REQUEST_METHOD'));
    }

    public function testFromArraysExposesTypedAndBagHelpers(): void
    {
        $request = Request::fromArrays(
            ['page' => '7', 'enabled' => '0', 'empty_value' => '   '],
            ['enabled' => 'on', 'ids' => ['1', '2'], 'count' => '12'],
            ['theme' => 'admin'],
            ['HTTP_HOST' => 'example.test']
        );

        $this->assertSame(['theme' => 'admin', 'page' => '7', 'enabled' => 'on', 'empty_value' => '   ', 'ids' => ['1', '2'], 'count' => '12'], $request->all());
        $this->assertSame(['page' => '7', 'count' => '12'], $request->only(['page', 'count', 'missing']));
        $this->assertSame(['theme' => 'admin', 'enabled' => 'on', 'empty_value' => '   ', 'ids' => ['1', '2'], 'count' => '12'], $request->except(['page']));
        $this->assertTrue($request->exists('empty_value'));
        $this->assertFalse($request->has('missing'));
        $this->assertSame(12, $request->integer('count'));
        $this->assertSame('7', $request->string('page'));
        $this->assertTrue($request->boolean('enabled'));
        $this->assertSame(['1', '2'], $request->inputArray('ids'));
        $this->assertFalse($request->filled('empty_value'));
        $this->assertSame(['page' => '7', 'enabled' => '0', 'empty_value' => '   '], $request->query());
        $this->assertSame(['enabled' => 'on', 'ids' => ['1', '2'], 'count' => '12'], $request->post());
        $this->assertSame(['theme' => 'admin'], $request->cookie());
        $this->assertSame(['HTTP_HOST' => 'example.test'], $request->server());
    }

    public function testExistsTreatsNullAsPresentWhileHasRemainsBackwardCompatible(): void
    {
        $request = Request::fromArrays([], [], [], [], ['nullable' => null]);

        $this->assertTrue($request->exists('nullable'));
        $this->assertFalse($request->has('nullable'));
        $this->assertNull($request->input('nullable'));
    }
}
