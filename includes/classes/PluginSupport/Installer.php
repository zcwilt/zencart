<?php
/**
 * @copyright Copyright 2003-2023 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Marco Ponchia 2022 Dec 16 Modified in v1.5.8a $
 */

namespace Zencart\PluginSupport;

class Installer
{

    public function __construct(protected SqlPatchInstaller $patchInstaller, protected ScriptedInstallerFactory $scriptedInstallerFactory, protected PluginErrorContainer $errorContainer)
    {
    }

    public function executeInstallers($pluginDir): void
    {
        $this->executePatchInstaller($pluginDir);
        if ($this->errorContainer->hasErrors()) {
            return;
        }
        $this->executeScriptedInstaller($pluginDir);
    }

    public function executeUninstallers($pluginDir): void
    {
        $this->executePatchUninstaller($pluginDir);
        if ($this->errorContainer->hasErrors()) {
            return;
        }
        $this->executeScriptedUninstaller($pluginDir);
    }

    public function executeUpgraders($pluginDir, $oldVersion): void
    {
        $this->executeScriptedUpgrader($pluginDir, $oldVersion);
    }

    protected function executePatchInstaller($pluginDir): void
    {
        $patchFile = 'install.sql';
        $this->executePatchFile($pluginDir, $patchFile);
    }

    protected function executePatchUninstaller($pluginDir): void
    {
        $patchFile = 'uninstall.sql';
        $this->executePatchFile($pluginDir, $patchFile);
    }

    protected function executePatchFile($pluginDir, $patchFile): void
    {
        if (!file_exists($pluginDir . '/Installer/' . $patchFile)) {
            return;
        }
        $lines = file($pluginDir . '/Installer/' . $patchFile);
        $paramLines = $this->patchInstaller->parse($lines);
        if ($this->errorContainer->hasErrors()) {
            return;
        }
        $this->patchInstaller->executePatchSql($paramLines);
    }

    protected function executeScriptedInstaller($pluginDir): void
    {
        if (!file_exists($pluginDir . '/Installer/ScriptedInstaller.php')) {
            return;
        }
        $scriptedInstaller = $this->scriptedInstallerFactory->make($pluginDir);
        $scriptedInstaller->doInstall();
    }

    protected function executeScriptedUninstaller($pluginDir): void
    {
        if (!file_exists($pluginDir . '/Installer/ScriptedInstaller.php')) {
            return;
        }
        $scriptedInstaller = $this->scriptedInstallerFactory->make($pluginDir);
        $scriptedInstaller->doUninstall();
    }

    protected function executeScriptedUpgrader($pluginDir, $oldVersion): void
    {
        if (!file_exists($pluginDir . '/Installer/ScriptedInstaller.php')) {
            return;
        }
        $scriptedInstaller = $this->scriptedInstallerFactory->make($pluginDir);
        $scriptedInstaller->doUpgrade($oldVersion);
    }

    public function getErrorContainer(): PluginErrorContainer
    {
        return $this->errorContainer;
    }
}
