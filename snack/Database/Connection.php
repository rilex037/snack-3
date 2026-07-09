<?php

declare(strict_types=1);

namespace Snack\Database;

use PDO;
use PDOException;
use PDOStatement;
use Snack\Exception\QueryException;

final class Connection implements ConnectionInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $driverName
    ) {
    }

    public function select(string $query, array $bindings = []): array
    {
        $statement = $this->run($query, $bindings);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectOne(string $query, array $bindings = []): ?array
    {
        $statement = $this->run($query, $bindings);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function insert(string $query, array $bindings = []): string
    {
        $this->run($query, $bindings);

        return $this->pdo->lastInsertId();
    }

    public function affectingStatement(string $query, array $bindings = []): int
    {
        return $this->run($query, $bindings)->rowCount();
    }

    public function statement(string $query, array $bindings = []): bool
    {
        return $this->run($query, $bindings)->rowCount() >= 0;
    }

    public function raw(string $query, array $bindings = []): PDOStatement
    {
        return $this->run($query, $bindings);
    }

    public function transaction(\Closure $callback): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $callback($this);
            $this->pdo->commit();

            return $result;
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function driver(): string
    {
        return $this->driverName;
    }

    private function run(string $query, array $bindings): PDOStatement
    {
        try {
            $statement = $this->pdo->prepare($query);
            $statement->execute($this->normalizeBindings($bindings));

            return $statement;
        } catch (PDOException $exception) {
            throw QueryException::fromPdo($query, $bindings, $exception);
        }
    }

    private function normalizeBindings(array $bindings): array
    {
        return array_map(
            static fn (mixed $value): mixed => is_bool($value) ? (int) $value : $value,
            $bindings
        );
    }
}
