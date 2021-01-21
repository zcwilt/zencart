<?php

namespace Zencart\Request;

use Zencart\Traits\Singleton;

class Request
{
    use Singleton;

    public $requestBag;

    public static function capture()
    {
        $instance = self::getInstance();
        $instance->requestBag = $_REQUEST;
        return $instance;
    }

    public function input($name, $default = null)
    {
        $retVal = $this->requestBag[$name] ?? $default;
        return $retVal;
    }
}
