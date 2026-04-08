<?php
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 *
 * Expected variables:
 * - string $pageHeading
 * - string $startDate
 * - string $endDate
 * - string $referralCode
 * - array $referralOptions
 * - array $orderEntries
 */
?>
<div class="container-fluid">
  <h1 class="pageHeading"><?= $pageHeading ?></h1>

  <?= zen_draw_form('new_date', FILENAME_STATS_CUSTOMERS_REFERRALS, '', 'get', 'class="form-horizontal"') ?>
  <?= zen_hide_session_id() ?>
  <?= zen_draw_hidden_field('action', 'new_date') ?>
  <?= zen_draw_hidden_field('start_date', $startDate) ?>
  <?= zen_draw_hidden_field('end_date', $endDate) ?>
  <div class="form-group">
      <?= zen_draw_label(TEXT_INFO_SELECT_REFERRAL, 'referral_code', 'class="control-label col-sm-3"') ?>
    <div class="col-sm-9 col-md-6">
        <?= zen_draw_pull_down_menu('referral_code', $referralOptions, $referralCode, 'onChange="this.form.submit();" class="form-control"') ?>
    </div>
  </div>
  </form>

  <?= zen_draw_form('search', FILENAME_STATS_CUSTOMERS_REFERRALS, '', 'get', 'class="form-horizontal"') ?>
  <?= zen_draw_hidden_field('referral_code', $referralCode) ?>
  <?= zen_hide_session_id() ?>
  <div class="form-group">
      <?= zen_draw_label(TEXT_INFO_START_DATE, 'start_date', 'class="control-label col-sm-3"') ?>
    <div class="col-sm-9 col-md-6">
        <?= zen_draw_input_field('start_date', $startDate, 'class="form-control"') ?>
    </div>
  </div>
  <div class="form-group">
      <?= zen_draw_label(TEXT_INFO_END_DATE, 'end_date', 'class="control-label col-sm-3"') ?>
    <div class="col-sm-9 col-md-6">
        <?= zen_draw_input_field('end_date', $endDate, 'class="form-control"') ?>
    </div>
  </div>
  <div class="col-sm-12 text-right"><button type="submit" class="btn btn-primary"><?= IMAGE_DISPLAY ?></button></div>
  </form>

  <table class="table">
      <?php foreach ($orderEntries as $entry) { ?>
      <tr>
        <td colspan="4"><?= zen_draw_separator('pixel_trans.gif', '1', '10') ?></td>
      </tr>
      <tr>
        <td class="main"><?= $entry['datePurchasedLong'] ?></td>
        <td class="main"><?= TEXT_ORDER_NUMBER ?> <?= $entry['ordersId'] ?></td>
        <td class="main"><?= $entry['couponCode'] !== '' ? TEXT_COUPON_ID . ' ' . $entry['couponCode'] : '' ?></td>
        <td class="main"><a href="<?= $entry['detailsLink'] ?>" class="btn btn-primary" role="button"><?= IMAGE_DETAILS ?></a></td>
      </tr>

      <?php foreach ($entry['totals'] as $total) { ?>
        <tr>
          <td colspan="2" class="<?= $total['class'] ?>-Text"><?= $total['title'] ?></td>
          <td colspan="2" class="<?= $total['class'] ?>-Amount text-right"><?= $total['text'] ?></td>
        </tr>
      <?php } ?>
    <?php } ?>
  </table>
</div>
