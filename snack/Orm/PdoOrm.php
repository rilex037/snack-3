<?php

declare(strict_types=1);

namespace Snack\Orm;

class PdoOrm implements OrmInterface
{
    private \PDO $pdo;
    private int $offset;
    private int $limit;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function get(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM table WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    public function getAll(): array
    {
        // TODO: Add business logic to retrieve all records from database
        return [];
    }

    public function insert(array $data): void
    {
        // TODO: Add business logic to insert record into database
    }

    public function update(int $id, array $data): void
    {
        // TODO: Add business logic to update record in database
    }

    public function delete(int $id): void
    {
        // TODO: Add business logic to delete record from database
    }

    public function join(string $table, string $on, string $type): OrmInterface
    {
        // TODO: Add business logic to join tables in database
        return $this;
    }

    public function where(array $conditions): OrmInterface
    {
        // TODO: Add business logic to apply conditions to query
        return $this;
    }

    public function orderBy(string $column, string $direction): OrmInterface
    {
        // TODO: Add business logic to apply order to query
        return $this;
    }

    public function offset(int $offset): OrmInterface
    {
        $this->offset = $offset;
        return $this;
    }

    public function limit(int $limit): OrmInterface
    {
        $this->limit = $limit;
        return $this;
    }

    private function buildQuery(): string
    {
        $query = 'SELECT * FROM table';

        if (!empty($this->conditions)) {
            $query .= ' WHERE ' . implode(' AND ', array_map(function ($k, $v) {
                return "$k = :$k";
            }, array_keys($this->conditions), $this->conditions));
        }

        if (!empty($this->orderBy)) {
            $query .= ' ORDER BY ' . $this->orderBy;
        }

        if (!empty($this->limit)) {
            $query .= ' LIMIT ' . $this->limit;
        }

        if (!empty($this->offset)) {
            $query .= ' OFFSET ' . $this->offset;
        }

        return $query;
    }
}
