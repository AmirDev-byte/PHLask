<?php

namespace PHLask\Http;

use Psr\Http\Message\StreamInterface;

/**
 * Stream - کلاس جریان داده
 *
 * پیاده‌سازی PSR-7 StreamInterface
 */
class Stream implements StreamInterface
{
    /**
     * @var resource منبع جریان داده
     */
    private $stream;

    /**
     * @var bool آیا جریان قابل خواندن است
     */
    private bool $readable;

    /**
     * @var bool آیا جریان قابل نوشتن است
     */
    private bool $writable;

    /**
     * @var bool آیا جریان قابل جستجو است
     */
    private bool $seekable;

    /**
     * @var int|null سایز جریان
     */
    private ?int $size = null;

    /**
     * @var string|null مسیر فایل (اگر از فایل ایجاد شده باشد)
     */
    private ?string $uri = null;

    /**
     * سازنده کلاس Stream
     *
     * @param resource $stream منبع جریان داده
     * @throws \InvalidArgumentException اگر منبع معتبر نباشد
     */
    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('Stream must be a resource');
        }

        $this->stream = $stream;

        $meta = stream_get_meta_data($this->stream);
        $this->seekable = $meta['seekable'] ?? false;
        $this->readable = false;
        $this->writable = false;

        $mode = $meta['mode'] ?? '';

        // تشخیص قابلیت خواندن و نوشتن بر اساس حالت فایل
        if (strpos($mode, 'r') !== false || strpos($mode, '+') !== false) {
            $this->readable = true;
        }

        if (strpos($mode, 'w') !== false || strpos($mode, 'a') !== false || strpos($mode, '+') !== false) {
            $this->writable = true;
        }

        // ذخیره مسیر فایل، اگر وجود داشته باشد
        if (isset($meta['uri'])) {
            $this->uri = $meta['uri'];
        }
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        if (isset($this->stream)) {
            fclose($this->stream);
            $this->detach();
        }
    }

    /**
     * @inheritDoc
     */
    public function detach()
    {
        if (!isset($this->stream)) {
            return null;
        }

        $result = $this->stream;
        $this->stream = null;
        $this->size = null;
        $this->uri = null;
        $this->readable = false;
        $this->writable = false;
        $this->seekable = false;

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getSize(): ?int
    {
        if (!isset($this->stream)) {
            return null;
        }

        if ($this->size !== null) {
            return $this->size;
        }

        $stats = fstat($this->stream);
        if (isset($stats['size'])) {
            $this->size = $stats['size'];
            return $this->size;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function tell(): int
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        $position = ftell($this->stream);
        if ($position === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return $position;
    }

    /**
     * @inheritDoc
     */
    public function eof(): bool
    {
        if (!isset($this->stream)) {
            return true;
        }

        return feof($this->stream);
    }

    /**
     * @inheritDoc
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * @inheritDoc
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->seekable) {
            throw new \RuntimeException('Stream is not seekable');
        }

        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new \RuntimeException('Unable to seek to stream position ' . $offset);
        }
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * @inheritDoc
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * @inheritDoc
     */
    public function write($string): int
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->writable) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }

        $size = fwrite($this->stream, $string);

        if ($size === false) {
            throw new \RuntimeException('Unable to write to stream');
        }

        return $size;
    }

    /**
     * @inheritDoc
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * @inheritDoc
     */
    public function read($length): string
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->readable) {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }

        $data = fread($this->stream, $length);

        if ($data === false) {
            throw new \RuntimeException('Unable to read from stream');
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getContents(): string
    {
        if (!isset($this->stream)) {
            throw new \RuntimeException('Stream is detached');
        }

        if (!$this->readable) {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }

        $contents = stream_get_contents($this->stream);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($key = null)
    {
        if (!isset($this->stream)) {
            return $key ? null : [];
        }

        $meta = stream_get_meta_data($this->stream);

        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }
}