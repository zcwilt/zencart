<?php

namespace Zencart\Logger\Handlers;

use Zencart\Logger\LoggerHandler;
use Zencart\Logger\LoggerHandlerContract;
use Monolog\Logger;

class FileLoggerHandler extends LoggerHandler implements LoggerHandlerContract
{

    public function setup(): void
    {
        $logger = new Logger($this->options['channel'].'-logger');
        $debugLogFile = $this->getDebugLogFile();
        $logger->pushHandler(new StreamHandler($debugLogFile));
    }

    protected function getDebugLogFile(): string
    {

    }
}
