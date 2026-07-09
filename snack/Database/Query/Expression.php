<?php

declare(strict_types=1);

namespace Snack\Database\Query;

final class Expression implements \Stringable
{
    public function __construct(private readonly string $sql)
    {
    }

    public function __toString(): string
    {
        return $this->sql;
    }
}
