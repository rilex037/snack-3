<?php

declare(strict_types=1);

namespace Snack\Exception;

final class QueryException extends SnackException
{
    public static function fromPdo(string $sql, array $bindings, \Throwable $previous): self
    {
        return new self(
            sprintf(
                'Query failed: %s -- SQL: %s -- Bindings: %s',
                $previous->getMessage(),
                $sql,
                json_encode($bindings)
            ),
            (int) $previous->getCode(),
            $previous
        );
    }
}
