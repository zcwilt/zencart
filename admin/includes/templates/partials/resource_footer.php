<?php
/**
 * Expected variables:
 * - string $countHtml
 * - string $linksHtml
 *
 * Optional variables:
 * - string|null $actionHtml
 * - string|null $primaryActionHref
 * - string|null $primaryActionLabel
 */
$actionHtml = $actionHtml ?? null;
$primaryActionHref = $primaryActionHref ?? null;
$primaryActionLabel = $primaryActionLabel ?? null;
$footerBeforeHtml = '';
$footerAfterHtml = '';

global $zco_notifier;
if (isset($zco_notifier) && is_object($zco_notifier) && method_exists($zco_notifier, 'notify')) {
    $zco_notifier->notify(
        'NOTIFY_ADMIN_RESOURCE_FOOTER_START',
        [],
        $footerBeforeHtml,
        $footerAfterHtml,
        $countHtml,
        $linksHtml,
        $actionHtml,
        $primaryActionHref,
        $primaryActionLabel
    );
}

if (
    $countHtml !== ''
    || $linksHtml !== ''
    || $footerBeforeHtml !== ''
    || $footerAfterHtml !== ''
    || $actionHtml !== null
    || ($primaryActionHref !== null && $primaryActionLabel !== null)
) {
    ?>
    <?= $footerBeforeHtml ?>
    <div class="row">
        <table class="table">
            <?php if ($countHtml !== '' || $linksHtml !== '') { ?>
                <tr>
                    <td><?= $countHtml ?></td>
                    <td class="text-right"><?= $linksHtml ?></td>
                </tr>
            <?php } ?>
            <?php if ($actionHtml !== null && $actionHtml !== '') { ?>
                <tr>
                    <td colspan="2" class="text-right"><?= $actionHtml ?></td>
                </tr>
            <?php } elseif ($primaryActionHref !== null && $primaryActionLabel !== null) { ?>
                <tr>
                    <td colspan="2" class="text-right">
                        <a href="<?= $primaryActionHref ?>" class="btn btn-primary" role="button"><?= $primaryActionLabel ?></a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
    <?= $footerAfterHtml ?>
<?php } ?>
