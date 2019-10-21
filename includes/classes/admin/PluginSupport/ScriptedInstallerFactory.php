<?php


namespace Zencart\PluginSupport;


class ScriptedInstallerFactory
{

    public function make($pluginDir, $dbConn)
    {
        require_once $pluginDir . '/Installer/sqlinstall/ScriptedInstaller.php';
        $scriptedInstaller = new \ScriptedInstaller($this->dbConn);
        return $scriptedInstaller;
    }
}