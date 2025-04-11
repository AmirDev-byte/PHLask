<?php

namespace PHLask\Http;

use Psr\Http\Message\UriInterface;

/**
 * Uri - کلاس آدرس URI
 *
 * پیاده‌سازی PSR-7 UriInterface
 */
class Uri implements UriInterface
{
    /**
     * @var string طرح (scheme) آدرس
     */
    private string $scheme = '';

    /**
     * @var string نام کاربری
     */
    private string $user = '';

    /**
     * @var string|null رمز عبور
     */
    private ?string $password = null;

    /**
     * @var string میزبان
     */
    private string $host = '';

    /**
     * @var int|null پورت
     */
    private ?int $port = null;

    /**
     * @var string مسیر
     */
    private string $path = '';

    /**
     * @var string پارامترهای کوئری
     */
    private string $query = '';

    /**
     * @var string قطعه (fragment)
     */
    private string $fragment = '';

    /**
     * @var array پورت‌های پیش‌فرض برای طرح‌های مختلف
     */
    private const DEFAULT_PORTS = [
        'http' => 80,
        'https' => 443,
        'ftp' => 21,
        'sftp' => 22,
    ];

    /**
     * سازنده کلاس Uri
     *
     * @param string $uri آدرس URI
     */
    public function __construct(string $uri = '')
    {
        if (!empty($uri)) {
            $parts = parse_url($uri);

            if ($parts === false) {
                throw new \InvalidArgumentException('Unable to parse URI: ' . $uri);
            }

            $this->scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
            $this->user = $parts['user'] ?? '';
            $this->password = isset($parts['pass']) ? $parts['pass'] : null;
            $this->host = isset($parts['host']) ? strtolower($parts['host']) : '';
            $this->port = isset($parts['port']) ? (int) $parts['port'] : null;
            $this->path = $parts['path'] ?? '';
            $this->query = $parts['query'] ?? '';
            $this->fragment = $parts['fragment'] ?? '';
        }
    }

    /**
     * ایجاد Uri از متغیرهای سراسری PHP
     *
     * @return Uri
     */
    public static function fromGlobals(): Uri
    {
        $uri = new self();

        // تشخیص طرح (scheme)
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $uri = $uri->withScheme($scheme);

        // تنظیم میزبان و پورت
        $host = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $uri->withHost($host);

        if (isset($_SERVER['SERVER_PORT'])) {
            $uri = $uri->withPort((int) $_SERVER['SERVER_PORT']);
        }

        // تنظیم مسیر و کوئری
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $queryPos = strpos($path, '?');

        if ($queryPos !== false) {
            $query = substr($path, $queryPos + 1);
            $path = substr($path, 0, $queryPos);
            $uri = $uri->withQuery($query);
        }

        return $uri->withPath($path);
    }

    /**
     * @inheritDoc
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @inheritDoc
     */
    public function getAuthority(): string
    {
        if (empty($this->host)) {
            return '';
        }

        $authority = $this->host;

        if (!empty($this->user)) {
            $userInfo = $this->user;
            if ($this->password !== null) {
                $userInfo .= ':' . $this->password;
            }
            $authority = $userInfo . '@' . $authority;
        }

        if ($this->port !== null && !$this->isDefaultPort()) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * @inheritDoc
     */
    public function getUserInfo(): string
    {
        if (empty($this->user)) {
            return '';
        }

        $userInfo = $this->user;
        if ($this->password !== null) {
            $userInfo .= ':' . $this->password;
        }

        return $userInfo;
    }

    /**
     * @inheritDoc
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @inheritDoc
     */
    public function getPort(): ?int
    {
        return $this->isDefaultPort() ? null : $this->port;
    }

    /**
     * بررسی می‌کند که آیا پورت فعلی، پورت پیش‌فرض برای طرح فعلی است یا خیر
     *
     * @return bool
     */
    private function isDefaultPort(): bool
    {
        if ($this->port === null) {
            return true;
        }

        if (empty($this->scheme)) {
            return false;
        }

        return isset(self::DEFAULT_PORTS[$this->scheme]) && self::DEFAULT_PORTS[$this->scheme] === $this->port;
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @inheritDoc
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @inheritDoc
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * @inheritDoc
     */
    public function withScheme($scheme): self
    {
        $scheme = strtolower($scheme);

        if ($this->scheme === $scheme) {
            return $this;
        }

        $clone = clone $this;
        $clone->scheme = $scheme;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withUserInfo($user, $password = null): self
    {
        if ($this->user === $user && $this->password === $password) {
            return $this;
        }

        $clone = clone $this;
        $clone->user = $user;
        $clone->password = $password;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withHost($host): self
    {
        $host = strtolower($host);

        if ($this->host === $host) {
            return $this;
        }

        $clone = clone $this;
        $clone->host = $host;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withPort($port): self
    {
        if ($port !== null) {
            $port = (int) $port;
            if ($port < 1 || $port > 65535) {
                throw new \InvalidArgumentException('Invalid port: ' . $port . '. Must be between 1 and 65535');
            }
        }

        if ($this->port === $port) {
            return $this;
        }

        $clone = clone $this;
        $clone->port = $port;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withPath($path): self
    {
        if (strpos($path, '?') !== false) {
            throw new \InvalidArgumentException('Path cannot contain a query string');
        }

        if (strpos($path, '#') !== false) {
            throw new \InvalidArgumentException('Path cannot contain a fragment');
        }

        if ($this->path === $path) {
            return $this;
        }

        // اطمینان از وجود / در ابتدای مسیر
        if (!empty($path) && $path[0] !== '/') {
            $path = '/' . $path;
        }

        $clone = clone $this;
        $clone->path = $path;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withQuery($query): self
    {
        if (is_string($query) && strpos($query, '?') === 0) {
            $query = substr($query, 1);
        }

        if (strpos($query, '#') !== false) {
            throw new \InvalidArgumentException('Query cannot contain a fragment');
        }

        if ($this->query === $query) {
            return $this;
        }

        $clone = clone $this;
        $clone->query = $query;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withFragment($fragment): self
    {
        if (is_string($fragment) && strpos($fragment, '#') === 0) {
            $fragment = substr($fragment, 1);
        }

        if ($this->fragment === $fragment) {
            return $this;
        }

        $clone = clone $this;
        $clone->fragment = $fragment;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        $uri = '';

        if (!empty($this->scheme)) {
            $uri .= $this->scheme . ':';
        }

        $authority = $this->getAuthority();
        if (!empty($authority)) {
            $uri .= '//' . $authority;
        }

        $uri .= $this->path;

        if (!empty($this->query)) {
            $uri .= '?' . $this->query;
        }

        if (!empty($this->fragment)) {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }
}