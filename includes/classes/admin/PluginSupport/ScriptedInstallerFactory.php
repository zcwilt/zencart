<?php


namespace Zencart\PluginSupport;


class ScriptedInstallerFactory
{

    public function __construct($dbConn)
    {
        $this->dbConn = $dbConn;
    }
    public function make($pluginDir)
    {
        require_once $pluginDir . '/Installer/sqlinstall/ScriptedInstaller.php';
        $scriptedInstaller = new \ScriptedInstaller($this->dbConn);
        return $scriptedInstaller;
    }
}