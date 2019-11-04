<?php

namespace Zencart\PluginSupport;

class ScriptedInstaller
{
    public function __construct($dbConn)
    {
        $this->dbConn = $dbConn;
        $this->logErrors = [];
        $this->messageErrors = [];
    }

    public function execute()
    {
        $installed = $this->executeInstall();
        return $installed;
    }

    protected function executeInstall()
    {
        return true;
    }

    protected function executeInstallSql($sql)
    {
        $this->dbConn->dieOnErrors = false;
        $this->dbConn->Execute($sql);
        if ($this->dbConn->error_number !== 0) {
            $this->logErrors[] = $this->dbConn->error_text;
            $this->messageErrors['SQLFAILURE'] = PLUGIN_INSTALL_SQL_FAILURE;
        }
        $this->dbConn->dieOnErrors = true;
    }

    public function getErrors()
    {
        return $this->logErrors;
    }

    public function hasErrors()
    {
        return (count($this->logErrors) > 0);
    }


}