<?php
namespace Zencart\Logger;

interface LoggerHandlerContract
{
    public  function setup();
    public  function log($severity, $message, $context = []);
}
