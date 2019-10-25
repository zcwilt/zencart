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
        }
        $this->dbConn->dieOnErrors = true;
        return true;
    }

    protected function processError()
    {

    }
}