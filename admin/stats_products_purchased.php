<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2024 Jun 05 Modified in v2.1.0-alpha1 $
 */

use Zencart\AdminUi\Reports\ReportResolver;
use Zencart\AdminUi\Reports\StatsProductsPurchasedReport;

require 'includes/application_top.php';

$reportClass = ReportResolver::getInstance()->resolve('stats_products_purchased', StatsProductsPurchasedReport::class);
$adminPage = (new $reportClass($sanitizedRequest, $messageStack))->buildPage();
extract($adminPage->viewData(), EXTR_SKIP);
?>
<!doctype html>
<html <?= HTML_PARAMS ?>>
  <head>
      <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
    <link rel="stylesheet" media="print" href="includes/css/stylesheet_print.css">
  </head>
  <body>
    <?php require DIR_WS_INCLUDES . 'header.php'; ?>
    <?php require $adminPage->templatePath(); ?>
    <?php require DIR_WS_INCLUDES . 'footer.php'; ?>
  </body>
</html>
<?php require DIR_WS_INCLUDES . 'application_bottom.php'; ?>
