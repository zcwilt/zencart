<?php
/**
 * CLI bootstrap for Zen Cart console commands.
 *
 * This intentionally avoids any admin/page/session bootstrap so commands can
 * start from a small, predictable runtime and opt into heavier services later.
 *
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This entry point is only available from the command line.\n");
    exit(1);
}

if (!defined('ZENCART_CONSOLE_RUNNING')) {
    define('ZENCART_CONSOLE_RUNNING', true);
}

if (!defined('IS_ADMIN_FLAG')) {
    define('IS_ADMIN_FLAG', false);
}

$catalogRoot = preg_replace('#/includes/$#', '/', realpath(__DIR__) . '/');
$includesRoot = $catalogRoot . 'includes/';

date_default_timezone_set(date_default_timezone_get());

if (!defined('DIR_FS_CATALOG')) {
    define('DIR_FS_CATALOG', $catalogRoot);
}

if (!defined('DIR_FS_INCLUDES')) {
    define('DIR_FS_INCLUDES', DIR_FS_CATALOG . 'includes/');
}

if (!defined('DIR_FS_ADMIN')) {
    define('DIR_FS_ADMIN', DIR_FS_CATALOG . 'admin/');
}

if (!defined('DIR_WS_CATALOG')) {
    define('DIR_WS_CATALOG', '/');
}

if (!defined('DIR_WS_ADMIN')) {
    define('DIR_WS_ADMIN', '/admin/');
}

require_once DIR_FS_INCLUDES . 'defined_paths.php';
require_once DIR_FS_INCLUDES . 'functions/php_polyfills.php';
require_once DIR_FS_INCLUDES . 'functions/zen_define_default.php';
require_once DIR_FS_INCLUDES . 'classes/vendors/AuraAutoload/src/Loader.php';

$psr4Autoloader = new \Aura\Autoload\Loader();
$psr4Autoloader->register();

require DIR_FS_INCLUDES . 'psr4Autoload.php';

return $psr4Autoloader;
