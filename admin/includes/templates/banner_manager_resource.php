<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *
 * @var Zencart\AdminUi\Resources\BannerManager\BannerManagerController $bannerManagerController
 * @var string $pageHeading
 */
$action = $bannerManagerController->getAction();
$selectedBanner = $bannerManagerController->selectedBanner();
$formValues = $bannerManagerController->formValues();
$dateCheckerPath = DIR_WS_INCLUDES . 'javascript/dateChecker.php';
if (!is_file($dateCheckerPath) && defined('DIR_FS_ADMIN')) {
    $dateCheckerPath = DIR_FS_ADMIN . 'includes/javascript/dateChecker.php';
}
?>
<div class="container-fluid">
  <h1><?= $pageHeading ?></h1>

  <?php if ($bannerManagerController->shouldShowLegend()) { ?>
    <div class="row">
      <table class="table-condensed">
        <tr>
          <td class="text-right"><?= TEXT_LEGEND ?></td>
          <td class="text-center"><?= TABLE_HEADING_STATUS ?></td>
          <td class="text-center"><?= TEXT_LEGEND_BANNER_OPEN_NEW_WINDOWS ?></td>
        </tr>
        <tr>
          <td class="text-right"></td>
          <td class="text-center">
            <?= zen_icon('enabled', IMAGE_ICON_STATUS_ON, '2x', hidden: true) ?>
            &nbsp;
            <?= zen_icon('disabled', IMAGE_ICON_STATUS_OFF, '2x', hidden: true) ?>
          </td>
          <td class="text-center actions"><div class="btn-group">
            <?= zen_icon('new-window', IMAGE_ICON_BANNER_OPEN_NEW_WINDOWS_ON, '2x', hidden: true) ?>
            &nbsp;
            <?= zen_icon('new-window-off', IMAGE_ICON_BANNER_OPEN_NEW_WINDOWS_OFF, hidden: true) ?>
          </div></td>
        </tr>
      </table>
    </div>
  <?php } ?>

  <?php if ($bannerManagerController->isFormMode() && $formValues !== null) { ?>
    <div class="row">
      <?= zen_draw_form('new_banner', FILENAME_BANNER_MANAGER, $bannerManagerController->formPostParameters(), 'post', 'enctype="multipart/form-data" class="form-horizontal"') ?>
      <?php if ($bannerManagerController->formAction() === 'upd') { ?>
        <?= zen_draw_hidden_field('banners_id', $bannerManagerController->currentBannerId()) ?>
      <?php } ?>

      <div class="form-group">
        <div class="col-sm-3">
          <p class="control-label"><?= TEXT_BANNERS_STATUS ?></p>
        </div>
        <div class="col-sm-9 col-md-6">
          <label class="radio-inline"><?= zen_draw_radio_field('status', '1', (int) $formValues->status === 1) . TEXT_BANNERS_ACTIVE ?></label>
          <label class="radio-inline"><?= zen_draw_radio_field('status', '0', (int) $formValues->status === 0) . TEXT_BANNERS_NOT_ACTIVE ?></label>
          <span class="help-block"><?= TEXT_INFO_BANNER_STATUS ?></span>
        </div>
      </div>

      <div class="form-group">
        <div class="col-sm-3">
          <p class="control-label"><?= TEXT_BANNERS_OPEN_NEW_WINDOWS ?></p>
        </div>
        <div class="col-sm-9 col-md-6">
          <label class="radio-inline"><?= zen_draw_radio_field('banners_open_new_windows', '1', (int) $formValues->banners_open_new_windows === 1) . TEXT_YES ?></label>
          <label class="radio-inline"><?= zen_draw_radio_field('banners_open_new_windows', '0', (int) $formValues->banners_open_new_windows === 0) . TEXT_NO ?></label><br>
          <span class="help-block"><?= TEXT_INFO_BANNER_OPEN_NEW_WINDOWS ?></span>
        </div>
      </div>

      <div class="form-group">
        <?= zen_draw_label(TEXT_BANNERS_TITLE, 'banners_title', 'class="col-sm-3 control-label"') ?>
        <div class="col-sm-9 col-md-6">
          <?= zen_draw_input_field('banners_title', htmlspecialchars((string) $formValues->banners_title, ENT_COMPAT, CHARSET), zen_set_field_length(TABLE_BANNERS, 'banners_title') . ' class="form-control" id="banners_title"', true) ?>
        </div>
      </div>

      <div class="form-group">
        <?= zen_draw_label(TEXT_BANNERS_URL, 'banners_url', 'class="col-sm-3 control-label"') ?>
        <div class="col-sm-9 col-md-6">
          <?= zen_draw_input_field('banners_url', (string) $formValues->banners_url, zen_set_field_length(TABLE_BANNERS, 'banners_url') . ' class="form-control" id="banners_url"') ?>
        </div>
      </div>

      <div class="form-group">
        <?= zen_draw_label(TEXT_BANNERS_GROUP, 'banners_group', 'class="col-sm-3 control-label"') ?>
        <div class="col-sm-4 col-md-3">
          <?= zen_draw_pull_down_menu('banners_group', $bannerManagerController->groupOptions(), (string) $formValues->banners_group, 'class="form-control" id="banners_group"') ?>
          <p><?= TEXT_BANNERS_NEW_GROUP ?></p><?= zen_draw_input_field('new_banners_group', '', 'class="form-control" id="new_banners_group"', count($bannerManagerController->groupOptions()) === 0) ?>
        </div>
      </div>

      <div style="border: 1px solid grey; padding: 10px;">
        <div class="form-group row mt-2">
          <div class="col-sm-offset-3 col-sm-9"><?= TEXT_BANNERS_IMAGE_LOCAL ?></div>
          <?= zen_draw_label(TEXT_BANNERS_CURRENT_IMAGE, 'banners_image_local', 'class="col-sm-3 control-label"') ?>
          <div class="col-sm-9 col-md-6">
            <div class="input-group">
              <span class="input-group-addon"><?= $bannerManagerController->abbreviatedImagesDirectory() ?></span>
              <?= zen_draw_input_field('banners_image_local', (string) ($formValues->banners_image ?? ''), zen_set_field_length(TABLE_BANNERS, 'banners_image') . 'id="banners_image_local" class="form-control"') ?>
            </div>
          </div>
        </div>

        <div class="form-group row mt-2">
          <?= zen_draw_label(TEXT_BANNERS_IMAGE, 'banners_image', 'class="col-sm-3 control-label"') ?>
          <div class="col-sm-9 col-md-6">
            <?= zen_draw_file_field('banners_image', '', 'class="form-control" id="banners_image"') ?>
          </div>
        </div>

        <div class="form-group row mt-2">
          <?= zen_draw_label(TEXT_BANNERS_IMAGE_TARGET, 'banners_image_target', 'class="col-sm-3 control-label"') ?>
          <div class="col-sm-9 col-md-6">
            <div class="input-group">
              <span class="input-group-addon"><?= $bannerManagerController->abbreviatedImagesDirectory() ?></span>
              <?= zen_draw_input_field('banners_image_target', 'banners/', 'class="form-control" id="banners_image_target"') ?>
            </div>
            <div><?= TEXT_BANNER_IMAGE_TARGET_INFO ?></div>
          </div>
        </div>
      </div>

      <div class="form-group mt-4">
        <?= zen_draw_label(TEXT_BANNERS_HTML_TEXT, 'banners_html_text', 'class="col-sm-3 control-label"') ?>
        <div class="col-sm-9 col-md-6">
          <?= '<p>' . TEXT_BANNERS_HTML_TEXT_INFO . '</p>' . zen_draw_textarea_field('banners_html_text', 'soft', '80', '10', htmlspecialchars((string) $formValues->banners_html_text, ENT_COMPAT, CHARSET), 'class="editorHook form-control" id="banners_html_text"') ?>
        </div>
      </div>

      <div class="form-group">
        <?= zen_draw_label(TEXT_BANNERS_ALL_SORT_ORDER, 'banners_sort_order', 'class="col-sm-3 control-label"') ?>
        <div class="col-sm-9 col-md-6">
          <?= TEXT_BANNERS_ALL_SORT_ORDER_INFO . '<br>' . zen_draw_input_field('banners_sort_order', (string) $formValues->banners_sort_order, zen_set_field_length(TABLE_BANNERS, 'banners_sort_order') . ' class="form-control" id="banners_sort_order"') ?>
        </div>
      </div>

      <div class="form-group">
        <?= zen_draw_label(TEXT_BANNERS_SCHEDULED_AT, 'date_scheduled', 'class="col-sm-3 control-label"') ?>
        <div class="col-sm-9 col-md-6">
          <div class="date input-group" id="datepicker_date_scheduled">
            <span class="input-group-addon datepicker_icon"><?= zen_icon('calendar-days', size: 'lg') ?></span>
            <?= zen_draw_input_field('date_scheduled', (string) $formValues->date_scheduled, 'class="form-control" id="date_scheduled" autocomplete="off"') ?>
          </div>
          <span class="help-block errorText">(<?= zen_datepicker_format_full() ?>) <span class="date-check-error"><?= ERROR_INVALID_SCHEDULED_DATE ?></span></span>
        </div>
      </div>

      <div class="form-group">
        <?= zen_draw_label(TEXT_BANNERS_EXPIRES_ON, 'expires_date', 'class="col-sm-3 control-label"') ?>
        <div class="col-sm-9 col-md-6">
          <div class="date input-group" id="datepicker_expires_date">
            <span class="input-group-addon datepicker_icon"><?= zen_icon('calendar-days', size: 'lg') ?></span>
            <?= zen_draw_input_field('expires_date', (string) $formValues->expires_date, 'class="form-control" id="expires_date" autocomplete="off"') ?>
          </div>
          <span class="help-block errorText">(<?= zen_datepicker_format_full() ?>) <span class="date-check-error"><?= ERROR_INVALID_EXPIRES_DATE ?></span></span>
          <?= TEXT_BANNERS_OR_AT ?>
        </div>
      </div>

      <?php require $dateCheckerPath; ?>

      <div class="form-group">
        <?= zen_draw_label(TEXT_BANNERS_IMPRESSIONS, 'expires_impressions', 'class="control-label col-sm-3"') ?>
        <div class="col-sm-9 col-md-6">
          <?= zen_draw_input_field('expires_impressions', (string) $formValues->expires_impressions, 'maxlength="7" size="7" class="form-control" id="expires_impressions"') ?>
        </div>
      </div>

      <div class="form-group">
        <div class="col-sm-12 text-right">
          <button type="submit" class="btn btn-primary"><?= ($bannerManagerController->formAction() === 'add') ? IMAGE_INSERT : IMAGE_UPDATE ?></button>
          <a href="<?= $bannerManagerController->cancelFormUrl() ?>" class="btn btn-default" role="button"><?= IMAGE_CANCEL ?></a>
        </div>
      </div>

      <div class="form-group">
        <div class="col-sm-offset-3 col-sm-9 col-md-6">
          <?= TEXT_BANNERS_BANNER_NOTE . '<br>' . TEXT_BANNERS_INSERT_NOTE . '<br>' . TEXT_BANNERS_EXPIRY_NOTE . '<br>' . TEXT_BANNERS_SCHEDULE_NOTE ?>
        </div>
      </div>
      </form>
    </div>
  <?php } else { ?>
    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-9 col-lg-9 configurationColumnLeft">
        <table class="table table-hover table-striped">
          <thead>
            <tr class="dataTableHeadingRow">
              <th class="dataTableHeadingContent"><?= TABLE_HEADING_BANNERS ?></th>
              <th class="dataTableHeadingContent text-right"><?= TABLE_HEADING_GROUPS ?></th>
              <th class="dataTableHeadingContent"><?= TABLE_HEADING_POSITIONS ?></th>
              <th class="dataTableHeadingContent text-center"><?= TABLE_HEADING_STATISTICS ?></th>
              <th class="dataTableHeadingContent text-center"><?= TABLE_HEADING_STATUS ?></th>
              <th class="dataTableHeadingContent text-center"><?= TABLE_HEADING_BANNER_OPEN_NEW_WINDOWS ?></th>
              <th class="dataTableHeadingContent text-right"><?= TABLE_HEADING_BANNER_SORT_ORDER ?></th>
              <th class="dataTableHeadingContent text-right"><?= TABLE_HEADING_ACTION ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bannerManagerController->listingRows() as $row) { ?>
              <tr<?= $row['selected'] ? ' id="defaultSelected" class="dataTableRowSelected"' : ' class="dataTableRow"' ?> onclick="document.location.href = '<?= $row['rowLink'] ?>'">
                <td class="dataTableContent"><a href="javascript:popupImageWindow('<?= $row['popupLink'] ?>')" title="View Banner"><i class="fa-regular fa-window-restore fa-lg txt-black" aria-hidden="true"></i></a>&nbsp;<?= $row['title'] ?></td>
                <td class="dataTableContent text-right"><?= $row['group'] ?></td>
                <td class="dataTableContent"><div class="text-nowrap"><?= implode('<br>', $row['positions']) ?></div></td>
                <td class="dataTableContent text-center"><?= $row['statistics'] ?></td>
                <td class="dataTableContent text-center">
                  <a href="<?= $row['statusLink'] ?>" data-toggle="tooltip" title="<?= $row['statusTitle'] ?>">
                    <?= zen_icon($row['status'] === 1 ? 'enabled' : 'disabled', '', '2x', false, true) ?>
                  </a>
                </td>
                <td class="dataTableContent text-center">
                  <a href="<?= $row['openWindowLink'] ?>"<?= $row['openNewWindow'] === 1 ? ' data-toggle="tooltip" title="' . IMAGE_ICON_BANNER_OPEN_NEW_WINDOWS_ON . '"' : '' ?>>
                    <?= zen_icon($row['openNewWindow'] === 1 ? 'new-window' : 'new-window-off', '', '2x', false, true) ?>
                  </a>
                </td>
                <td class="dataTableContent text-right"><?= $row['sortOrder'] ?></td>
                <td class="dataTableContent text-right">
                  <a href="<?= $row['statisticsLink'] ?>"><?= zen_icon('line-chart', '', 'lg', false, true) ?></a>
                  <?php if ($row['selected']) { ?>
                    <?= zen_icon('caret-right', '', '2x', true) ?>
                  <?php } else { ?>
                    <a href="<?= $row['infoLink'] ?>"><?= zen_icon('circle-info', '', '2x', true) ?></a>
                  <?php } ?>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>

      <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 configurationColumnRight">
        <?php
        $boxHeader = $bannerManagerController->getBoxHeader();
        $boxContent = $bannerManagerController->getBoxContent();
        require DIR_FS_ADMIN . 'includes/templates/partials/resource_infobox.php';
        ?>
      </div>
    </div>

    <?php
    $countHtml = $bannerManagerController->splitResults()?->display_count($bannerManagerController->queryNumRows(), MAX_DISPLAY_SEARCH_RESULTS, $bannerManagerController->currentPage(), TEXT_DISPLAY_NUMBER_OF_BANNERS) ?? '';
    $linksHtml = $bannerManagerController->splitResults()?->display_links($bannerManagerController->queryNumRows(), MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $bannerManagerController->currentPage(), $bannerManagerController->listingPaginationParameters()) ?? '';
    $actionHtml = '<a href="' . $bannerManagerController->newBannerUrl() . '" class="btn btn-primary" role="button">' . IMAGE_NEW_BANNER . '</a>';
    require DIR_FS_ADMIN . 'includes/templates/partials/resource_footer.php';
    ?>
  <?php } ?>
</div>
