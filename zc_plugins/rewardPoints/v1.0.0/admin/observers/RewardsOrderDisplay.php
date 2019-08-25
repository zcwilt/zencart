<?php


class RewardsOrderDisplay extends base
{
    public function __construct(notifier $zco_notifier = null) {
        if (!$zco_notifier) $zco_notifier = new notifier;
        $this->notifier = $zco_notifier;
        $this->notifier->attach($this, array('NOTIFY_ADMIN_ORDERS_LIST_EXTRA_COLUMN_HEADING'));
    }

    public function updateNotifyAdminOrdersListExtraColumnHeading(&$class, $eventID, $p1, &$extra_headings)
    {
        //$extra_headings[] = ['align' => 'center', 'text' => TABLE_HEADING_REWARD_POINTS];
    }
}