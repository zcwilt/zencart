<?php

namespace Zencart\PluginSupport;

class Installer
{

    protected $errors = [];

    public function __construct($patchInstaller, $scriptedInstallerFactory)
    {
        $this->patchInstaller = $patchInstaller;
        $this->scriptedInstallerFactory = $scriptedInstallerFactory;
    }

    public function executeInstallers($pluginDir)
    {
        $pluginDir . '/Installer/sqlinstall/install.sql';
        $this->executePatchInstaller($pluginDir);
        $this->errors = $this->patchInstaller->getErrors();
        if ($this->hasErrors()) {
            return;
        }
        $scriptedInstaller = $this->executeScriptedInstaller($pluginDir);
        $this->errors = $scriptedInstaller->getErrors();
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

    protected function executeScriptedInstaller($pluginDir)
    {
        if (!file_exists($pluginDir . '/Installer/sqlinstall/ScriptedInstaller.php')) {
             return;
        }
        $scriptedInstaller = $this->scriptedInstallerFactory->make($pluginDir);
        $scriptedInstaller->execute();
        return $scriptedInstaller;
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