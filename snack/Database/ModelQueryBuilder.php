<?php

declare(strict_types=1);

namespace Snack\Database;

use Snack\Database\Query\Builder;
use Snack\Exception\ModelNotFoundException;

final class ModelQueryBuilder
{
    public function __construct(
        private readonly Builder $builder,
        private readonly string $modelClass
    ) {
    }

    public function toBase(): Builder
    {
        return $this->builder;
    }

    public function get(): array
    {
        return array_map($this->hydrate(...), $this->builder->get());
    }

    public function first(): ?Model
    {
        $row = $this->builder->first();

        return $row === null ? null : $this->hydrate($row);
    }

    public function find(int|string $id, string $column = 'id'): ?Model
    {
        $row = $this->builder->find($id, $column);

        return $row === null ? null : $this->hydrate($row);
    }

    public function findOrFail(int|string $id, string $column = 'id'): Model
    {
        return $this->find($id, $column) ?? throw ModelNotFoundException::forId($this->modelClass, $id);
    }

    public function __call(string $name, array $arguments): mixed
    {
        $result = $this->builder->{$name}(...$arguments);

        return $result instanceof Builder ? $this : $result;
    }

    private function hydrate(array $row): Model
    {
        $factory = [$this->modelClass, 'newFromBuilder'];

        return $factory($row);
    }
}
