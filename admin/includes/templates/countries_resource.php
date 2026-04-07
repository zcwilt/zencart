<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *
 * @var Zencart\ViewBuilders\SimpleDataFormatter $formatter
 * @var Zencart\AdminUi\Resources\Countries\CountriesController $tableController
 * @var splitPageResults|null $countriesSplit
 * @var int $countriesQueryNumRows
 * @var string $currentPage
 * @var string $pageParameter
 * @var string $pageHeading
 * @var string $pageTopLinkHtml
 */
$headers = $formatter->getTableHeaders();
$countryNameHeader = $headers[1] ?? ['headerClass' => 'dataTableHeadingContent col-sm-6', 'title' => TABLE_HEADING_COUNTRY_NAME, 'href' => null, 'sortIndicator' => ''];
$statusHeader = $headers[4] ?? ['title' => TABLE_HEADING_COUNTRY_STATUS];
$footerLinkParameters = $formatter->getPersistentLinkParameters(['page', 'cID', 'action']);
/**
 * Build a normal anchor for selectable row cells so browser navigation does not rely on row-level JS.
 */
$renderRowCellLink = static function (?string $href, string $content, string $cellClass = ''): string {
    $anchorClass = trim('country-row-link-text ' . $cellClass);
    if ($href === null || $href === '') {
        return $content;
    }

    return '<a href="' . htmlspecialchars($href, ENT_QUOTES) . '" class="' . htmlspecialchars($anchorClass, ENT_QUOTES) . '" role="button">' . $content . '</a>';
};
?>
<div class="container-fluid">
    <style>
        tr.country-row-link {
            cursor: pointer;
        }

        .country-row-link-text {
            color: inherit;
            cursor: pointer;
            display: block;
            text-decoration: none;
        }
    </style>
    <h1><?= $pageHeading ?></h1>
    <div class="text-right"><?= $pageTopLinkHtml ?></div>
    <?php
    $toolbarFormName = 'countries-search';
    $toolbarColumnClass = 'col-xs-12 col-sm-12 col-md-9 col-lg-9 configurationColumnLeft';
    $toolbarHiddenParameters = $formatter->toolbarHiddenParameters();
    $searchButtonLabel = TEXT_RESOURCE_LIST_SEARCH_BUTTON;
    $resetButtonLabel = TEXT_RESOURCE_LIST_RESET_BUTTON;
    require DIR_FS_ADMIN . 'includes/templates/partials/resource_toolbar.php';
    ?>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-9 col-lg-9 configurationColumnLeft">
            <table class="table table-hover" role="listbox">
                <thead>
                <tr class="dataTableHeadingRow">
                    <th class="<?= $countryNameHeader['headerClass'] ?>">
                        <?php if (!empty($countryNameHeader['href'])) { ?>
                            <a href="<?= $countryNameHeader['href'] ?>"><?= $countryNameHeader['title'] ?><?= $countryNameHeader['sortIndicator'] ?? '' ?></a>
                        <?php } else { ?>
                            <?= $countryNameHeader['title'] ?>
                        <?php } ?>
                    </th>
                    <th class="dataTableHeadingContent text-center" colspan="2"><?= TABLE_HEADING_COUNTRY_CODES ?></th>
                    <th class="dataTableHeadingContent text-center"><?= $statusHeader['title'] ?></th>
                    <th class="dataTableHeadingContent text-right"><?= TABLE_HEADING_ACTION ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($formatter->getTableData() as $tableData) { ?>
                    <?php
                    $isSelectedRow = $formatter->isRowSelected($tableData);
                    $rowLink = $isSelectedRow ? '' : $formatter->getNotSelectedRowLink($tableData);
                    ?>
                    <?php if ($isSelectedRow) { ?>
                        <tr id="defaultSelected" class="dataTableRowSelected country-row-link js-resource-row-link" data-row-link="<?= $formatter->getSelectedRowLink($tableData) ?>" role="option" aria-selected="true">
                    <?php } else { ?>
                        <tr class="dataTableRow country-listing-row country-row-link js-resource-row-link" data-row-link="<?= $formatter->getNotSelectedRowLink($tableData) ?>" data-cid="<?= $tableData['countries_id']['original'] ?>" role="option" aria-selected="false">
                    <?php } ?>
                    <td class="dataTableContent col-sm-6"><?= $renderRowCellLink($rowLink, zen_output_string_protected($tableData['countries_name']['value']), 'country-row-link-block') ?></td>
                    <td class="dataTableContent text-center"><?= $renderRowCellLink($rowLink, (string) $tableData['countries_iso_code_2']['value'], 'country-row-link-block text-center') ?></td>
                    <td class="dataTableContent text-center"><?= $renderRowCellLink($rowLink, (string) $tableData['countries_iso_code_3']['value'], 'country-row-link-block text-center') ?></td>
                    <td class="dataTableContent text-center dataTableButtonCell" onmousedown="event.stopPropagation();" onclick="event.stopPropagation(); return false;">
                        <?php
                        $statusForm = zen_draw_form(
                            'setstatus_' . (int) $tableData['countries_id']['original'],
                            FILENAME_COUNTRIES,
                            $tableController->statusFormParameters()
                        );
                        echo preg_replace('/^<form\b/', '<form onsubmit="event.stopPropagation();" onmousedown="event.stopPropagation();" onclick="event.stopPropagation();"', $statusForm, 1);
                        ?>
                        <button type="button" class="btn btn-status" onmousedown="event.stopPropagation();" onclick="event.stopPropagation(); event.preventDefault(); this.form.submit(); return false;">
                            <?php if ((int) $tableData['status']['original'] === 0) { ?>
                                <i class="fa-solid fa-square txt-status-off" title="<?= IMAGE_ICON_STATUS_OFF ?>" onmousedown="event.stopPropagation();" onclick="event.stopPropagation(); event.preventDefault(); this.closest('form').submit(); return false;"></i>
                            <?php } else { ?>
                                <i class="fa-solid fa-square txt-status-on" title="<?= IMAGE_ICON_STATUS_ON ?>" onmousedown="event.stopPropagation();" onclick="event.stopPropagation(); event.preventDefault(); this.closest('form').submit(); return false;"></i>
                            <?php } ?>
                        </button>
                        <?php
                        echo zen_draw_hidden_field('current_country', $tableData['countries_id']['original']);
                        echo zen_draw_hidden_field('current_status', $tableData['status']['original']);
                        echo '</form>';
                        ?>
                    </td>
                    <td class="dataTableContent text-right">
                        <?php if ($isSelectedRow) { ?>
                            <?= zen_icon('caret-right', '', '2x', true) ?>
                        <?php } else { ?>
                            <a href="<?= $formatter->getNotSelectedRowLink($tableData) ?>" title="<?= IMAGE_ICON_INFO ?>" role="button"><?= zen_icon('circle-info', '', '2x', true, false) ?></a>
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
    <?php
    $countHtml = $countriesSplit?->display_count($countriesQueryNumRows, MAX_DISPLAY_SEARCH_RESULTS, $currentPage, TEXT_DISPLAY_NUMBER_OF_COUNTRIES) ?? '';
    $linksHtml = $countriesSplit?->display_links($countriesQueryNumRows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $currentPage, $footerLinkParameters) ?? '';
    $actionHtml = $tableController->shouldShowNewCountryAction()
        ? '<a href="' . $tableController->newCountryUrl() . '" class="btn btn-primary" role="button">' . IMAGE_NEW_COUNTRY . '</a>'
        : null;
    require DIR_FS_ADMIN . 'includes/templates/partials/resource_footer.php';
    ?>
</div>
<?php require DIR_FS_ADMIN . 'includes/templates/partials/resource_list_behaviors.php'; ?>
