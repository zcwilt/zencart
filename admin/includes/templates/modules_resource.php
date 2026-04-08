<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *
 * @var Zencart\AdminUi\Resources\Modules\ModulesController $modulesController
 * @var string $pageHeading
 */
$availableNotifications = $modulesController->availableNotifications();
$notificationsTemplatePath = DIR_WS_MODULES . 'notificationsDisplay.php';
if (!is_file($notificationsTemplatePath) && defined('DIR_FS_ADMIN')) {
    $notificationsTemplatePath = DIR_FS_ADMIN . 'includes/modules/notificationsDisplay.php';
}
$infoBoxTemplatePath = DIR_FS_ADMIN . 'includes/templates/partials/resource_infobox.php';
$footerTemplatePath = DIR_FS_ADMIN . 'includes/templates/partials/resource_footer.php';
?>
<div class="container-fluid">
  <h1><?= $pageHeading ?></h1>

  <div class="row">
    <?php if (is_file($notificationsTemplatePath)) {
        require $notificationsTemplatePath;
    } ?>
    <div class="col-xs-12 col-sm-12 col-md-9 col-lg-9 configurationColumnLeft">
      <?php foreach (['enabled' => TEXT_ENABLED, 'available' => TEXT_AVAILABLE] as $groupKey => $groupLabel) { ?>
        <table class="table table-hover">
          <thead>
            <tr class="dataTableHeadingRow">
              <th class="dataTableHeadingContent" style="width:40%;"><?= $groupLabel ?> <?= TABLE_HEADING_MODULES ?></th>
              <th class="dataTableHeadingContent" style="width:20%;"><?= defined('TEXT_INTERNAL_MODULE_ID') ? TEXT_INTERNAL_MODULE_ID : '(Module ID)' ?></th>
              <th class="dataTableHeadingContent text-right" style="width:10%;"><?= TABLE_HEADING_SORT_ORDER ?></th>
              <?php if ($modulesController->isPaymentSet()) { ?>
                <th class="dataTableHeadingContent text-center" style="width:20%;"><?= TABLE_HEADING_ORDERS_STATUS ?></th>
              <?php } ?>
              <th class="dataTableHeadingContent text-right" style="width:10%;"><?= TABLE_HEADING_ACTION ?>&nbsp;</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($modulesController->groupedRows()[$groupKey] as $row) { ?>
              <tr<?= $row['selected'] ? ' id="defaultSelected" class="dataTableRowSelected"' : ' class="dataTableRow"' ?><?= $row['rowLink'] !== null ? ' style="cursor:pointer;" onclick="document.location.href=\'' . $row['rowLink'] . '\'"' : '' ?>>
                <td class="dataTableContent"><?= $row['title'] ?></td>
                <td class="dataTableContent"><?= $row['code'] ?></td>
                <td class="dataTableContent text-right"><?= $row['sortOrder'] . $row['statusIcon'] ?></td>
                <?php if ($modulesController->isPaymentSet()) { ?>
                  <td class="dataTableContent text-center"><?= $row['orderStatus'] ?></td>
                <?php } ?>
                <td class="dataTableContent text-right">
                  <?php if ($row['selected']) { ?>
                    <?= zen_icon('caret-right', '', '2x', true) ?>
                  <?php } else { ?>
                    <a href="<?= $row['infoLink'] ?>" data-toggle="tooltip" title="<?= IMAGE_ICON_INFO ?>" role="button"><?= zen_icon('circle-info', '', '2x', true) ?></a>
                  <?php } ?>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      <?php } ?>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 configurationColumnRight">
      <?php
      $boxHeader = $modulesController->getBoxHeader();
      $boxContent = $modulesController->getBoxContent();
      require $infoBoxTemplatePath;
      ?>
    </div>
  </div>

  <div class="row"><?= TEXT_MODULE_DIRECTORY . ' ' . $modulesController->moduleDirectory() ?></div>

  <?php
  $countHtml = '';
  $linksHtml = '';
  require $footerTemplatePath;
  ?>
</div>

<?php if ($modulesController->helpBody() !== '') { ?>
  <div id="helpModal" class="modal fade">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><?= $modulesController->helpTitle() ?></h4>
        </div>
        <div class="modal-body">
          <?= $modulesController->helpBody() ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
<?php } ?>
