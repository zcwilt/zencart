<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @author inspired from sales_report_graphs.php,v 0.01 2002/11/27 19:02:22 cwi Exp  Released under the GNU General Public License $
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: torvista 2026 Mar 04 Modified in v2.2.1 $
 */

use Zencart\AdminUi\Reports\ReportResolver;
use Zencart\AdminUi\Reports\StatsSalesReportGraphsReport;

require 'includes/application_top.php';

$reportClass = ReportResolver::getInstance()->resolve('stats_sales_report_graphs', StatsSalesReportGraphsReport::class);
$adminPage = (new $reportClass($sanitizedRequest, $messageStack))->buildPage();
extract($adminPage->viewData(), EXTR_SKIP);
?>
<!doctype html>
<html <?= HTML_PARAMS ?>>
  <head>
      <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
  </head>
  <body>
    <?php require DIR_WS_INCLUDES . 'header.php'; ?>
    <?php require $adminPage->templatePath(); ?>
    <?php require DIR_WS_INCLUDES . 'footer.php'; ?>
  </body>
</html>
<?php require DIR_WS_INCLUDES . 'application_bottom.php'; ?>
