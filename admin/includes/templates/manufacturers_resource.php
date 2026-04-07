<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *
 * @var Zencart\AdminUi\Resources\Manufacturers\ManufacturersDataFormatter $formatter
 * @var Zencart\AdminUi\Resources\Manufacturers\ManufacturersController $tableController
 * @var splitPageResults|null $manufacturersSplit
 * @var int $manufacturersQueryNumRows
 * @var string $currentPage
 * @var string $pageHeading
 */
$tableRows = $formatter->getTableData();
$rawRows = $formatter->getResultSet()->getCollection();
$currentPageInt = ctype_digit((string) $currentPage) ? (int) $currentPage : 0;
?>
<div class="container-fluid">
    <style>
        .p_label {
            display: inline-block;
            max-width: 100%;
            margin-bottom: 5px;
            font-weight: 700;
        }
    </style>
    <h1><?= $pageHeading ?></h1>
    <?php
    $toolbarFormName = 'manufacturers-search';
    $toolbarColumnClass = 'col-xs-12 col-sm-12 col-md-9 col-lg-9 configurationColumnLeft';
    $toolbarHiddenParameters = $formatter->searchHiddenParameters();
    $searchButtonLabel = TEXT_RESOURCE_LIST_SEARCH_BUTTON;
    $resetButtonLabel = TEXT_RESOURCE_LIST_RESET_BUTTON;
    require DIR_FS_ADMIN . 'includes/templates/partials/resource_toolbar.php';
    ?>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-9 col-lg-9 configurationColumnLeft">
            <table class="table table-hover" role="listbox">
                <thead>
                <tr class="dataTableHeadingRow">
                    <th class="dataTableHeadingContent text-center"><?= TABLE_HEADING_ID ?></th>
                    <th class="dataTableHeadingContent"><?= TABLE_HEADING_MANUFACTURERS ?></th>
                    <th class="dataTableHeadingContent"><?= TABLE_HEADING_MANUFACTURER_FEATURED ?></th>
                    <?php
                    $extraHeadings = false;
                    $zco_notifier->notify('NOTIFY_ADMIN_MANUFACTURERS_EXTRA_COLUMN_HEADING', [], $extraHeadings);
                    if (is_array($extraHeadings)) {
                        foreach ($extraHeadings as $headingInfo) {
                            $align = isset($headingInfo['align']) ? ' text-' . $headingInfo['align'] : '';
                            ?>
                            <th class="dataTableHeadingContent<?= $align ?>"><?= $headingInfo['text'] ?></th>
                            <?php
                        }
                    }
                    ?>
                    <th class="dataTableHeadingContent text-right"><?= TABLE_HEADING_ACTION ?>&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($tableRows as $index => $tableRow) { ?>
                    <?php
                    $manufacturer = $rawRows[$index] ?? null;
                    $editLink = $formatter->editRowLink($tableRow);
                    $infoLink = $formatter->getNotSelectedRowLink($tableRow);
                    $manufacturerId = $tableRow['manufacturers_id']['value'] ?? '';
                    ?>
                    <?php if ($formatter->isRowSelected($tableRow)) { ?>
                        <tr id="defaultSelected" class="dataTableRowSelected" onclick="document.location.href='<?= $editLink ?>'" role="option" aria-selected="true">
                    <?php } else { ?>
                        <tr class="dataTableRow" onclick="document.location.href='<?= $editLink ?>'" role="option" aria-selected="false" style="cursor:pointer;">
                    <?php } ?>
                    <td class="dataTableContent text-center"><?= $tableRow['manufacturers_id']['value'] ?></td>
                    <td class="dataTableContent"><?= $tableRow['manufacturers_name']['value'] ?></td>
                    <td class="dataTableContent"><?= $tableRow['featured']['value'] ?></td>
                    <?php
                    $extraData = false;
                    $zco_notifier->notify('NOTIFY_ADMIN_MANUFACTURERS_EXTRA_COLUMN_DATA', $manufacturer, $extraData);
                    if (is_array($extraData)) {
                        foreach ($extraData as $dataInfo) {
                            $align = isset($dataInfo['align']) ? ' text-' . $dataInfo['align'] : '';
                            ?>
                            <td class="dataTableContent<?= $align ?>"><?= $dataInfo['text'] ?></td>
                            <?php
                        }
                    }
                    ?>
                    <td class="dataTableContent text-right actions">
                        <div class="btn-group">
                            <a href="<?= $editLink ?>" class="btn btn-sm btn-default btn-edit" role="button" data-toggle="tooltip" title="<?= ICON_EDIT ?>">
                                <?= zen_icon('pencil', hidden: true) ?>
                            </a>
                            <a href="<?= zen_href_link(FILENAME_MANUFACTURERS, ($currentPageInt !== 0 ? 'page=' . $currentPageInt . '&' : '') . 'mID=' . $manufacturerId . '&action=delete') ?>" class="btn btn-sm btn-default btn-delete" role="button" data-toggle="tooltip" title="<?= ICON_DELETE ?>">
                                <?= zen_icon('trash', hidden: true) ?>
                            </a>
                        </div>
                        <?php if ($formatter->isRowSelected($tableRow)) { ?>
                            <?= zen_icon('caret-right', '', '2x', true) ?>
                        <?php } else { ?>
                            <a href="<?= $infoLink ?>">
                                <?= zen_icon('circle-info', '', '2x', true, true) ?>
                            </a>
                        <?php } ?>
                    </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 configurationColumnRight">
            <?php
            $boxHeader = $tableController->getBoxHeader();
            $boxContent = $tableController->getBoxContent();
            require DIR_FS_ADMIN . 'includes/templates/partials/resource_infobox.php';
            ?>
        </div>
    </div>
    <?php if ($tableController->getAction() === '') { ?>
        <div class="col-sm-12 text-right">
            <?php $selectedManufacturerId = $formatter->currentRowFromRequest()['manufacturers_id'] ?? $formatter->currentRowFromRequest()->manufacturers_id ?? ''; ?>
            <a href="<?= zen_href_link(FILENAME_MANUFACTURERS, ($currentPageInt !== 0 ? 'page=' . $currentPageInt . '&' : '') . ($selectedManufacturerId !== '' ? 'mID=' . $selectedManufacturerId . '&' : '') . 'action=new') ?>" class="btn btn-primary" role="button"><?= IMAGE_INSERT ?></a>
        </div>
    <?php } ?>
    <?php
    $countHtml = $manufacturersSplit?->display_count($manufacturersQueryNumRows, MAX_DISPLAY_SEARCH_RESULTS, $currentPage, TEXT_DISPLAY_NUMBER_OF_MANUFACTURERS) ?? '';
    $linksHtml = $manufacturersSplit?->display_links($manufacturersQueryNumRows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $currentPage) ?? '';
    require DIR_FS_ADMIN . 'includes/templates/partials/resource_footer.php';
    ?>
</div>
<?php require DIR_FS_ADMIN . 'includes/templates/partials/resource_list_behaviors.php'; ?>
