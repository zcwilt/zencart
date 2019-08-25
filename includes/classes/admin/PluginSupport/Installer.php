<?php

namespace Zencart\PluginSupport;

class Installer
{

    protected $errors = [];

    public function __construct($patchInstaller, $migrationInstaller)
    {
        $this->patchInstaller = $patchInstaller;
        $this->migrationInstaller = $migrationInstaller;
    }

    public function executeInstallers($pluginDir)
    {
        $pluginDir . '/Installer/sqlinstall/install.sql';
        $this->executePatchInstaller($pluginDir);
        $this->errors = $this->patchInstaller->getErrors();
        if ($this->hasErrors()) {
            return;
        }
        $this->executeMigrationInstaller($pluginDir);
//        die('heree');
    }

    protected function executePatchInstaller($pluginDir)
    {
        if (!file_exists($pluginDir . '/Installer/sqlinstall/install.sql')) {
            return;
        }
        $lines = file($pluginDir . '/Installer/sqlinstall/install.sql');
        $paramLines = $this->patchInstaller->parse($lines);
        if ($this->hasErrors()) {
            return;
        }
        $this->patchInstaller->executePatchSql($paramLines);
    }

    protected function executeMigrationInstaller($pluginDir)
    {
        if (!file_exists($pluginDir . '/Installer/sqlinstall/PluginMigrationInstall.php')) {
            return;
        }
        require_once $pluginDir . '/Installer/sqlinstall/PluginMigrationInstall.php';
        $migrationInstaller = new \ScriptedInstaller;
        $result = $migrationInstaller->execute();
        if ($this->hasErrors()) {
            return;
        }
    }

    public function hasErrors()
    {
        return(count($this->errors) > 0);
    }

    public function getErrors()
    {
        return $this->errors;
    }
}