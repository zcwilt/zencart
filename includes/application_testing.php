<?php
/**
 * application_testing.php
 * Carry out some actions if we are using test framework
 *
 * @copyright Copyright 2003-2023 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}
if (!defined('ZENCART_TESTFRAMEWORK_RUNNING')) {
    return;
}
$user = $_SERVER['USER'] ?? $_SERVER['MY_USER'];
$config = './not_for_release/testFramework/Support/configs/' . $user . '.store.configure.php';
require($config);

