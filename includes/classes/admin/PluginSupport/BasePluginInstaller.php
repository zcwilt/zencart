<?php

namespace Zencart\PluginSupport;

class BasePluginInstaller
{

    protected $pluginDir;

    public function __construct($dbConn, $sqlInstaller)
    {
        $this->dbConn = $dbConn;
        $this->sqlInstaller = $sqlInstaller;
    }

    public function processInstall($pluginKey, $version)
    {
        $this->pluginDir = DIR_FS_CATALOG . 'zc_plugins/' . $pluginKey . '/' . $version;
        $this->loadInstallerLanguageFile('main.php', $this->pluginDir);
        $this->sqlInstaller->executeInstallers($this->pluginDir);
        if ($this->sqlInstaller->hasErrors()) {
            $this->processSqlErrors();
            return false;
        }
        //$this->setPluginVersionStatus($pluginKey, $version, 1);
        return true;
    }

    public function processUninstall($pluginKey, $version)
    {
        $this->pluginDir = DIR_FS_CATALOG . 'zc_plugins/' . $pluginKey . '/' . $version;
        $this->loadInstallerLanguageFile('main.php', $this->pluginDir);
//        $this->sqlInstaller->executeInstallers($this->pluginDir);
//        print_r($this->sqlInstaller->getErrors());

        $this->setPluginVersionStatus($pluginKey, '', 0);
    }

    protected function setPluginVersionStatus($pluginKey, $version, $status)
    {
        $sql = "UPDATE " . TABLE_PLUGIN_CONTROL . " SET status = :status:, version = :version: WHERE unique_key = :uniqueKey:";
        $sql = $this->dbConn->bindVars($sql, ':status:', $status, 'integer');
        $sql = $this->dbConn->bindVars($sql, ':uniqueKey:', $pluginKey, 'string');
        $sql = $this->dbConn->bindVars($sql, ':version:', $version, 'string');
        $this->dbConn->execute($sql);
    }

    protected function loadInstallerLanguageFile($file)
    {
        $lng = $_SESSION['language'];
        $filename = $this->pluginDir . '/installer/langauages/' . $lng . '/' . $file;
        if (file_exists($filename)) {
            require_once($filename);
        }
    }

    protected function processSqlErrors()
    {

    }
}