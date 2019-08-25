<?php
/**
 * @package admin
 * @copyright Copyright 2003-2019 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id:
 */

use Zencart\PluginSupport\SqlPatchInstaller;
use Zencart\PluginSupport\ScriptedInstaller;
use Zencart\PluginSupport\PluginConfigInstaller;
use Zencart\PluginSupport\InstallerFactory;
use Zencart\PluginSupport\Installer;
use Zencart\PluginManager\PluginManager;
use Zencart\QueryBuilder\QueryBuilder;
use Zencart\TableViewControllers\PluginManagerController;

require('includes/application_top.php');

$pluginSqlInstaller = new Installer(new SqlPatchInstaller($db), new ScriptedInstaller($db));

$installerFactory = new InstallerFactory($db, $pluginSqlInstaller, new PluginConfigInstaller());


$tableDefinition = [];

$tableController = (new PluginManagerController(
    $db, $messageStack, new QueryBuilder($db), $tableDefinition, $installerFactory))->processRequest();


?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
<head>
    <meta charset="<?php echo CHARSET; ?>">
    <title><?php echo TITLE; ?></title>
    <link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
    <link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
    <script src="includes/menu.js"></script>
    <script src="includes/general.js"></script>
    <script>
        function init() {
            cssjsmenu('navbar');
            if (document.getElementById) {
                var kill = document.getElementById('hoverJS');
                kill.disabled = true;
            }
        }
    </script>
</head>
<body onload="init()">
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->

<?php require "includes/templates/table_view.php"; ?>

<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
