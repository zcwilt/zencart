<?php
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2025 Sep 18 Modified in v2.2.0 $
 */

namespace Zencart\Request;

use Zencart\Traits\Singleton;

/**
 * @since ZC v1.5.8
 */
class Request
{
    use Singleton;

    protected array $paramBag = [];
    protected array $queryBag = [];
    protected array $postBag = [];
    protected array $cookieBag = [];
    protected array $serverBag = [];

    /**
     * @return mixed|Request
     * @since ZC v1.5.8
     */
    static function capture()
    {
        $self = self::getInstance();
        $self->queryBag = $_GET;
        $self->postBag = $_POST;
        $self->cookieBag = $_COOKIE;
        $self->serverBag = $_SERVER;
        $self->paramBag = $_REQUEST;
        return self::getInstance();
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     * @since ZC v1.5.8
     */
    public function input($key, $default = null)
    {
        return (isset($this->paramBag[$key]) ? $this->paramBag[$key] : $default);
    }

    /**
     * @param $key
     * @return bool
     * @since ZC v1.5.8
     */
    public function has($key)
    {
        return (isset($this->paramBag[$key]));
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     * @since ZC v3.0.0
     */
    public function query($key, $default = null)
    {
        return (isset($this->queryBag[$key]) ? $this->queryBag[$key] : $default);
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     * @since ZC v3.0.0
     */
    public function post($key, $default = null)
    {
        return (isset($this->postBag[$key]) ? $this->postBag[$key] : $default);
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     * @since ZC v3.0.0
     */
    public function cookie($key, $default = null)
    {
        return (isset($this->cookieBag[$key]) ? $this->cookieBag[$key] : $default);
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     * @since ZC v3.0.0
     */
    public function server($key, $default = null)
    {
        return (isset($this->serverBag[$key]) ? $this->serverBag[$key] : $default);
    }

    /**
     * @param $key
     * @param int $default
     * @return int
     * @since ZC v3.0.0
     */
    public function integer($key, int $default = 0): int
    {
        return $this->toInteger($this->input($key, $default), $default);
    }

    /**
     * @param $key
     * @param int $default
     * @return int
     * @since ZC v3.0.0
     */
    public function postInteger($key, int $default = 0): int
    {
        return $this->toInteger($this->post($key, $default), $default);
    }

    /**
     * @param $key
     * @param int $default
     * @return int
     * @since ZC v3.0.0
     */
    public function queryInteger($key, int $default = 0): int
    {
        return $this->toInteger($this->query($key, $default), $default);
    }

    /**
     * @param $key
     * @param bool $default
     * @return bool
     * @since ZC v3.0.0
     */
    public function boolean($key, bool $default = false): bool
    {
        return $this->toBoolean($this->input($key, $default), $default);
    }

    /**
     * @param $key
     * @param string $default
     * @return string
     * @since ZC v3.0.0
     */
    public function string($key, string $default = ''): string
    {
        return $this->toString($this->input($key, $default), $default);
    }

    /**
     * @param $key
     * @param string $default
     * @return string
     * @since ZC v3.0.0
     */
    public function postString($key, string $default = ''): string
    {
        return $this->toString($this->post($key, $default), $default);
    }

    /**
     * @param $key
     * @param string $default
     * @return string
     * @since ZC v3.0.0
     */
    public function queryString($key, string $default = ''): string
    {
        return $this->toString($this->query($key, $default), $default);
    }

    /**
     * @param $key
     * @param array $default
     * @return array
     * @since ZC v3.0.0
     */
    public function inputArray($key, array $default = []): array
    {
        return $this->toArray($this->input($key, $default), $default);
    }

    /**
     * @param mixed $value
     * @param int $default
     * @return int
     */
    protected function toInteger($value, int $default): int
    {
        if (is_array($value) || is_object($value) || $value === null || $value === '') {
            return $default;
        }

        return (int)$value;
    }

    /**
     * @param mixed $value
     * @param bool $default
     * @return bool
     */
    protected function toBoolean($value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_array($value) || is_object($value) || $value === null || $value === '') {
            return $default;
        }

        $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return ($filtered === null) ? $default : $filtered;
    }

    /**
     * @param mixed $value
     * @param string $default
     * @return string
     */
    protected function toString($value, string $default): string
    {
        if (is_array($value) || is_object($value) || $value === null) {
            return $default;
        }

        return (string)$value;
    }

    /**
     * @param mixed $value
     * @param array $default
     * @return array
     */
    protected function toArray($value, array $default): array
    {
        return is_array($value) ? $value : $default;
    }
}
