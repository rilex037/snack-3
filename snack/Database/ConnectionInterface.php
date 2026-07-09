<?php

declare(strict_types=1);

namespace Snack\Database;

use PDO;

interface ConnectionInterface
{
    public function select(string $query, array $bindings = []): array;

    public function selectOne(string $query, array $bindings = []): ?array;

    public function insert(string $query, array $bindings = []): string;

    public function affectingStatement(string $query, array $bindings = []): int;

    public function statement(string $query, array $bindings = []): bool;

    public function raw(string $query, array $bindings = []): \PDOStatement;

    public function transaction(\Closure $callback): mixed;

    public function pdo(): PDO;

    public function driver(): string;
}
