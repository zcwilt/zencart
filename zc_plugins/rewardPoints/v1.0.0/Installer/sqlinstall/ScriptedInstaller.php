<?php

use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    protected function executeInstall()
    {
        $sql = "CcREATE TABLE IF NOT EXISTS reward_master (
                               rewards_products_id INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                               scope INT( 1 ) NOT NULL DEFAULT '0',
                               scope_id INT( 11 ) NOT NULL DEFAULT '0',
                               point_ratio DOUBLE( 15, 4 ) NOT NULL DEFAULT '1',
                               bonus_points DOUBLE( 15, 4 ) NULL,
                               redeem_ratio DOUBLE( 15, 4 ) NULL,
                               redeem_points DOUBLE( 15, 4 ) NULL,
                               UNIQUE unique_id ( scope , scope_id ));";

        $installed = $this->executeInstallSql($sql);
        return $installed;
    }


}
