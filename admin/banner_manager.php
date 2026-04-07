<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Oct 18 Modified in v2.2.0 $
 */

use Zencart\AdminUi\Resources\BannerManagerResource;
use Zencart\AdminUi\Resources\ResourceResolver;

require 'includes/application_top.php';
require 'includes/functions/functions_banner_graphs.php';

$resourceClass = ResourceResolver::getInstance()->resolve('banner_manager', BannerManagerResource::class);
$adminPage = (new $resourceClass($sanitizedRequest, $messageStack))->buildPage();
extract($adminPage->viewData(), EXTR_SKIP);
?>
<!doctype html>
<html <?= HTML_PARAMS ?>>
  <head>
    <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
    <link rel="stylesheet" href="includes/css/banner_tools.css">
    <script>
      function popupImageWindow(url) {
        window.open(url, 'popupImageWindow', 'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=yes,copyhistory=no,width=100,height=100,screenX=150,screenY=150,top=150,left=150')
      }
    </script>
    <?php if (!empty($editor_handler)) { include $editor_handler; } ?>
  </head>
  <body>
    <div id="spiffycalendar" class="text"></div>
    <!-- header //-->
    <?php require DIR_WS_INCLUDES . 'header.php'; ?>
    <!-- header_eof //-->

    <!--[if lte IE 8]><script src="includes/javascript/flot/excanvas.min.js"></script><![endif]-->
    <script src="includes/javascript/flot/jquery.flot.min.js"></script>
    <script src="includes/javascript/flot/jquery.flot.orderbars.js"></script>

    <!-- body //-->
    <?php require $adminPage->templatePath(); ?>
    <!-- body_eof //-->

    <!-- footer //-->
    <?php require DIR_WS_INCLUDES . 'footer.php'; ?>
    <!-- footer_eof //-->

    <script>
      $(function () {
        $('input[name="date_scheduled"]').datepicker({
          minDate: 0
        });
        $('input[name="expires_date"]').datepicker({
          minDate: 1
        });
      })
    </script>
  </body>
</html>
<?php require DIR_WS_INCLUDES . 'application_bottom.php'; ?>
