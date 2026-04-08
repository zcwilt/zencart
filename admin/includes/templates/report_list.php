<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *
 * Expected variables:
 * - string $pageHeading
 * - object $formatter
 * - Zencart\AdminUi\Pages\ListFooterConfig|null $footerConfig
 * - Zencart\AdminUi\Pages\ReportViewConfig|null $reportViewConfig
 */
?>
<div class="container-fluid">
    <h1><?= $pageHeading ?></h1>
    <?php
    $toolbarFormName = 'report-search';
    $toolbarColumnClass = 'col-xs-12';
    $toolbarHiddenParameters = method_exists($formatter, 'searchHiddenParameters')
        ? $formatter->searchHiddenParameters()
        : [];
    require DIR_FS_ADMIN . 'includes/templates/partials/resource_toolbar.php';
    $reportViewConfig = $reportViewConfig ?? new \Zencart\AdminUi\Pages\ReportViewConfig();
    if ($reportViewConfig->summaryHtml() !== '') {
        echo $reportViewConfig->summaryHtml();
    }
    ?>
    <div class="row">
        <div class="col-xs-12">
            <?php $tableDataSet = $formatter->getTableData(); ?>
            <?php if ($tableDataSet === []) { ?>
                <?= $reportViewConfig->emptyStateHtml() ?>
            <?php } else { ?>
                <table class="table table-hover">
                    <thead>
                    <tr class="dataTableHeadingRow">
                        <?php foreach ($formatter->getTableHeaders() as $colHeader) { ?>
                            <th class="<?= $colHeader['headerClass'] ?? 'dataTableHeadingContent' ?>"><?= $colHeader['title'] ?? '' ?></th>
                        <?php } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tableDataSet as $tableData) { ?>
                        <?php
                        $rowLink = method_exists($formatter, 'rowLink') ? $formatter->rowLink($tableData) : null;
                        $rowClass = $rowLink !== null ? ' class="dataTableRow js-resource-row-link" data-row-link="' . htmlspecialchars($rowLink, ENT_QUOTES) . '"' : ' class="dataTableRow"';
                        ?>
                        <tr<?= $rowClass ?>>
                            <?php foreach ($tableData as $field => $column) { ?>
                                <?php if (str_starts_with((string) $field, '_')) { continue; } ?>
                                <td class="<?= trim('dataTableContent ' . ($column['class'] ?? '')) ?>"><?= $column['value'] ?></td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
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
<?= $reportViewConfig->afterHtml() ?>
