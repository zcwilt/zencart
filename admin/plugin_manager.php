<?php
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Oct 25 Modified in v2.2.0 $
 */

use Zencart\AdminUi\Resources\PluginManagerResource;
use Zencart\AdminUi\Resources\ResourceResolver;
use Zencart\PluginManager\PluginManager;
use Zencart\PluginSupport\Installer;
use Zencart\PluginSupport\InstallerFactory;
use Zencart\PluginSupport\PluginErrorContainer;
use Zencart\PluginSupport\ScriptedInstallerFactory;
use Zencart\PluginSupport\SqlPatchInstaller;

/* @var PluginManager $pluginManager */
/* @var queryFactory $db */
/* @var messageStack $messageStack */

require 'includes/application_top.php';
$pluginManager->inspectAndUpdate();

// These next few classes are only needed by the plugin manager
$errorContainer = new PluginErrorContainer();
$pluginInstaller = new Installer(new SqlPatchInstaller($db, $errorContainer), new ScriptedInstallerFactory($db, $errorContainer), $errorContainer);
$installerFactory = new InstallerFactory($db, $pluginInstaller, $errorContainer);
$resourceClass = ResourceResolver::getInstance()->resolve('plugin_manager', PluginManagerResource::class);
$adminPage = (new $resourceClass($sanitizedRequest, $messageStack, $pluginManager, $installerFactory))->buildPage();
extract($adminPage->viewData(), EXTR_SKIP);

?>
<!doctype html>
<html <?= HTML_PARAMS ?>>
<head>
<?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
      <style>
          .w-20 {width: 20%}
          .w-15 {width: 15%}
          .w-10 {width: 10%}
          .w-5 {width: 5%}
      </style>
</head>
<body>
<!-- header //-->
<?php require DIR_WS_INCLUDES . 'header.php'; ?>
<!-- header_eof //-->

<!-- body //-->
<?php
require $adminPage->templatePath();
?>
<!-- body_eof //-->

<!-- footer //-->
<?php require DIR_WS_INCLUDES . 'footer.php'; ?>
<!-- footer_eof //-->
</body>
</html>
<?php require DIR_WS_INCLUDES . 'application_bottom.php'; ?>
