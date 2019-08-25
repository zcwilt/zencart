<?php

namespace Zencart\PluginSupport;

class ScriptedInstaller
{
    public function __construct()
    {
        $this->errors = [];
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

    protected function processError()
    {

    }
}