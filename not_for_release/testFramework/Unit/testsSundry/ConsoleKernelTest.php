<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Tests\Unit\testsSundry;

use PHPUnit\Framework\TestCase;
use Tests\Support\UnitTestBootstrap;
use Zencart\Console\ConsoleInput;
use Zencart\Console\ConsoleKernel;
use Zencart\Console\ConsoleOutput;
use Zencart\Console\PluginCommandDiscovery;

class ConsoleKernelTest extends TestCase
{
    protected $preserveGlobalState = false;

    public static function setUpBeforeClass(): void
    {
        UnitTestBootstrap::initialize();
        require_once DIR_FS_CATALOG . 'includes/classes/vendors/AuraAutoload/src/Loader.php';
        $psr4Autoloader = new \Aura\Autoload\Loader();
        $psr4Autoloader->register();
        require DIR_FS_CATALOG . 'includes/psr4Autoload.php';
    }

    public function testListCommandShowsCoreCommands(): void
    {
        [$stdout, $stderr, $output] = $this->makeOutput();

        $kernel = new ConsoleKernel();
        $exitCode = $kernel->run(new ConsoleInput(['zc_cli.php', 'list']), $output);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Available commands:', stream_get_contents($stdout, -1, 0));
        $this->assertSame('', stream_get_contents($stderr, -1, 0));
    }

    public function testHelpFlagRoutesThroughHelpCommand(): void
    {
        [$stdout, , $output] = $this->makeOutput();

        $kernel = new ConsoleKernel();
        $exitCode = $kernel->run(new ConsoleInput(['zc_cli.php', 'list', '--help']), $output);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('List available console commands.', stream_get_contents($stdout, -1, 0));
    }

    public function testGlobalHelpFallsBackToCommandListing(): void
    {
        [$stdout, , $output] = $this->makeOutput();

        $kernel = new ConsoleKernel();
        $exitCode = $kernel->run(new ConsoleInput(['zc_cli.php', '--help']), $output);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Available commands:', stream_get_contents($stdout, -1, 0));
    }

    public function testPluginCommandRunsThroughKernel(): void
    {
        [$stdout, , $output] = $this->makeOutput();
        require_once DIR_FS_CATALOG . 'includes/classes/vendors/AuraAutoload/src/Loader.php';
        $psr4Autoloader = new \Aura\Autoload\Loader();
        $psr4Autoloader->register();
        require DIR_FS_CATALOG . 'includes/psr4Autoload.php';

        $discovery = new PluginCommandDiscovery(
            DIR_FS_CATALOG . 'not_for_release/testFramework/Support/plugins',
            $psr4Autoloader
        );

        $kernel = new ConsoleKernel(null, $discovery);
        $exitCode = $kernel->run(new ConsoleInput(['zc_cli.php', 'zen-test:demo', 'team']), $output);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Hello team', stream_get_contents($stdout, -1, 0));
    }

    /**
     * @return array{resource, resource, ConsoleOutput}
     */
    private function makeOutput(): array
    {
        $stdout = fopen('php://temp', 'w+');
        $stderr = fopen('php://temp', 'w+');

        return [$stdout, $stderr, new ConsoleOutput($stdout, $stderr)];
    }
}
