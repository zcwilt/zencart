<?php
/**
 * Expected variables:
 * - array $boxHeader
 * - array $boxContent
 */
$infoboxBeforeHtml = '';
$infoboxAfterHtml = '';

global $zco_notifier;
if (isset($zco_notifier) && is_object($zco_notifier) && method_exists($zco_notifier, 'notify')) {
    $zco_notifier->notify(
        'NOTIFY_ADMIN_RESOURCE_INFOBOX_START',
        [],
        $infoboxBeforeHtml,
        $infoboxAfterHtml,
        $boxHeader,
        $boxContent
    );
}

if (!empty($boxHeader) && !empty($boxContent)) {
    echo $infoboxBeforeHtml;
    $box = new box();
    echo $box->infoBox($boxHeader, $boxContent);
    echo $infoboxAfterHtml;
}
