<?php

declare(strict_types=1);

namespace Snack\Orm\Pdo;

use Snack\Orm\OrmInterface;

final class PdoOrm implements OrmInterface
{
    private static ?\PDO $pdo = null;
    private array $conditions = [];
    private array $joins = [];
    private string $orderBy = '';
    private int $offset;
    private int $limit;

    public function __construct()
    {
        if (!self::$pdo) {
            self::$pdo = require_once 'PdoInstance.php';
        }
    }

    public function get(int $id): ?array
    {
        $stmt = self::$pdo->prepare('SELECT * FROM table WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    public function getAll(): array
    {
        $stmt = self::$pdo->prepare($this->buildQuery());
        $stmt->execute($this->conditions);
        return $stmt->fetchAll();
    }

    public function insert(array $data): void
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(function ($key) {
            return ":$key";
        }, array_keys($data)));
        $stmt = self::$pdo->prepare("INSERT INTO table ($columns) VALUES ($placeholders)");
        $stmt->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $set = implode(', ', array_map(function ($key) {
            return "$key = :$key";
        }, array_keys($data)));
        $data['id'] = $id;
        $stmt = self::$pdo->prepare("UPDATE table SET $set WHERE id = :id");
        $stmt->execute($data);
    }

    public function delete(int $id): void
    {
        $stmt = self::$pdo->prepare('DELETE FROM table WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function join(string $table, string $on, string $type): OrmInterface
    {
        $this->joins[] = "$type JOIN $table ON $on";
        return $this;
    }

    public function where(array $conditions): OrmInterface
    {
        $this->conditions = $conditions;
        return $this;
    }

    public function orderBy(string $column, string $direction): OrmInterface
    {
        $this->orderBy = "$column $direction";
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

        if (!empty($this->joins)) {
            $query .= ' ' . implode(' ', $this->joins);
        }

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
