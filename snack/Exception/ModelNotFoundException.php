<?php

declare(strict_types=1);

namespace Snack\Exception;

final class ModelNotFoundException extends SnackException
{
    public static function forId(string $model, int|string $id): self
    {
        return new self("No query results for model [{$model}] with id [{$id}].");
    }
}
