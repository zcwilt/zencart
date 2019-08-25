<?php

namespace Zencart\PluginSupport;

use Zencart\PluginSupport\Installer;
use Zencart\AdminExceptions\PluginInstallerException;

class InstallerFactory
{

    public function __construct($dbConn, $sqlInstaller)
    {
        $this->dbConn = $dbConn;
        $this->sqlInstaller = $sqlInstaller;
    }

    public function make($plugin, $version)
    {
        $pluginDir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin . '/';
        $versionDir = $pluginDir . $version . '/';

        if (!is_dir($pluginDir)) {
            throw new PluginInstallerException('NO PLUGIN DIRECTORY');
        }
        if (!is_dir($versionDir)) {
            throw new PluginInstallerException('NO PLUGIN VERSION DIRECTORY');
        }
        if (!file_exists($versionDir  . 'manifest.php')) {
            throw new PluginInstallerException('NO VERSION MANIFEST');
        }
        if (!file_exists($versionDir . 'installer/' . 'Installer.php')) {
            $installer = new BasePluginInstaller($this->dbConn, $this->configInstaller, $this->sqlInstaller);
            return $installer;
        }
        require_once($versionDir . 'Installer');
        $installer = new Installer($this->dbConn, $this->configInstaller, $this->sqlInstaller);
        return $installer;
    }
}