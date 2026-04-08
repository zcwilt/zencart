<?php
/**
 * Zen Cart console entry point.
 *
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

$psr4Autoloader = require __DIR__ . '/includes/application_cli_bootstrap.php';

$input = new \Zencart\Console\ConsoleInput($_SERVER['argv'] ?? []);
$output = new \Zencart\Console\ConsoleOutput();
$pluginDiscovery = new \Zencart\Console\PluginCommandDiscovery(__DIR__ . '/zc_plugins', $psr4Autoloader);
$kernel = new \Zencart\Console\ConsoleKernel(null, $pluginDiscovery);

exit($kernel->run($input, $output));
