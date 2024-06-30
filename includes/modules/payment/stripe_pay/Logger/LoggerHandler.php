<?php

class LoggerHandler
{

    protected array $options = [];

    public function __construct($commonOptions = [])
    {
        $this->options = $commonOptions;
    }
}
