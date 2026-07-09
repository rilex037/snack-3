<?php

declare(strict_types=1);

namespace Snack\Database\Query;

use Snack\Database\ConnectionInterface;
use Snack\Exception\QueryException;

class Builder
{
    private const OPERATORS = [
        '=', '!=', '<>', '<', '<=', '>', '>=',
        'like', 'not like', 'ilike', 'in', 'not in', 'is', 'is not',
    ];

    private array $columns = ['*'];

    private bool $distinct = false;

    private array $wheres = [];

    private array $joins = [];

    private array $orders = [];

    private array $groups = [];

    private array $havings = [];

    private ?int $limitValue = null;
    private ?int $offsetValue = null;

    private array $bindings = ['select' => [], 'where' => [], 'having' => []];

    public function __construct(
        protected readonly ConnectionInterface $connection,
        protected readonly string $table
    ) {
    }

    public static function on(ConnectionInterface $connection, string $table): static
    {
        return new static($connection, $table);
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function expr(string $sql): Expression
    {
        return new Expression($sql);
    }

    // ---------------------------------------------------------------
    // Select
    // ---------------------------------------------------------------

    public function select(string|Expression ...$columns): static
    {
        $this->columns = $columns === [] ? ['*'] : $columns;

        return $this;
    }

    public function addSelect(string|Expression ...$columns): static
    {
        array_push($this->columns, ...$columns);

        return $this;
    }

    public function selectRaw(string $sql, array $bindings = []): static
    {
        $this->columns[] = new Expression($sql);
        $this->addBindings($bindings, 'select');

        return $this;
    }

    public function distinct(): static
    {
        $this->distinct = true;

        return $this;
    }

    // ---------------------------------------------------------------
    // Where (helper forms)
    // ---------------------------------------------------------------

    public function where(string|\Closure $column, mixed $operator = null, mixed $value = null, string $boolean = 'and'): static
    {
        if ($column instanceof \Closure) {
            return $this->whereNested($column, $boolean);
        }

        if (func_num_args() === 2) {
            [$value, $operator] = [$operator, '='];
        }

        $this->assertValidOperator((string) $operator);

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
        ];

        if (!$value instanceof Expression) {
            $this->addBinding($value, 'where');
        }

        return $this;
    }

    public function orWhere(string|\Closure $column, mixed $operator = null, mixed $value = null): static
    {
        if ($column instanceof \Closure) {
            return $this->whereNested($column, 'or');
        }

        return func_num_args() === 2
            ? $this->where($column, '=', $operator, 'or')
            : $this->where($column, $operator, $value, 'or');
    }

    public function whereNested(\Closure $callback, string $boolean = 'and'): static
    {
        $nested = new static($this->connection, $this->table);
        $callback($nested);

        $this->wheres[] = ['type' => 'nested', 'query' => $nested, 'boolean' => $boolean];
        $this->addBindings($nested->getBindings(), 'where');

        return $this;
    }

    public function whereIn(string $column, array|self $values, string $boolean = 'and', bool $not = false): static
    {
        $type = $not ? 'notIn' : 'in';

        if ($values instanceof self) {
            $this->wheres[] = ["type" => $type . 'Sub', 'column' => $column, 'query' => $values, 'boolean' => $boolean];
            $this->addBindings($values->getBindings(), 'where');

            return $this;
        }

        $values = array_values($values);
        $this->wheres[] = ['type' => $type, 'column' => $column, 'values' => $values, 'boolean' => $boolean];
        $this->addBindings($values, 'where');

        return $this;
    }

    public function whereNotIn(string $column, array|self $values, string $boolean = 'and'): static
    {
        return $this->whereIn($column, $values, $boolean, not: true);
    }

    public function orWhereIn(string $column, array|self $values): static
    {
        return $this->whereIn($column, $values, 'or');
    }

    public function whereNull(string $column, string $boolean = 'and', bool $not = false): static
    {
        $this->wheres[] = ['type' => $not ? 'notNull' : 'null', 'column' => $column, 'boolean' => $boolean];

        return $this;
    }

    public function whereNotNull(string $column, string $boolean = 'and'): static
    {
        return $this->whereNull($column, $boolean, not: true);
    }

    public function orWhereNull(string $column): static
    {
        return $this->whereNull($column, 'or');
    }

