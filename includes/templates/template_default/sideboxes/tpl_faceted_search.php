<?php
/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Scott C Wilson Fri Oct 12 20:38:41 2018 -0400 Modified in v1.5.6 $
 */
$content = '';

$content .= '<div id="' . str_replace('_', '-', $box_id . 'Content') . '" class="sideBoxContent centeredContent">';
//$content .= '<pre>' . print_r($processedAttributes, true) . '</pre>';
$content .= '<ul>';
foreach ($processedAttributes as $fsAttrKey => $fsAttrValue) {
    $content .= '<li>' . $fsAttrKey . '-' . $fsAttrValue['name'] . '</li>';
    $content .= '<ul>';
    foreach ($fsAttrValue['values'] as $optValKey => $optValValue) {
        $content .= '<li>' . $optValKey . '-' . $optValValue['name'] . '</li>';
    }
    $content .= '</ul>';
}
$content .= '</ul>';
$content .= '</div>';
