<?php

declare(strict_types=1);

namespace Snack\Exception;

final class HttpException extends SnackException
{
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message !== '' ? $message : "HTTP {$statusCode}", 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
