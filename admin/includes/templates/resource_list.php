<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *
 * Expected variables:
 * - string $pageHeading
 * - Zencart\ViewBuilders\SimpleDataFormatter $formatter
 * - object $tableController
 * - Zencart\AdminUi\Pages\ListViewConfig|null $listViewConfig
 * - Zencart\AdminUi\Pages\ListFooterConfig|null $footerConfig
 */
?>
<div class="container-fluid">
    <h1><?= $pageHeading ?></h1>
    <?php
    $toolbarFormName = 'table-search';
    $toolbarColumnClass = 'col-xs-12';
    $toolbarHiddenParameters = method_exists($formatter, 'searchHiddenParameters')
        ? $formatter->searchHiddenParameters()
        : [];
    require DIR_FS_ADMIN . 'includes/templates/partials/resource_toolbar.php';
    ?>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-9 col-lg-9 configurationColumnLeft">
            <?php
            $tableDataSet = $formatter->getTableData();
            $listViewConfig = $listViewConfig ?? new \Zencart\AdminUi\Pages\ListViewConfig();
            $groupByField = $listViewConfig->groupByField();
            $groupOrder = $listViewConfig->groupOrder();
            $groupLabels = $listViewConfig->groupLabels();
            $columnWidths = $listViewConfig->columnWidths();

            if ($groupByField === null || $groupOrder === []) {
                $groupOrder = [null];
            }

            foreach ($groupOrder as $groupValue) {
                $rowsForGroup = [];
                foreach ($tableDataSet as $tableData) {
                    if ($groupValue === null) {
                        $rowsForGroup[] = $tableData;
                        continue;
                    }

                    if (
                        isset($tableData[$groupByField])
                        && array_key_exists('original', $tableData[$groupByField])
                        && $tableData[$groupByField]['original'] === $groupValue
                    ) {
                        $rowsForGroup[] = $tableData;
                    }
                }

                if ($rowsForGroup === []) {
                    continue;
                }
                ?>
                <table class="table table-hover">
                    <thead>
                    <tr class="dataTableHeadingRow">
                        <?php
                        $firstHeader = true;
                        foreach ($formatter->getTableHeaders() as $index => $colHeader) {
                            $colWidth = $columnWidths[$index] ?? '';
                            ?>
                            <th class="<?= trim($colHeader['headerClass'] . ' ' . $colWidth) ?>">
                                <?php
                                if ($firstHeader && $groupValue !== null && isset($groupLabels[$groupValue])) {
                                    echo $groupLabels[$groupValue];
                                } elseif (!empty($colHeader['href'])) {
                                    echo '<a href="' . $colHeader['href'] . '">' . $colHeader['title'] . ($colHeader['sortIndicator'] ?? '') . '</a>';
                                } else {
                                    echo $colHeader['title'];
                                }
                                $firstHeader = false;
                                ?>
                            </th>
                            <?php
                        } ?>
                        <th class="dataTableHeadingContent w-5 text-right"><?= TABLE_HEADING_ACTION ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rowsForGroup as $tableData) { ?>
                        <?php if ($formatter->isRowSelected($tableData)) { ?>
                            <tr id="defaultSelected" class="dataTableRowSelected js-resource-row-link" data-row-link="<?= $formatter->getSelectedRowLink($tableData) ?>">
                        <?php } else { ?>
                            <tr class="dataTableRow js-resource-row-link" data-row-link="<?= $formatter->getNotSelectedRowLink($tableData) ?>">
                        <?php } ?>
                        <?php foreach ($tableData as $column) { ?>
                            <?php $cellClass = trim('dataTableContent ' . ($column['class'] ?? '')); ?>
                            <td class="<?= $cellClass ?>"><?= $column['value'] ?></td>
                        <?php } ?>
                        <?php require DIR_FS_ADMIN . 'includes/templates/partials/tableview_rowactions.php'; ?>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 configurationColumnRight">
            <?php
            $boxHeader = $tableController->getBoxHeader();
            $boxContent = $tableController->getBoxContent();
            require DIR_FS_ADMIN . 'includes/templates/partials/resource_infobox.php';
            ?>
        </div>
    </div>
    <?php
    $footerConfig = $footerConfig ?? new \Zencart\AdminUi\Pages\ListFooterConfig();
    $countHtml = $footerConfig->countHtml();
    $linksHtml = $footerConfig->linksHtml();
    $primaryActionHref = $footerConfig->primaryActionHref();
    $primaryActionLabel = $footerConfig->primaryActionLabel();
    require DIR_FS_ADMIN . 'includes/templates/partials/resource_footer.php';
    ?>
</div>
<?php require DIR_FS_ADMIN . 'includes/templates/partials/resource_list_behaviors.php'; ?>
