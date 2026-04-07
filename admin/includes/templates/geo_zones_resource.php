<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *
 * @var Zencart\AdminUi\Resources\GeoZones\GeoZonesController $geoZonesController
 * @var string $pageHeading
 */
$detailMode = $geoZonesController->isDetailMode();
$rows = $geoZonesController->listingRows();
$splitResults = $geoZonesController->splitResults();
$queryNumRows = $geoZonesController->queryNumRows();
$selectedGeoZone = $geoZonesController->selectedGeoZone();
$selectedSubZone = $geoZonesController->selectedSubZone();
?>
<div class="container-fluid">
    <?php if ($geoZonesController->showZoneScript()) { ?>
        <script>
            function resetZoneSelected(theForm) {
                if (theForm.state && theForm.state.value !== '') {
                    theForm.zone_id.selectedIndex = 0;
                    if (theForm.zone_id.options.length > 0) {
                        theForm.state.value = '<?= JS_STATE_SELECT ?>';
                    }
                }
            }

            function update_zone(theForm) {
                var numState = theForm.zone_id.options.length;
                var selectedCountry = '';

                while (numState > 0) {
                    numState--;
                    theForm.zone_id.options[numState] = null;
                }

                selectedCountry = theForm.zone_country_id.options[theForm.zone_country_id.selectedIndex].value;
                <?= zen_js_zone_list('selectedCountry', 'theForm', 'zone_id'); ?>
            }
        </script>
    <?php } ?>
    <h1><?= $pageHeading ?></h1>
    <p><?php if ($geoZonesController->currentZoneId() > 0) { echo $geoZonesController->currentZoneName(); } ?></p>

    <?php if ($detailMode) { ?>
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-9 col-lg-9 configurationColumnLeft">
                <table class="table table-hover" role="listbox">
                    <thead>
                    <tr class="dataTableHeadingRow">
                        <th class="dataTableHeadingContent"><?= TABLE_HEADING_COUNTRY_NAME ?></th>
                        <th class="dataTableHeadingContent"><?= TABLE_HEADING_COUNTRY_ZONE ?></th>
                        <th class="dataTableHeadingContent text-right"><?= TABLE_HEADING_ACTION ?>&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row) { ?>
                        <tr<?= $row['selected'] ? ' id="defaultSelected" class="dataTableRowSelected"' : ' class="dataTableRow"' ?> onclick="document.location.href='<?= $row['rowLink'] ?>'" role="option" aria-selected="<?= $row['selected'] ? 'true' : 'false' ?>">
                            <td class="dataTableContent"><?= $row['countries_name'] ?></td>
                            <td class="dataTableContent"><?= $row['zone_name'] ?></td>
                            <td class="dataTableContent text-right">
                                <?php if ($row['selected']) { ?>
                                    <?= zen_icon('caret-right', '', '2x', true) ?>
                                <?php } else { ?>
                                    <a href="<?= $row['infoLink'] ?>"><?= zen_icon('circle-info', IMAGE_ICON_INFO, '2x', true, false) ?></a>
                                <?php } ?>&nbsp;
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
    <?php } else { ?>
        <div class="row">
            <?= TEXT_LEGEND ?>&nbsp;<?= zen_icon('status-green') . TEXT_LEGEND_TAX_AND_ZONES ?>&nbsp;&nbsp;&nbsp;<?= zen_icon('status-yellow') . TEXT_LEGEND_ONLY_ZONES ?>&nbsp;&nbsp;&nbsp;<?= zen_icon('status-red') . TEXT_LEGEND_NOT_CONF ?>
        </div>
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-9 col-lg-9 configurationColumnLeft">
                <table class="table table-hover" role="listbox">
                    <thead>
                    <tr class="dataTableHeadingRow">
                        <th class="dataTableHeadingContent"><?= TABLE_HEADING_TAX_ZONES ?></th>
                        <th class="dataTableHeadingContent"><?= TABLE_HEADING_TAX_ZONES_DESCRIPTION ?></th>
                        <th class="dataTableHeadingContent text-center"><?= TABLE_HEADING_STATUS ?></th>
                        <th class="dataTableHeadingContent text-right"><?= TABLE_HEADING_ACTION ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row) { ?>
                        <tr<?= $row['selected'] ? ' id="defaultSelected" class="dataTableRowSelected"' : ' class="dataTableRow"' ?> onclick="document.location.href='<?= $row['rowLink'] ?>'" role="option" aria-selected="<?= $row['selected'] ? 'true' : 'false' ?>">
                            <td class="dataTableContent"><a href="<?= $row['folderLink'] ?>"><?= zen_image(DIR_WS_ICONS . 'folder.gif', ICON_FOLDER) ?></a>&nbsp;<?= $row['geo_zone_name'] ?></td>
                            <td class="dataTableContent"><?= $row['geo_zone_description'] ?></td>
                            <td class="dataTableContent text-center"><?= zen_icon($row['status_icon']) ?></td>
                            <td class="dataTableContent text-right">
                                <?php if ($row['selected']) { ?>
                                    <?= zen_icon('caret-right', '', '2x', true) ?>
                                <?php } else { ?>
                                    <a href="<?= $row['infoLink'] ?>"><?= zen_icon('circle-info', IMAGE_ICON_INFO, '2x', true, false) ?></a>
                                <?php } ?>&nbsp;
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
    <?php } ?>
        <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 configurationColumnRight">
            <?php
            $boxHeader = $geoZonesController->getBoxHeader();
            $boxContent = $geoZonesController->getBoxContent();
            require DIR_FS_ADMIN . 'includes/templates/partials/resource_infobox.php';
            ?>
        </div>
    </div>

    <?php
    if ($detailMode) {
        $countHtml = $splitResults?->display_count($queryNumRows, MAX_DISPLAY_SEARCH_RESULTS, $geoZonesController->currentSubZonePage(), TEXT_DISPLAY_NUMBER_OF_GEO_ZONES) ?? '';
        $linksHtml = $splitResults?->display_links($queryNumRows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $geoZonesController->currentSubZonePage(), $geoZonesController->detailPaginationParameters(), 'spage') ?? '';
        $actionHtml = $geoZonesController->getSubAction() === ''
            ? '<a href="' . $geoZonesController->backToZoneListUrl() . '" class="btn btn-default" role="button">' . IMAGE_BACK . '</a> '
                . '<a href="' . $geoZonesController->newSubZoneUrl() . '" class="btn btn-primary" role="button">' . IMAGE_INSERT . '</a>'
            : null;
    } else {
        $countHtml = $splitResults?->display_count($queryNumRows, MAX_DISPLAY_SEARCH_RESULTS, $geoZonesController->currentZonePage(), TEXT_DISPLAY_NUMBER_OF_GEO_ZONES) ?? '';
        $linksHtml = $splitResults?->display_links($queryNumRows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $geoZonesController->currentZonePage(), $geoZonesController->topLevelPaginationParameters(), 'zpage') ?? '';
        $actionHtml = $geoZonesController->getAction() === ''
            ? '<a href="' . $geoZonesController->newGeoZoneUrl() . '" class="btn btn-primary" role="button">' . IMAGE_INSERT . '</a>'
            : null;
    }
    require DIR_FS_ADMIN . 'includes/templates/partials/resource_footer.php';
    ?>
</div>
