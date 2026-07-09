<?php

declare(strict_types=1);

namespace Snack\Http;

final class Request
{
    private function __construct(
        private readonly array $query,
        private readonly array $body,
        private readonly array $server,
        private readonly array $headers,
    ) {
    }

    public static function fromGlobals(): self
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = (string) $value;
            }
        }

        $body = $_POST;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $headers['CONTENT-TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = $raw !== '' ? json_decode($raw, true) : [];
            $body = is_array($decoded) ? $decoded : [];
        }

        return new self($_GET, $body, $_SERVER, $headers);
    }

    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function path(): string
    {
        $path = parse_url((string) ($this->server['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

        return $path !== null && $path !== false ? $path : '/';
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->query : ($this->query[$key] ?? $default);
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->body : ($this->body[$key] ?? $default);
    }

    public function all(): array
    {
        return [...$this->query, ...$this->body];
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtoupper($name)] ?? $this->headers[$name] ?? $default;
    }

    public function isJson(): bool
    {
        return str_contains($this->header('Content-Type', ''), 'application/json');
    }

    public function wantsJson(): bool
    {
        return str_contains($this->header('Accept', ''), 'application/json') || $this->isJson();
    }

    public function ip(): ?string
    {
        return $this->server['REMOTE_ADDR'] ?? null;
    }
}
