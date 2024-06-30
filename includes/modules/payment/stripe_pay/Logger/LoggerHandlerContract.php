<?php

interface LoggerHandlerContract
{
    public  function setup();
    public  function log($severity, $message, $context = []);
}
