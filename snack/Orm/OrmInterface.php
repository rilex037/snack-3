<?php

declare(strict_types=1);

namespace Snack\Orm;

interface OrmInterface
{
    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    public function get(int $id): ?array;
    public function getAll(): array;
    public function insert(array $data): void;
    public function update(int $id, array $data): void;
    public function delete(int $id): void;
    public function join(string $table, string $on, string $type): OrmInterface;
    public function where(array $conditions): OrmInterface;
    public function orderBy(string $column, string $direction): OrmInterface;
    public function limit(int $limit): OrmInterface;
    public function offset(int $offset): OrmInterface;
}
