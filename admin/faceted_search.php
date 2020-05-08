<?php
/**
 * @package admin
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id:
 */

use Zencart\QueryBuilder\QueryBuilder;
use Zencart\TableViewControllers\FacetedSearchController;

require('includes/application_top.php');
require('includes/classes/split_page_results_new.php');

$tableDefinition = [
    'colKey'           => 'id',
    'actions'          => [
        [
            'action'                => 'new', 'text' => 'new', 'getParams' => [],
            'showOnlyOnEmptyAction' => true
        ],
    ],
    'defaultRowAction' => '',
    'columns'          => [
        'id'            => ['title' => TABLE_HEADING_ID],
        'products_id'   => ['title' => TABLE_HEADING_PRODUCTS_ID],
        'products_name' => ['title' => TABLE_HEADING_PRODUCTS_NAME],
    ]
];


$tableController = (new FacetedSearchController($db, $messageStack, new QueryBuilder($db), $tableDefinition))->processRequest();

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
