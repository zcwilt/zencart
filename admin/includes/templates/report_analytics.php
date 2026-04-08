<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *
 * Expected variables:
 * - string $pageHeading
 * - array $navigationLinks
 * - array $chartConfigs
 * - array $tableHeaders
 * - array $tableRows
 * - array $summaryRows
 * - array $filterRows
 * - string|null $previousLink
 * - string|null $nextLink
 */
?>
<div class="container-fluid">
  <h1><?= $pageHeading ?></h1>

  <table class="table">
    <tr>
      <td class="menuBoxHeading text-right">
        <?php foreach ($navigationLinks as $index => $link) { ?>
          <a href="<?= $link['href'] ?>"><?= $link['label'] ?></a><?= $index < count($navigationLinks) - 1 ? ' | ' : '' ?>
        <?php } ?>
      </td>
    </tr>
  </table>

  <div class="col-sm-12 col-md-6">
    <?php foreach ($chartConfigs as $chartConfig) { ?>
      <div id="<?= htmlspecialchars($chartConfig['containerId'], ENT_QUOTES) ?>"></div>
    <?php } ?>
  </div>

  <div class="col-sm-12 col-md-6">
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead>
          <tr class="dataTableHeadingRow">
            <?php foreach ($tableHeaders as $header) { ?>
              <th class="<?= $header['class'] ?>"><?= $header['title'] ?></th>
            <?php } ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tableRows as $row) { ?>
            <tr class="dataTableRow">
              <td class="dataTableContent"><?= $row['label'] ?></td>
              <td class="dataTableContent text-center"><?= $row['count'] ?></td>
              <td class="dataTableContent text-right"><?= $row['avg'] ?></td>
              <td class="dataTableContent text-right"><?= $row['sum'] ?></td>
              <td class="dataTableContent text-right"><?= $row['variance'] ?></td>
            </tr>
          <?php } ?>
        </tbody>
        <tfoot>
          <?php if ($previousLink !== null || $nextLink !== null) { ?>
            <tr>
              <td colspan="2"><?= $previousLink !== null ? '<a href="' . $previousLink . '">' . TEXT_PREVIOUS_LINK . '</a>' : '' ?></td>
              <td>&nbsp;</td>
              <td colspan="2" class="text-right"><?= $nextLink !== null ? '<a href="' . $nextLink . '">' . TEXT_NEXT_LINK . '</a>' : '' ?></td>
            </tr>
          <?php } ?>
        </tfoot>
      </table>
    </div>

    <table class="table">
      <?php foreach ($summaryRows as $summaryRow) { ?>
        <tr class="dataTableRow">
          <td class="dataTableContent text-right"><?= $summaryRow['label'] ?></td>
          <td class="dataTableContent text-right"><?= $summaryRow['value'] ?></td>
        </tr>
      <?php } ?>
    </table>

    <table class="table table-condensed">
      <tr class="dataTableRow">
        <td class="dataTableContent text-left col-sm-10"><?= FILTER_STATUS ?></td>
        <td class="dataTableContent text-right"><?= FILTER_VALUE ?></td>
      </tr>
      <?php foreach ($filterRows as $filterRow) { ?>
        <tr>
          <td class="dataTableContent text-left"><?= $filterRow['label'] ?></td>
          <td class="dataTableContent text-right col-sm-12"><?= $filterRow['valueHtml'] ?></td>
        </tr>
      <?php } ?>
    </table>
  </div>
</div>

<script src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    google.charts.load('current', {packages: ['corechart']});
    google.charts.setOnLoadCallback(function () {
        <?php foreach ($chartConfigs as $chartConfig) { ?>
        (function () {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'label');
            data.addColumn('number', <?= json_encode($chartConfig['seriesLabel']) ?>);
            data.addRows(<?= json_encode($chartConfig['data']) ?>);

            var options = {
                title: <?= json_encode($chartConfig['title']) ?>,
                legend: 'bottom',
                is3D: false,
                width: 600,
                height: 450,
                colors: [<?= json_encode($chartConfig['color']) ?>],
                vAxis: {minValue: 0}
            };

            var chart = new google.visualization.ColumnChart(document.getElementById(<?= json_encode($chartConfig['containerId']) ?>));
            chart.draw(data, options);
        })();
        <?php } ?>
    });
</script>