    public function whereBetween(string $column, array $values, string $boolean = 'and', bool $not = false): static
    {
        $this->wheres[] = ['type' => $not ? 'notBetween' : 'between', 'column' => $column, 'values' => $values, 'boolean' => $boolean];
        $this->addBinding($values[0], 'where');
        $this->addBinding($values[1], 'where');

        return $this;
    }

    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'and'): static
    {
        $this->wheres[] = ['type' => 'raw', 'sql' => $sql, 'boolean' => $boolean];
        $this->addBindings($bindings, 'where');

        return $this;
    }

    public function orWhereRaw(string $sql, array $bindings = []): static
    {
        return $this->whereRaw($sql, $bindings, 'or');
    }

    // ---------------------------------------------------------------
    // Joins
    // ---------------------------------------------------------------

    public function join(string $table, string $first, string $operator, string $second, string $type = 'inner'): static
    {
        $this->joins[] = compact('table', 'first', 'operator', 'second', 'type');

        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    // ---------------------------------------------------------------
    // Group by / having
    // ---------------------------------------------------------------

    public function groupBy(string ...$columns): static
    {
        array_push($this->groups, ...$columns);

        return $this;
    }

    public function having(string $column, string $operator, mixed $value, string $boolean = 'and'): static
    {
        $this->assertValidOperator($operator);
        $this->havings[] = ['type' => 'basic', 'column' => $column, 'operator' => $operator, 'value' => $value, 'boolean' => $boolean];
        $this->addBinding($value, 'having');

        return $this;
    }

    public function havingRaw(string $sql, array $bindings = [], string $boolean = 'and'): static
    {
        $this->havings[] = ['type' => 'raw', 'sql' => $sql, 'boolean' => $boolean];
        $this->addBindings($bindings, 'having');

        return $this;
    }

    // ---------------------------------------------------------------
    // Order / limit / offset
    // ---------------------------------------------------------------

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->orders[] = ['column' => $column, 'direction' => strtolower($direction) === 'desc' ? 'desc' : 'asc'];

        return $this;
    }

    public function orderByRaw(string $sql): static
    {
        $this->orders[] = ['raw' => $sql];

        return $this;
    }

    public function limit(int $value): static
    {
        $this->limitValue = max(0, $value);

        return $this;
    }

    public function offset(int $value): static
    {
        $this->offsetValue = max(0, $value);

        return $this;
    }

    public function forPage(int $page, int $perPage = 15): static
    {
        return $this->offset(max(0, $page - 1) * $perPage)->limit($perPage);
    }

    // ---------------------------------------------------------------
    // Terminal read operations
    // ---------------------------------------------------------------

    public function get(): array
    {
        return $this->connection->select($this->toSql(), $this->getBindings());
    }

    public function first(): ?array
    {
        $rows = (clone $this)->limit(1)->get();

        return $rows[0] ?? null;
    }

    public function find(int|string $id, string $column = 'id'): ?array
    {
        return (clone $this)->where($column, '=', $id)->first();
    }

    public function value(string $column): mixed
    {
        $row = (clone $this)->select($column)->first();

        if ($row === null) {
            return null;
        }

        return $row[$column] ?? array_values($row)[0] ?? null;
    }

    public function pluck(string $column, ?string $key = null): array
    {
        $clone = clone $this;
        $key === null ? $clone->select($column) : $clone->select($column, $key);

        $result = [];

        foreach ($clone->get() as $row) {
            if ($key !== null) {
                $result[$row[$key]] = $row[$column];
            } else {
                $result[] = $row[$column];
            }
        }

        return $result;
    }

    public function count(string $column = '*'): int
    {
        $clone = clone $this;
        $clone->columns = [new Expression("COUNT({$column}) AS aggregate")];
        $clone->orders = [];
        $clone->limitValue = null;
        $clone->offsetValue = null;

        $row = $this->connection->selectOne($clone->toSql(), $clone->getBindings());

        return (int) ($row['aggregate'] ?? 0);
    }

    public function exists(): bool
    {
        $clone = clone $this;
        $clone->columns = [new Expression('1 AS exists_flag')];
        $clone->orders = [];
        $clone->offsetValue = null;
        $clone->limitValue = 1;

        return $this->connection->selectOne($clone->toSql(), $clone->getBindings()) !== null;
    }

    public function doesntExist(): bool
    {
        return !$this->exists();
    }

    public function chunk(int $count, callable $callback): void
    {
        $page = 1;

        do {
            $results = (clone $this)->forPage($page, $count)->get();

            if ($results === []) {
                break;
            }

            if ($callback($results) === false) {
                return;
            }

            $page++;
        } while (count($results) === $count);
    }

    // ---------------------------------------------------------------
    // Terminal write operations
    // ---------------------------------------------------------------

    public function insert(array $values): bool
    {
        if ($values === []) {
            return true;
        }

        $rows = array_is_list($values) && is_array(reset($values)) ? $values : [$values];
        $columns = array_keys($rows[0]);

        $placeholders = [];
        $bindings = [];

        foreach ($rows as $row) {
            $placeholders[] = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';

            foreach ($columns as $column) {
                $bindings[] = $row[$column];
            }
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $this->wrapIdentifier($this->table),
            implode(', ', array_map($this->wrapIdentifier(...), $columns)),
            implode(', ', $placeholders)
        );

        return $this->connection->statement($sql, $bindings);
    }

    public function insertGetId(array $values): string
    {
        $columns = array_keys($values);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->wrapIdentifier($this->table),
            implode(', ', array_map($this->wrapIdentifier(...), $columns)),
            implode(', ', array_fill(0, count($columns), '?'))
        );

        return $this->connection->insert($sql, array_values($values));
    }

    public function update(array $values): int
    {
        $sets = [];
        $bindings = [];

        foreach ($values as $column => $value) {
            if ($value instanceof Expression) {
                $sets[] = $this->wrapIdentifier($column) . ' = ' . (string) $value;
                continue;
            }

            $sets[] = $this->wrapIdentifier($column) . ' = ?';
            $bindings[] = $value;
        }

        $sql = sprintf('UPDATE %s SET %s', $this->wrapIdentifier($this->table), implode(', ', $sets));

        if ($this->wheres !== []) {
            $sql .= ' ' . $this->compileWheres();
        }

        return $this->connection->affectingStatement($sql, [...$bindings, ...$this->bindings['where']]);
    }

    public function delete(): int
    {
        if ($this->wheres === []) {
            throw new QueryException(
                'Refusing to run an unconditional DELETE. Add a where() clause, or call truncate() if you really mean to delete every row.'
            );
        }

        $sql = 'DELETE FROM ' . $this->wrapIdentifier($this->table) . ' ' . $this->compileWheres();

        return $this->connection->affectingStatement($sql, $this->bindings['where']);
    }

    public function truncate(): bool
    {
        // DELETE rather than TRUNCATE so this behaves identically across
        // every supported driver, including sqlite (which has no TRUNCATE).
        return $this->connection->statement('DELETE FROM ' . $this->wrapIdentifier($this->table));
    }

    public function increment(string $column, int|float $amount = 1, array $extra = []): int
    {
        return $this->update([$column => new Expression($this->wrapIdentifier($column) . ' + ' . $amount), ...$extra]);
    }

    public function decrement(string $column, int|float $amount = 1, array $extra = []): int
    {
        return $this->update([$column => new Expression($this->wrapIdentifier($column) . ' - ' . $amount), ...$extra]);
    }

    // ---------------------------------------------------------------
    // SQL compilation
    // ---------------------------------------------------------------

    public function toSql(): string
    {
        $sql = 'SELECT ' . ($this->distinct ? 'DISTINCT ' : '') . $this->compileColumns();
        $sql .= ' FROM ' . $this->wrapIdentifier($this->table);

        if (($joins = $this->compileJoins()) !== '') {
            $sql .= ' ' . $joins;
        }

        if ($this->wheres !== []) {
            $sql .= ' ' . $this->compileWheres();
        }

        if ($this->groups !== []) {
            $sql .= ' GROUP BY ' . implode(', ', array_map($this->wrapIdentifier(...), $this->groups));
        }

        if ($this->havings !== [] && ($having = $this->compileHavings()) !== '') {
            $sql .= ' ' . $having;
        }

        if (($orders = $this->compileOrders()) !== '') {
            $sql .= ' ' . $orders;
        }

        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        if ($this->offsetValue !== null) {
            $sql .= ' OFFSET ' . $this->offsetValue;
        }

        return $sql;
    }

    public function getBindings(): array
    {
        return [...$this->bindings['select'], ...$this->bindings['where'], ...$this->bindings['having']];
    }

    private function compileColumns(): string
    {
        if ($this->columns === []) {
            return '*';
        }

        return implode(', ', array_map(
            fn (string|Expression $column): string => $column instanceof Expression ? (string) $column : $this->wrapIdentifier($column),
            $this->columns
        ));
    }

    private function compileJoins(): string
    {
        $parts = [];

        foreach ($this->joins as $join) {
            $parts[] = sprintf(
                '%s JOIN %s ON %s %s %s',
                strtoupper($join['type']),
                $this->wrapIdentifier($join['table']),
                $this->wrapIdentifier($join['first']),
                $join['operator'],
                $this->wrapIdentifier($join['second'])
            );
        }

        return implode(' ', $parts);
    }

    private function compileWheres(): string
    {
        return $this->compileConditions($this->wheres, 'WHERE');
    }

    private function compileHavings(): string
    {
        return $this->compileConditions($this->havings, 'HAVING');
    }

    private function compileConditions(array $conditions, string $keyword): string
    {
        if ($conditions === []) {
            return '';
        }

        $segments = [];

        foreach ($conditions as $i => $condition) {
            $prefix = $i === 0 ? '' : strtoupper((string) $condition['boolean']) . ' ';
            $segments[] = $prefix . $this->compileCondition($condition);
        }

        return $keyword . ' ' . implode(' ', $segments);
    }

    private function compileCondition(array $condition): string
    {
        return match ($condition['type']) {
            'basic' => $this->wrapIdentifier($condition['column']) . ' ' . $condition['operator'] . ' '
                . ($condition['value'] instanceof Expression ? (string) $condition['value'] : '?'),
            'raw' => $condition['sql'],
            'in' => $this->wrapIdentifier($condition['column']) . ' IN (' . $this->placeholders($condition['values']) . ')',
            'notIn' => $this->wrapIdentifier($condition['column']) . ' NOT IN (' . $this->placeholders($condition['values']) . ')',
            'inSub' => $this->wrapIdentifier($condition['column']) . ' IN (' . $condition['query']->toSql() . ')',
            'notInSub' => $this->wrapIdentifier($condition['column']) . ' NOT IN (' . $condition['query']->toSql() . ')',
            'null' => $this->wrapIdentifier($condition['column']) . ' IS NULL',
            'notNull' => $this->wrapIdentifier($condition['column']) . ' IS NOT NULL',
            'between' => $this->wrapIdentifier($condition['column']) . ' BETWEEN ? AND ?',
            'notBetween' => $this->wrapIdentifier($condition['column']) . ' NOT BETWEEN ? AND ?',
            'nested' => '(' . substr($condition['query']->compileWheres(), 6) . ')',
            default => throw new QueryException("Unknown condition type [{$condition['type']}]."),
        };
    }

    private function placeholders(array $values): string
    {
        return implode(', ', array_fill(0, count($values), '?'));
    }

    private function compileOrders(): string
    {
        if ($this->orders === []) {
            return '';
        }

        $parts = array_map(
            fn (array $order): string => $order['raw'] ?? $this->wrapIdentifier($order['column']) . ' ' . strtoupper($order['direction']),
            $this->orders
        );

        return 'ORDER BY ' . implode(', ', $parts);
    }

    private function quoteChar(): string
    {
        return $this->connection->driver() === 'mysql' ? '`' : '"';
    }

    private function wrapIdentifier(string $value): string
    {
        if (stripos($value, ' as ') !== false) {
            [$column, $alias] = preg_split('/\s+as\s+/i', $value, 2);

            return $this->wrapIdentifier(trim($column)) . ' AS ' . trim($alias);
        }

        if ($value === '*') {
            return '*';
        }

        $quote = $this->quoteChar();

        return implode('.', array_map(
            fn (string $segment): string => $segment === '*' ? '*' : $quote . str_replace($quote, $quote . $quote, $segment) . $quote,
            explode('.', $value)
        ));
    }

    private function assertValidOperator(string $operator): void
    {
        if (!in_array(strtolower($operator), self::OPERATORS, true)) {
            throw new QueryException("Invalid operator [{$operator}].");
        }
    }

    private function addBindings(array $values, string $type): void
    {
        foreach ($values as $value) {
            $this->addBinding($value, $type);
        }
    }

    private function addBinding(mixed $value, string $type): void
    {
        $this->bindings[$type][] = $value;
    }
}
