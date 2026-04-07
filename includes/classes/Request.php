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
        return self::fromArrays($_GET, $_POST, $_COOKIE, $_SERVER, $_REQUEST);
    }

    /**
     * @since ZC v2.2.1
     */
    public static function fromArrays(array $query = [], array $post = [], array $cookie = [], array $server = [], ?array $request = null): self
    {
        $self = self::getInstance();
        $self->queryBag = $query;
        $self->postBag = $post;
        $self->cookieBag = $cookie;
        $self->serverBag = $server;
        $self->paramBag = $request ?? array_replace($cookie, $query, $post);

        return $self;
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
     * @since ZC v2.2.1
     */
    public function exists($key): bool
    {
        return array_key_exists($key, $this->paramBag);
    }

    /**
     * @since ZC v2.2.1
     */
    public function all(): array
    {
        return $this->paramBag;
    }

    /**
     * @since ZC v2.2.1
     */
    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->paramBag)) {
                $result[$key] = $this->paramBag[$key];
            }
        }

        return $result;
    }

    /**
     * @since ZC v2.2.1
     */
    public function except(array $keys): array
    {
        $result = $this->paramBag;
        foreach ($keys as $key) {
            unset($result[$key]);
        }

        return $result;
    }

    /**
     * @since ZC v2.2.1
     */
    public function query($key = null, $default = null)
    {
        return $this->bagInput($this->queryBag, $key, $default);
    }

    /**
     * @since ZC v2.2.1
     */
    public function post($key = null, $default = null)
    {
        return $this->bagInput($this->postBag, $key, $default);
    }

    /**
     * @since ZC v2.2.1
     */
    public function cookie($key = null, $default = null)
    {
        return $this->bagInput($this->cookieBag, $key, $default);
    }

    /**
     * @since ZC v2.2.1
     */
    public function server($key = null, $default = null)
    {
        return $this->bagInput($this->serverBag, $key, $default);
    }

    /**
     * @since ZC v2.2.1
     */
    public function integer($key, int $default = 0): int
    {
        if (!$this->exists($key)) {
            return $default;
        }

        return (int) $this->input($key, $default);
    }

    /**
     * @since ZC v2.2.1
     */
    public function string($key, string $default = ''): string
    {
        if (!$this->exists($key)) {
            return $default;
        }

        $value = $this->input($key, $default);
        if (is_array($value)) {
            return $default;
        }

        return (string) $value;
    }

    /**
     * @since ZC v2.2.1
     */
    public function boolean($key, bool $default = false): bool
    {
        if (!$this->exists($key)) {
            return $default;
        }

        $value = $this->input($key);
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'off', 'no', ''], true)) {
                return false;
            }
        }

        return (bool) $value;
    }

    /**
     * @since ZC v2.2.1
     */
    public function inputArray($key, array $default = []): array
    {
        if (!$this->exists($key)) {
            return $default;
        }

        $value = $this->input($key);
        return is_array($value) ? $value : $default;
    }

    /**
     * @since ZC v2.2.1
     */
    public function filled($key): bool
    {
        if (!$this->exists($key)) {
            return false;
        }

        $value = $this->input($key);
        if (is_array($value)) {
            return $value !== [];
        }

        return trim((string) $value) !== '';
    }

    /**
     * @since ZC v2.2.1
     */
    protected function bagInput(array $bag, $key = null, $default = null)
    {
        if ($key === null) {
            return $bag;
        }

        return array_key_exists($key, $bag) ? $bag[$key] : $default;
    }
}
