<?php
/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id:
 */

if (!isset($category_depth) || $category_depth != 'products') return;
if (!isset($listing_sql)) return;
$concatSql = zen_fs_prepare_listing_sql($listing_sql);
if ($concatSql == '') return;
$productIds = zen_fs_create_productid_list($concatSql);
if (!zen_fs_has_searchable_attributes($productIds)) return;
$rawAttributes = zen_fs_get_searchable_attributes($productIds);
print_r($rawAttributes);

require($template->get_template_dir('tpl_faceted_search.php',DIR_WS_TEMPLATE, $current_page_base,'sideboxes'). '/tpl_faceted_search.php');
$title = '<label>' . BOX_HEADING_SEARCH . '</label>';
$title_link = false;
require($template->get_template_dir($column_box_default, DIR_WS_TEMPLATE, $current_page_base,'common') . '/' . $column_box_default);
